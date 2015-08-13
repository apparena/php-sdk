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
namespace AppManager\Helper;

class Css
{
    private $i_id;
    private $lang;
    private $cache_key;
    private $cache_dir;
    private $root_path;
    private $file_id;
    private $files = array(); // Array of less files to be included
    private $config_values = array(); // Array of Config value IDs (Type CSS) which will be included in the compilation
    private $variables = array(); // Variables to be replaced in the source files
    private $replacements = array(); // Key value pair of string replacements in the compiled file

    /**
     * Initializes the CSS compiler class
     * @param string                      $cache_dir Directory for the compiled CSS files
     * @param \AppManager\Entity\Instance $instance  Instance object
     * @param string                      $lang      Language to generate the CSS for (does not need to be necessarily
     *                                               different
     * @param string                      $file_id   File identifier, in case you want to compile more than one file
     * @param string $root_path Absolute path to the project root
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

        $this->cache_key = "instances_" . $this->instance->getId() . "_" . $this->lang . "_" . $this->file_id . ".css";
    }

    /**
     * Returns the filepath to the compiled CSS file
     * @return string
     * @throws \Exception
     */
    public function getFilePath()
    {
        if (!$this->cache->exists($this->cache_key)) {


            try {
                // Compile Less files
                $options = array('compress' => true);
                $parser  = new \Less_Parser($options);

                // Add all files
                foreach ($this->files as $file) {
                    $parser->parseFile($file);
                }

                // Add CSS from config variables
                foreach ($this->config_values as $config_id) {
                    $value = $this->instance->getConfig($config_id, array("value", "type"));
                    if ($value['type'] == "css") {
                        $parser->parse($value['value']);
                    }
                }

                // Replace Variables
                $parser->ModifyVars($this->variables);

                $css_compiled = $parser->getCss();

            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }

            // Apply all replacements
            foreach ($this->replacements as $k => $v) {
                $css_compiled = str_replace($k, $v, $css_compiled);
            }

            $this->cache->save($this->cache_key, $css_compiled, "plain");
        }

        $base_path = substr($this->cache_dir, strlen($this->root_path));
        $url = $base_path . "/" . $this->cache_key;

        return $url;
    }

    /**
     * Key value pairs of variables and their values to be replaced in the original source file
     * @param Array $variables Variables and their values
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
    }

    /**
     * @param Array $files Array of absolute filepaths, which should be compiled
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
        $this->file_id = $file_id;
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




}
