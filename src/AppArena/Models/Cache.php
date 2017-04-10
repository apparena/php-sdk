<?php

namespace AppArena\Models;

use Cache\Namespaced\NamespacedCachePool;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class Cache {

	private $namespace = 'phpSdk';
	private $entityId;
	private $entityType;


	/** @var TagAwareAdapter */
	private $adapter;

	/**
	 * Initialization of the Caching object
	 *
	 * @param array $options Cache options
	 * @throws \Exception
	 */
	public function __construct( $options ) {
		if (isset($options['namespace'])) {
			$this->namespace  = $options['namespace'];
		}
		if (!isset($options['entityId'], $options['entityType'])) {
			throw new \InvalidArgumentException('Entity type or entity ID not available in the Caching adapter');
		}
		$this->entityId   = $options['entityId'];
		$this->entityType = $options['entityType'];

		$defaultLifetime = 0;
		$directory       = null; // the main cache directory (the application needs read-write permissions on it). if none is specified, a directory is created inside the system temporary directory
		if ( isset( $options['directory'] ) ) {
			if (!@mkdir( $options['directory'], 0755, true ) && !is_dir($options['directory'])){
				throw new \Exception('Cannot create cache folder');
			}

			if ( ! is_writable( $options['directory'] ) ) {
				throw new \Exception( $options['directory'] . ' is not writeable for the webserver.' );
			}
			$directory = $options['directory'];
		}

		if ( isset( $options['adapter'] ) && $options['adapter'] instanceof AbstractAdapter ) {
			$adapter = $options['adapter'];
		} else {

			// Initialize the file cache as fallback or primary option @see http://symfony.com/doc/current/components/cache/cache_pools.html
			$adapter = new FilesystemAdapter( $this->namespace, $defaultLifetime, $directory );

			// Check if redis configuration is available
			if ( isset( $options['redis'], $options['redis']['host'] ) ) {
				$port            = isset( $options['redis']['port'] ) ? $options['redis']['port'] : 6379;
				$redisConnection = RedisAdapter::createConnection( 'redis://' . $options['redis']['host'] . ':' . $port );
				$adapter         = new RedisAdapter( $redisConnection, $this->namespace, $defaultLifetime );
			}
		}

		// Convert Cache adapter to Tag aware adapter
		$this->adapter = new TagAwareAdapter($adapter);

		// Process cache cleaning parameters
		$this->processCleanParameters();

	}

	/**
	 * @return TagAwareAdapter
	 */
	public function getAdapter() {
		return $this->adapter;
	}

	/**
	 * Reads cache query parameters for cleaning the cache. Read the cache section in the documentation to understand
	 * the behaviour.
	 */
	private function processCleanParameters() {
		if ( isset( $_GET['cacheInvalidate'] ) ) {
			switch ( $_GET['cacheInvalidate'] ) {
				case 'all':
					// Invalidates all caches of the currently requested entity
					$this->invalidateAll();
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
					if ($this->entityType === 'template') {
						$this->invalidateTemplateApps();
					}
					break;
				case 'templates':
					// Invalidates all caches of all sub-templates of the currently requested template or version
					if ($this->entityType === 'template') {
						$this->invalidateTemplateSubtemplates();
					}
					/*if ($this->entityType === 'version') {
						$this->invalidateVersionTemplates();
					}*/
					break;
			}
		}
	}

	/**
	 * Invalidates all caches of the currently requested entity
	 */
	private function invalidateAll() {
		$cache = $this->getAdapter();
		$tags = [
			$this->entityType . '.' . $this->entityId
		];
		$cache->invalidateTags($tags);
	}

	/**
	 * Invalidates the config cache of the currently requested entity and language
	 */
	private function invalidateConfigs() {
		$cache = $this->getAdapter();
		$tags = [
			$this->entityType . '.' . $this->entityId . '.configs',
		];
		$cache->invalidateTags($tags);
	}

	/**
	 * Invalidates the basic information of the currently requested entity and language
	 */
	private function invalidateInfos() {
		$cache = $this->getAdapter();
		$tags = [
			$this->entityType . '.' . $this->entityId . '.infos',
		];
		$cache->invalidateTags($tags);
	}

	/**
	 * Invalidates the languages cache of the currently requested entity
	 */
	private function invalidateLanguages() {
		$cache = $this->getAdapter();
		$tags = [
			$this->entityType . '.' . $this->entityId . '.languages',
		];
		$cache->invalidateTags($tags);
	}

	/**
	 * Invalidates the translations cache of the currently requested entity and language
	 */
	private function invalidateTranslations() {
		$cache = $this->getAdapter();
		$tags = [
			$this->entityType . '.' . $this->entityId . '.translations',
		];
		$cache->invalidateTags($tags);
	}

	/**
	 * Invalidates all caches of all apps of the currently requested template
	 */
	private function invalidateTemplateApps(  ) {
		$cache = $this->getAdapter();
		$tags = [
			'appTemplate.' . $this->entityId,
		];
		$cache->invalidateTags($tags);
	}

	/**
	 * Invalidates all caches of all sub-templates of the currently requested template
	 */
	private function invalidateTemplateSubtemplates(  ) {
		$cache = $this->getAdapter();
		$tags = [
			'parentTemplate.' . $this->entityId,
		];
		$cache->invalidateTags($tags);
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
