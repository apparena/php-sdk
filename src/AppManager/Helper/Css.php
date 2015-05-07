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

    protected $parser = null;

    function __construct($cache_dir, $i_id, $locale)
    {
        $this->i_id   = $i_id;
        $this->locale = $locale;

        $this->cache = new Cache(
            array(
                'cache_dir' => $cache_dir
            )
        );

        $this->parser = new \Less_Parser();

        $this->cache_key = "instances_$i_id" . "_$locale" . ".css";
    }

    /**
     * Minifies, compiles (Less) and concatenates Less and Css files and content
     * @param array $data Array of filenames and CSS/Less content to be processed. Format:
     *                  $css_files = array(
     *                      'files' => array(
     *                          ROOT_PATH.'/css/style.css'
     *                          ROOT_PATH.'/css/bootstrap.min.css'
     *                      ),
     *                      'css' => array(
     *                          'body { color:red; }',
     *                          '@variable1: #123; p { color: @variable1; }'
     *                      )
     *                  );
     * @param array $replacements Array of values to be replaced in CSS/Less content before compiling. Format:
     *                  $css_replacements = array(
     *                       '{{app_color_primary.value}}' => __c('app_color_primary'),
     *                       '../fonts/fontawesome' => '../../js/vendor_bower/font-awesome/fonts/fontawesome'
     *                   );
     * @return string Filename of the generated CSS file
     * @throws \Exception
     */
    function concat(array $data, array $replacements)
    {
        if ($this->cache->exists($this->cache_key))
        {
            $content = $this->cache->load($this->cache_key);
        }
        else
        {
            if (isset($data['files']))
            {
                $files = $data['files'];
            }
            else
            {
                $files = array();
            }

            if (isset($data['css']))
            {
                $css = $data['css'];
            }
            else
            {
                $css = array();
            }

            // Get list of files and download them
            foreach ($files as $file)
            {
                $file_content = file_get_contents($file);

                foreach ($replacements as $k => $v)
                {
                    $file_content = str_replace($k, $v, $file_content);
                }
                $this->parser->parse($file_content);
            }

            // Get CSS content and add them to the parser object
            foreach ($css as $v)
            {
                $file_content = $v;

                foreach ($replacements as $k => $v)
                {
                    $file_content = str_replace($k, $v, $file_content);
                }
                $this->parser->parse($file_content);
            }

            $content = $this->parser->getCss();

            $this->cache->save($this->cache_key, $content, "plain");
        }

        return $this->cache_key;
    }


}
