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
namespace AppArena\Helper;

use Leafo\ScssPhp\Compiler;

class Css
{
    private $i_id;
    private $lang;
    private $cache_key;
    private $cache_dir;
    private $root_path;
    private $file_id;
    private $files         = array(); // Array of less files to be included
    private $config_values = array(); // Array of Config value IDs (Type CSS) which will be included in the compilation
    private $variables     = array(); // Variables to be replaced in the source files
    private $replacements  = array(); // Key value pair of string replacements in the compiled file

    /**
     * Initializes the CSS compiler class
     * @param string                      $cache_dir Directory for the compiled CSS files
     * @param \AppArena\Instance $instance  Instance object
     * @param string                      $lang      Language to generate the CSS for (does not need to be necessarily
     *                                               different
     * @param string                      $file_id   File identifier, in case you want to compile more than one file
     * @param string                      $root_path Absolute path to the project root
     */
    function __construct($cache_dir, $instance, $lang = "de_DE", $file_id = "style", $root_path = "")
    {
        $this->instance  = $instance;
        $this->lang      = $lang;
        $this->file_id   = $file_id;
        $this->cache_dir = $cache_dir;
        $this->root_path = $root_path;

        $this->cache = new Cache(
            array(
                'cache_dir' => $cache_dir
            )
        );

        $this->cache_key = $this->getCacheKey();
    }

    /**
     * (DEPRECATED) Will be replaced by function getCompiledCss()
     * @return string Result of getCompiledCss
     * @throws \Exception
     */
    public function getFilePath()
    {
        return $this->getCompiledCss();
    }

    /**
     * Compiles the current configuration
     * @return string Relative file path to the compiled CSS file
     * @throws \Exception
     */
    public function getCompiledCss()
    {
        if (!$this->cache->exists($this->cache_key)) {

            $css_compiled = "";
            try {
                // Add all files
                foreach ($this->files as $file) {

                    // Get the file extension to decide which compiler to use
                    $path_parts = pathinfo($file);
                    $extension  = $path_parts['extension'];

                    switch ($extension) {
                        case "less":
                            $css_compiled .= $this->compileLessFile($file);
                            break;
                        case "scss":
                            $css_compiled .= $this->compileScssFile($file);
                            break;
                        case "css":
                            $css_compiled .= $this->compressCssFile($file);
                            break;
                    }
                }

            } catch (\Exception $e) {
                $error_message = $e->getMessage();
            }

            // Attach all CSS/LESS/SCSS from config values
            foreach ($this->config_values as $config_id) {
                $value = $this->instance->getConfig($config_id, array("value", "type", "compiler"));

                if ($value['type'] == "css") {
                    switch ($value['compiler']) {
                        case "scss":
                            $css_compiled .= $this->compileScss($value['value']);
                            break;
                        default:
                            $css_compiled .= $this->compileLess($value['value']);
                            break;
                    }
                }
            }

            // Apply all replacements
            foreach ($this->replacements as $k => $v) {
                $css_compiled = str_replace($k, $v, $css_compiled);
            }

            $this->cache->save($this->cache_key, $css_compiled, "plain");
        }

        $base_path = substr($this->cache_dir, strlen($this->root_path));
        $url       = $base_path . "/" . $this->cache_key;

        return $url;
    }


    /**
     * Compiles a file using http://leafo.net/scssphp/
     * @param $file string Path to the less file to compile
     * @return String compiled
     */
    private function compileScssFile($file)
    {
        $scss_parser = new Compiler();
        $scss_parser->setFormatter('Leafo\ScssPhp\Formatter\Compressed');
        // Set the import path to the current files path
        $path_parts  = pathinfo($file);
        $import_path = $path_parts['dirname'];
        $scss_parser->setImportPaths($import_path);

        // Replace Variables
        $scss_parser->setVariables($this->variables);

        // Compile the files source
        $file_content = file_get_contents($file);
        $compiled_css = $scss_parser->compile($file_content);

        return $compiled_css;
    }

    /**
     * Compiles scss code using http://leafo.net/scssphp/
     * @param $scss string Scss code to compile
     * @return String compiled css
     */
    private function compileScss($scss)
    {
        $scss_parser = new Compiler();
        $scss_parser->setFormatter('Leafo\ScssPhp\Formatter\Compressed');
        $scss_parser->setVariables($this->variables);
        $compiled_css = $scss_parser->compile($scss);

        return $compiled_css;
    }

    /**
     * Compiles a file using http://lessphp.gpeasy.com/
     * @param $file string Path to the less file to compile
     * @return String compiled
     */
    private function compileLessFile($file)
    {
        // Compile Less files
        $options = array('compress' => true);
        $less_parser = new \Less_Parser($options);

        $less_parser->parseFile($file);
        $less_parser->ModifyVars($this->variables);

        return $less_parser->getCss();
    }

    /**
     * Compresses a CSS file using http://lessphp.gpeasy.com/
     * @param $file string Path to the less file to compile
     * @return String compiled
     */
    private function compressCssFile($file)
    {
        // Compile Less files
        $options = array('compress' => true);
        $less_parser = new \Less_Parser($options);

        $less_parser->parseFile($file);

        return $less_parser->getCss();
    }

    /**
     * Compiles less code using http://lessphp.gpeasy.com/
     * @param $less string Less code
     * @return String compiled CSS
     */
    private function compileLess($less)
    {
        // Compile Less files
        $options = array('compress' => true);
        $less_parser = new \Less_Parser($options);

        $less_parser->parse($less);
        $less_parser->ModifyVars($this->variables);

        return $less_parser->getCss();
    }

    /**
     * Key value pairs of variables and their values to be replaced in the original source file
     * @param array $variables Variables and their values
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
    }

    /**
     * @param array $files Array of absolute filepaths, which should be compiled
     */
    public function setFiles($files)
    {
        $this->files = $files;
    }

    /**
     * Set an array with config value IDs, which will be included in the compilation process of the file
     * @param array $config_values
     */
    public function setConfigValues($config_values)
    {
        $this->config_values = $config_values;
    }

    /**
     * Sets the file ID to be used to generate the cache file
     * @param string $file_id
     */
    public function setFileId($file_id)
    {
        $this->file_id   = $file_id;
        $this->cache_key = "instances_" . $this->instance->getId() . "_" . $this->lang . "_" . $this->file_id . ".css";
    }

    /**
     * Search and replaces all strings (keys) of the submitted array with the corresponding value in the compiled css
     * source
     * @param array $replacements Array of key-value pairs. Key = Search-Term, Value = Replacement
     */
    public function setReplacements($replacements)
    {
        $this->replacements = $replacements;
    }

    /**
     * Returns the filename/cachekey of the submitted file-ID
     * @param $file_id
     * @return string
     */
    public function getCacheKey($file_id = false)
    {
        if (!$file_id) {
            $file_id = $this->file_id;
        }

        return "instances_" . $this->instance->getId() . "_" . $this->lang . "_" . $file_id . ".css";
    }

}
