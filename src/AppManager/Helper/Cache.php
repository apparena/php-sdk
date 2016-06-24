<?php
/**
 * Cache
 *
 * handle all caches in different types (File, Memcache, APC)
 *
 * @category    AppManager
 * @package     Helper
 * @subpackage  Cache
 *
 * @see         http://www.appalizr.com/
 *
 * @author      "Marcus Merchel" <kontakt@marcusmerchel.de>
 * @version     1.0.0 (28.02.14 - 14:58)
 */
namespace AppManager\Helper;

class Cache
{
    public $cache_dir = "";

    /**
     * Initialization of the Caching object
     * @param array $params Initialization parameter
     */
    public function __construct($params)
    {
        // Initialize Cache object
        if (isset($params['cache_dir']))
        {
            $this->cache_dir = rtrim($params['cache_dir'], "/");
        }

        if (file_exists($this->cache_dir) == false || is_dir($this->cache_dir) == false)
        {
            $this->createCacheDir($this->cache_dir);
        }
    }

    /**
     * Checks if the requested Key does exist
     * @param $cache_key
     * @return bool
     */
    function exists($cache_key)
    {
        $path = $this->cache_dir . "/" . $cache_key;;

        return file_exists($path);
    }

    /**
     * Saves something to the cache
     * @param string $cache_key
     * @param string $content
     * @param string $format
     * @return bool|int
     */
    function save($cache_key, $content, $format = "json")
    {
        $path = $this->cache_dir . "/" . $cache_key;

        if ($format == "json")
        {
            $content = json_encode($content);
        }

        return file_put_contents($path, $content);
    }

    function load($cache_key, $format = "json")
    {
        $path = $this->cache_dir . "/" . $cache_key;

        if ($this->exists($cache_key))
        {
            if ($format == "json") {
                return json_decode(file_get_contents($path), true);
            } else {
                return file_get_contents($path);
            }
        }
        else
        {2
            return null;
        }
    }

    /**
     * Cleans the whole cache for a certain key
     * @param $cache_key
     * @throws \Exception
     */
    function clean($cache_key)
    {
        $cache_key = str_replace('/', '_', $cache_key);

        $files = $this->getAllDirFiles($this->cache_dir);

        foreach ($files as $file)
        {
            if (strpos($file, str_replace("\\", "/", $this->cache_dir) . '/' . $cache_key) === 0)
            {
                unlink($file);
            }
        }
    }

    /**
     * Returns a list of all files
     * @param $dir
     * @return array
     * @throws \Exception
     */
    public function getAllDirFiles($dir)
    {
        $files = array();

        if (is_dir($dir) == false)
        {
            throw new \Exception("$dir is not a directory");
        }

        $dir = new \DirectoryIterator($dir);
        foreach ($dir as $fileinfo)
        {
            if (!$fileinfo->isDot() && !$fileinfo->isFile())
            {
                continue;
            }

            if (!$fileinfo->isDot() && $fileinfo->isFile())
            {
                $files[] = str_replace("\\", "/", $fileinfo->getPathname());
            }
        }

        return $files;
    }

    /**
     * Tries to create the caching directory from the submitted path.
     * @path String Absolute path of the cache directory
     */
    private function createCacheDir($path){

        $parts = explode("/", $path);

        if (count($parts) > 1) {
            $end = array_pop($parts);

            $parentPath = substr($path, 0, (strlen($end)+1) * -1);
            if (file_exists($parentPath) == false || is_dir($parentPath) == false)
            {
                // The parent folder does not exist neither... Call create function recursively
                $this->createCacheDir($parentPath);
            } else {
                // Parent folder exists, so create the subfolder
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
            }
        }
    }
}
