<?php
/**
 * Css
 *
 * Concats, minifies CSS and compiles less
 *
 * @category    AppManager
 * @package     Helper
 * @subpackage  Cache
 *
 * @see         http://www.appalizr.com/
 *
 * @author      "Sebastian Buckpesch" <s.buckpesch@app-arena.com>
 * @version     1.0.0 (28.02.15 - 14:58)
 */

namespace AppArena\Models;

use AppArena\Models\Entities\AbstractEntity;
use Leafo\ScssPhp\Compiler;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class CssCompiler {
	private $lang;
	private $cache_key;
	private $cache_dir;
	/** @var  Cache */
	private $cache;
	private $root_path;
	private $file_id;
	private $files         = []; // Array of less files to be included
	private $config_values = []; // Array of Config value IDs (Type CSS) which will be included in the compilation
	private $variables     = []; // Variables to be replaced in the source files
	private $replacements  = []; // Key value pair of string replacements in the compiled file

	/**
	 * Initializes the CSS compiler class
	 *
	 * @param Cache          $cache                  Directory for the compiled CSS files
	 * @param AbstractEntity $entity                 Entity object
	 * @param string         $lang                   Language to generate the CSS for (does not need to be necessarily
	 *                                               different
	 * @param string         $file_id                File identifier, in case you want to compile more than one file
	 * @param string         $root_path              Absolute path to the project root
	 */
	function __construct( Cache $cache, AbstractEntity $entity, $lang = "de_DE", $file_id = "style", $root_path = "", $cache_dir = "/var/cache" ) {
		$this->cache     = $cache;
		$this->entity    = $entity;
		$this->lang      = $lang;
		$this->file_id   = $file_id;
		$this->root_path = $root_path;
		$this->cache_dir = $cache_dir;
	}

	/**
	 * (DEPRECATED) Will be replaced by function getCompiledCss()
	 * @return string Result of getCompiledCss
	 * @throws \Exception
	 */
	public function getFilePath() {
		return $this->getCompiledCss();
	}

	/**
	 * Returns an array of compiled css files. You can submit a CSS config array regarding to the
	 * documentation, including CSS, Less, SCSS files. Furthermore you can define tring replacements
	 * and use config variables of the current app.
	 * @see http://app-arena.readthedocs.org/en/latest/sdk/php/030-css.html
	 *
	 * @param $css_config array CSS Configuration array
	 *
	 * @return array Assocative array including all compiled CSS files
	 */
	public function getCSSFiles( $css_config ) {

		$compiled_files = [];
		foreach ( $css_config as $file_id => $css_file ) {
			$this->setFileId( $file_id );
			// Reset settings
			$this->setConfigValues( [] );
			$this->setFiles( [] );
			$this->setVariables( [] );
			$this->setReplacements( [] );

			if ( isset( $css_file['config_values'] ) ) {
				$this->setConfigValues( $css_file['config_values'] );
			}
			if ( isset( $css_file['files'] ) ) {
				$this->setFiles( $css_file['files'] );
			}
			if ( isset( $css_file['variables'] ) ) {
				$this->setVariables( $css_file['variables'] );
			}
			if ( isset( $css_file['replacements'] ) ) {
				$this->setReplacements( $css_file['replacements'] );
			}

			$compiled_files[] = $this->getCompiledCss();
		}

		return $compiled_files;
	}

	/**
	 * Compiles the current configuration
	 * @return string Relative file path to the compiled CSS file
	 * @throws \Exception
	 */
	public function getCompiledCss() {

		try {

			// Get the filename
			$absolutePath = $this->cache_dir . '/' . $this->cache_key;
			$relativePath = substr( $this->cache_dir, strlen( $this->root_path ) ) . '/' . $this->cache_key;

			/** @var TagAwareAdapter $cache */
			$cache     = $this->getCache()->getAdapter();
			$cacheItem = $cache->getItem( $this->cache_key );
			if ( !$cacheItem->isHit() ) {

				// Compile all submitted files
				$response = $this->compileFiles($this->files);

				// Attach all CSS/LESS/SCSS from config values
				foreach ( $this->config_values as $config_id ) {
					$value = $this->entity->getConfig( $config_id, [ "value", "type", "compiler" ] );

					if ( $value['type'] === 'css' ) {
						switch ( $value['compiler'] ) {
							case "scss":
								$response .= $this->compileScss( $value['value'] );
								break;
							default:
								$response .= $this->compileLess( $value['value'] );
								break;
						}
					}
				}

				// Apply all replacements
				foreach ( $this->replacements as $k => $v ) {
					$response = str_replace( $k, $v, $response );
				}

				// Save compiled CSS to cache
				$cacheItem->set( $response );
				$cache->save( $cacheItem );

				// Write CSS to a file
				file_put_contents($absolutePath, $response);

				// Set tags (important to do this after saving, to avoid a loop)
				$cacheItem->tag( [$this->entity->getEntityType() . '.' . $this->entity->getId()] );
				$cache->save( $cacheItem );

			} else {
				// If the redis cache has the item, but the file does not exist, then write the file again
				if (!file_exists($absolutePath)) {
					$response = $cacheItem->get();
					file_put_contents($absolutePath, $response);
				}
			}

		} catch ( \Exception $e ) {
			throw $e;
		}

		// Attach a cache busting parameter to the app, when the GET parameter preview is set to true
		if (isset($_GET['preview']) && $_GET['preview']) {
			$relativePath .= '?v=' . time();
		}


		return $relativePath;
	}

	/**
	 * @param array $files A list of files to compile
	 * @return String Compiled CSS
	 * @throws \Exception When the compilation fails
	 */
	private function compileFiles( $files ) {

		$response = '';
		try {
			// Add all files
			foreach ( $files as $file ) {

				// Get the file extension to decide which compiler to use
				$path_parts = pathinfo( $file );
				$extension  = $path_parts['extension'];

				switch ( $extension ) {
					case "less":
						$response .= $this->compileLessFile( $file );
						break;
					case "scss":
						$response .= $this->compileScssFile( $file );
						break;
					case "css":
						$response .= $this->compressCssFile( $file );
						break;
				}
			}

		} catch ( \Exception $e ) {
			throw $e;
		}

		return $response;
	}

	/**
	 * Compiles a file using http://leafo.net/scssphp/
	 *
	 * @param $file string Path to the less file to compile
	 *
	 * @return String compiled
	 */
	private function compileScssFile( $file ) {
		$scss_parser = new Compiler();
		$scss_parser->setFormatter( 'Leafo\ScssPhp\Formatter\Compressed' );
		// Set the import path to the current files path
		$path_parts  = pathinfo( $file );
		$import_path = $path_parts['dirname'];
		$scss_parser->setImportPaths( $import_path );

		// Replace Variables
		$scss_parser->setVariables( $this->variables );

		// Compile the files source
		$file_content = file_get_contents( $file );
		$compiled_css = $scss_parser->compile( $file_content );

		return $compiled_css;
	}

	/**
	 * Compiles scss code using http://leafo.net/scssphp/
	 *
	 * @param $scss string Scss code to compile
	 *
	 * @return String compiled css
	 */
	private function compileScss( $scss ) {
		$scss_parser = new Compiler();
		$scss_parser->setFormatter( 'Leafo\ScssPhp\Formatter\Compressed' );
		$scss_parser->setVariables( $this->variables );
		$compiled_css = $scss_parser->compile( $scss );

		return $compiled_css;
	}

	/**
	 * Compiles a file using http://lessphp.gpeasy.com/
	 *
	 * @param $file string Path to the less file to compile
	 *
	 * @return String compiled
	 */
	private function compileLessFile( $file ) {
		// Compile Less files
		$options     = [ 'compress' => true ];
		$less_parser = new \Less_Parser( $options );

		$less_parser->parseFile( $file );
		$less_parser->ModifyVars( $this->variables );

		return $less_parser->getCss();
	}

	/**
	 * Compresses a CSS file using http://lessphp.gpeasy.com/
	 *
	 * @param $file string Path to the less file to compile
	 *
	 * @return String compiled
	 */
	private function compressCssFile( $file ) {
		// Compile Less files
		$options     = [ 'compress' => true ];
		$less_parser = new \Less_Parser( $options );

		$less_parser->parseFile( $file );

		return $less_parser->getCss();
	}

	/**
	 * Compiles less code using http://lessphp.gpeasy.com/
	 *
	 * @param $less string Less code
	 *
	 * @return String compiled CSS
	 */
	private function compileLess( $less ) {
		// Compile Less files
		$options     = [ 'compress' => true ];
		$less_parser = new \Less_Parser( $options );

		$less_parser->parse( $less );
		$less_parser->ModifyVars( $this->variables );

		return $less_parser->getCss();
	}

	/**
	 * Key value pairs of variables and their values to be replaced in the original source file
	 *
	 * @param array $variables Variables and their values
	 */
	public function setVariables( $variables ) {
		$this->variables = $variables;
	}

	/**
	 * @param array $files Array of absolute filepaths, which should be compiled
	 */
	public function setFiles( $files ) {
		$this->files = $files;
	}

	/**
	 * Set an array with config value IDs, which will be included in the compilation process of the file
	 *
	 * @param array $config_values
	 */
	public function setConfigValues( $config_values ) {
		$this->config_values = $config_values;
	}

	/**
	 * Sets the file ID to be used to generate the cache file
	 *
	 * @param string $file_id
	 */
	public function setFileId( $file_id ) {
		$this->file_id   = $file_id;
		$this->cache_key = $this->entity->getEntityType() . "s_" . $this->entity->getId() . "_" . $this->lang . "_" . $this->file_id . ".css";
	}

	/**
	 * Search and replaces all strings (keys) of the submitted array with the corresponding value in the compiled css
	 * source
	 *
	 * @param array $replacements Array of key-value pairs. Key = Search-Term, Value = Replacement
	 */
	public function setReplacements( $replacements ) {
		$this->replacements = $replacements;
	}

	/**
	 * Returns the filename/cachekey of the submitted file-ID
	 *
	 * @param $file_id
	 *
	 * @return string
	 */
	public function getCacheKey( $file_id = false ) {
		if ( ! $file_id ) {
			$file_id = $this->file_id;
		}

		return $this->entity->getEntityType() . "s_" . $this->entity->getId() . "_" . $this->lang . "_" . $file_id . ".css";
	}

	/**
	 * @return Cache
	 */
	private function getCache() {
		return $this->cache;
	}


}
