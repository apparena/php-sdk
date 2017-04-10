<?php
/**
 * Created by PhpStorm.
 * User: s.buckpesch
 * Date: 10.04.2017
 * Time: 10:09
 */

namespace AppArena\Models\CSSCompiler;


use Leafo\ScssPhp\Compiler;

class SCSS extends AbstractCSSCompiler {

	private $compiler;

	/**
	 * SCSS constructor.
	 */
	public function __construct() {
		// Initialize the SCSS compiler
		$this->compiler = new Compiler();
		$this->compiler->setFormatter( 'Leafo\ScssPhp\Formatter\Compressed' );


	}


	/**
	 * @inheritdoc
	 */
	public function compile() {
		$this->compiler->setVariables( $this->variables );

		// Get compiled content
		$compiled_css = $this->compiler->compile( $scss );

		return $compiled_css;
	}


	/**
	 * Compiles all files
	 * @return bool
	 */
	public function compileFiles(){
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

}