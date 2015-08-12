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
    private $file_id;
    private $less_files = array(); // Array of less files to be included
    private $variables = array(); // Variables to be replaced in the source files

    /**
     * Initializes the CSS compiler class
     * @param string $cache_dir Directory for the compiled CSS files
     * @param int    $i_id      Instance ID
     * @param string $lang      Language to generate the CSS for (does not need to be necessarily different
     * @param string $file_id   File identifier, in case you want to compile more than one file
     */
    function __construct($cache_dir, $i_id, $lang = "de_DE", $file_id = "style")
    {
        $this->i_id      = $i_id;
        $this->lang      = $lang;
        $this->file_id   = $file_id;
        $this->cache_dir = $cache_dir;

        $this->cache = new Cache(
            array(
                'cache_dir' => $cache_dir
            )
        );

        $this->cache_key = "instances_" . $this->i_id . "_" . $this->lang . "_" . $this->file_id . ".css";
    }

    /**
     * Minifies, compiles (Less) and concatenates Less and Css files and content
     * @param array $data         Array of filenames and CSS/Less content to be processed. Format:
     *                            $css_files = array(
     *                            'files' => array(
     *                            ROOT_PATH.'/css/style.css'
     *                            ROOT_PATH.'/css/bootstrap.min.css'
     *                            ),
     *                            'css' => array(
     *                            'body { color:red; }',
     *                            '@variable1: #123; p { color: @variable1; }'
     *                            )
     *                            );
     * @param array $replacements Array of values to be replaced in CSS/Less content before compiling. Format:
     *                            $css_replacements = array(
     *                            '{{app_color_primary.value}}' => __c('app_color_primary'),
     *                            '../fonts/fontawesome' => '../../js/vendor_bower/font-awesome/fonts/fontawesome'
     *                            );
     * @return string Filename of the generated CSS file
     * @throws \Exception
     */
    function concat(array $data, array $replacements)
    {
        $parser = new \Less_Parser();
        if (!$this->cache->exists($this->cache_key)) {
            if (isset($data['files'])) {
                $files = $data['files'];
            } else {
                $files = array();
            }

            if (isset($data['css'])) {
                $css = $data['css'];
            } else {
                $css = array();
            }

            // Get list of files and download them
            foreach ($files as $file) {
                $file_content = file_get_contents($file);

                foreach ($replacements as $k => $v) {
                    $file_content = str_replace($k, $v, $file_content);
                }
                $parser->parse($file_content);
            }

            // Get CSS content and add them to the parser object
            foreach ($css as $v) {
                $file_content = $v;

                foreach ($replacements as $k => $v) {
                    $file_content = str_replace($k, $v, $file_content);
                }
                $parser->parse($file_content);
            }

            $content = $parser->getCss();

            $this->cache->save($this->cache_key, $content, "plain");
        }

        return $this->cache_key;
    }


    /**
     * Returns the filepath to the compiled CSS file
     * @return string
     * @throws \Exception
     */
    public function getFilePath()
    {
        if (!$this->cache->exists($this->cache_key)) {

            // Compile Less files
            try {
                $parser = new \Less_Parser();
                foreach ($this->less_files as $file) {
                    $parser->parseFile($file);
                }

                // Replace Variables
                $parser->ModifyVars($this->variables);

                $css = $parser->getCss();

            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }

            $this->cache->save($this->cache_key, $css, "plain");
        }

        return $this->cache_dir . "/" . $this->cache_key;
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
     * @param Array $less_files Array of aboslute filepaths, which should be compiled
     */
    public function setLessFiles($less_files)
    {
        $this->less_files = $less_files;
    }


}
