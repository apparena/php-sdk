<?php

namespace AppArena\Models;

use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class Cache {

	private $namespace = 'phpSdk';
	private $entityId;
	private $entityType;

	private $dir;


	/** @var TagAwareAdapter */
	private $adapter;

	/**
	 * Initialization of the Caching object
	 *
	 * @param array $options Cache options
	 *
	 * @throws \Exception
	 */
	public function __construct( $options ) {
		if ( isset( $options['namespace'] ) ) {
			$this->namespace = $options['namespace'];
		}
		if ( ! isset( $options['entityId'], $options['entityType'] ) ) {
			throw new \InvalidArgumentException( 'Entity type or entity ID not available in the Caching adapter' );
		}
		$this->entityId   = $options['entityId'];
		$this->entityType = $options['entityType'];

		$defaultLifetime = 0;
		$dir       = null; // the main cache directory (the application needs read-write permissions on it). if none is specified, a directory is created inside the system temporary directory
		if ( isset( $options['dir'] ) ) {
			$dir = $options['dir'];
		}
		if ( isset( $options['directory'] ) ) {
			$dir = $options['directory'];
		}
		if ( $dir ) {
			if ( ! @mkdir( $dir, 0755, true ) && ! is_dir( $dir ) ) {
				throw new \Exception( 'Cannot create cache folder' );
			}

			if ( ! is_writable( $dir ) ) {
				throw new \Exception( $dir . ' is not writeable for the webserver.' );
			}

		}
		$this->dir = $dir;

		// Initialize the cache adapter
		if ( isset( $options['adapter'] ) && $options['adapter'] instanceof AbstractAdapter ) {
			$adapter = $options['adapter'];
		} else {

			// Initialize the file cache as fallback or primary option @see http://symfony.com/doc/current/components/cache/cache_pools.html
			$adapter = new FilesystemAdapter( $this->namespace, $defaultLifetime, $dir );

			// Check if redis configuration is available
			if ( isset( $options['redis'], $options['redis']['host'] ) ) {
				$port            = isset( $options['redis']['port'] ) ? $options['redis']['port'] : 6379;
				$redisConnection = RedisAdapter::createConnection( 'redis://' . $options['redis']['host'] . ':' . $port );
				$adapter         = new RedisAdapter( $redisConnection, $this->namespace, $defaultLifetime );
			}
		}

		// Convert Cache adapter to Tag aware adapter
		$this->adapter = new TagAwareAdapter( $adapter );

		// Process cache cleaning parameters
		if (isset($_GET['cacheInvalidate'])) {
			$this->cacheInvalidate($_GET['cacheInvalidate']);
		}
	}

	/**
	 * @return TagAwareAdapter
	 */
	public function getAdapter() {
		return $this->adapter;
	}

	/**
	 * Invalidate the cache of a submitted entity. See parameter settings in cache section of the documentation
	 * @param string $action Can be 'all', 'configs', 'infos', 'languages', 'translations', 'apps' or 'templates'
	 */
	public function cacheInvalidate( $action ) {

		switch ( $action ) {
			case 'all':
				// Invalidates all caches of the currently requested entity
				$this->invalidateAll();
				break;
			case 'channels':
				// Invalidates the channels cache of the currently requested entity and language
				$this->invalidateChannels();
				break;
			case 'configs':
				// Invalidates the config cache of the currently requested entity and language
				$this->invalidateConfigs();
				break;
			case 'infos':
				// Invalidates the basic information of the currently requested entity and language
				$this->invalidateInfos();
				break;
			case 'languages':
				// Invalidates the languages cache of the currently requested entity
				$this->invalidateLanguages();
				break;
			case 'translations':
				// Invalidates the translations cache of the currently requested entity and language
				$this->invalidateTranslations();
				break;
			case 'apps':
				// Invalidates all caches of all apps of the currently requested template
				if ( $this->entityType === 'template' ) {
					$this->invalidateTemplateApps();
				}
				break;
			case 'templates':
				// Invalidates all caches of all sub-templates of the currently requested template or version
				if ( $this->entityType === 'template' ) {
					$this->invalidateTemplateSubtemplates();
				}
				/*if ($this->entityType === 'version') {
					$this->invalidateVersionTemplates();
				}*/
				break;
		}

	}

	/**
	 * Invalidates all caches of the currently requested entity
	 */
	private function invalidateAll() {
		$cache = $this->getAdapter();
		$tags  = [
			$this->entityType . '.' . $this->entityId
		];
		$cache->invalidateTags( $tags );

		// Delete CSS file cache
		$this->invalidateCssFileCache();
	}

	/**
	 * Removes all css files matching a certain cache key pattern
	 */
	private function invalidateCssFileCache(  ) {
		$key = $this->entityType . 's_' . $this->entityId;
		$files = $this->getAllDirFiles($this->dir);
		foreach ($files as $file)
		{
			if (strpos($file, str_replace("\\", "/", $this->dir) . '/' . $key) === 0)
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
	private function getAllDirFiles($dir)
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
	 * Invalidates the config cache of the currently requested entity and language
	 */
	private function invalidateChannels() {
		$cache = $this->getAdapter();
		$tags  = [
			$this->entityType . '.' . $this->entityId . '.channels',
		];
		$cache->invalidateTags( $tags );
	}

	/**
	 * Invalidates the config cache of the currently requested entity and language
	 */
	private function invalidateConfigs() {
		$cache = $this->getAdapter();
		$tags  = [
			$this->entityType . '.' . $this->entityId . '.configs',
		];
		$cache->invalidateTags( $tags );
	}

	/**
	 * Invalidates the basic information of the currently requested entity and language
	 */
	private function invalidateInfos() {
		$cache = $this->getAdapter();
		$tags  = [
			$this->entityType . '.' . $this->entityId . '.infos',
		];
		$cache->invalidateTags( $tags );
	}

	/**
	 * Invalidates the languages cache of the currently requested entity
	 */
	private function invalidateLanguages() {
		$cache = $this->getAdapter();
		$tags  = [
			$this->entityType . '.' . $this->entityId . '.languages',
		];
		$cache->invalidateTags( $tags );
	}

	/**
	 * Invalidates the translations cache of the currently requested entity and language
	 */
	private function invalidateTranslations() {
		$cache = $this->getAdapter();
		$tags  = [
			$this->entityType . '.' . $this->entityId . '.translations',
		];
		$cache->invalidateTags( $tags );
	}

	/**
	 * Invalidates all caches of all apps of the currently requested template
	 */
	private function invalidateTemplateApps() {
		$cache = $this->getAdapter();
		$tags  = [
			'appTemplate.' . $this->entityId,
		];
		$cache->invalidateTags( $tags );
	}

	/**
	 * Invalidates all caches of all sub-templates of the currently requested template
	 */
	private function invalidateTemplateSubtemplates() {
		$cache = $this->getAdapter();
		$tags  = [
			'parentTemplate.' . $this->entityId,
		];
		$cache->invalidateTags( $tags );
	}

	/**
	 * Invalidates all caches of all templates of the currently requested version
	 */
	/*private function invalidateVersionTemplates(  ) {
		$cache = $this->getAdapter();
		$tags = [
			'templateVersion.' . $this->entityId,
		];
		$cache->invalidateTags($tags);
	}*/
}
