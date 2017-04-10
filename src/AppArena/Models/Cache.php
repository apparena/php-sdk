<?php

namespace AppArena\Models;

use Cache\Namespaced\NamespacedCachePool;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class Cache {

	private $namespace = '';

	/** @var AbstractAdapter */
	private $adapter;

	/**
	 * Initialization of the Caching object
	 *
	 * @param array $options Cache options
	 */
	public function __construct( $options ) {
		$namespace       = $options['namespace'];
		$defaultLifetime = 0;
		$directory       = null; // the main cache directory (the application needs read-write permissions on it). if none is specified, a directory is created inside the system temporary directory
		if (isset($options['directory'])) {
			if (!file_exists($options['directory'])) {
				mkdir($options['directory'], 0755, true);
			}
			if (!is_writeable($options['directory'])) {
				throw new \Exception($options['directory'] . ' is not writeable for the webserver.');
			}
			$directory = $options['directory'];
		}

		if ( isset( $options['adapter'] ) && $options['adapter'] instanceof AbstractAdapter ) {
			$this->adapter = $options['adapter'];
		} else {

			// Initialize the file cache as fallback or primary option @see http://symfony.com/doc/current/components/cache/cache_pools.html
			$adapter = new FilesystemAdapter( $namespace, $defaultLifetime, $directory );

			// Check if redis configuration is available
			if ( isset( $options['redis'], $options['redis']['host'] ) ) {
				$port            = isset( $options['redis']['port'] ) ? $options['redis']['port'] : 6379;
				$redisConnection = RedisAdapter::createConnection( 'redis://' . $options['redis']['host'] . ':' . $port );
				$adapter         = new RedisAdapter( $redisConnection, $namespace, $defaultLifetime );
			}

			$this->adapter = $adapter;
		}
		
		// Process cache cleaning parameters
		$this->processCleanParameters();

	}

	/**
	 * @return AbstractAdapter
	 */
	public function getAdapter() {
		return $this->adapter;
	}

	/**
	 * Reads cache query parameters for cleaning the cache. Read the cache section in the documentation to understand
	 * the behaviour.
	 */
	private function processCleanParameters( ) {

	}

}
