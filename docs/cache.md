# Cache

The PHP SDK uses the
[Symfony Cache component](http://symfony.com/doc/current/components/cache.html)
to cache API requests. To make it easy for you to start you can use one
of our preconfigured cache configurations or pass your own PHP-Cache
compliant adapter.

## Configuration options

| Parameter name | Description                                                                                                                                                        |
|:---------------|:-------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| adapter        | Pass your own Cache Adapter object. See http://symfony.com/doc/current/components/cache/cache_pools.html                                                           |
| directory      | The main cache directory (the application needs read-write permissions on it). If none is specified, a directory is created inside the system temporary directory. |
| redis          | Redis cache server configuration                                                                                                                                   |


```php
// Initialize the App-Manager using custom cache settings
$am = new \AppArena\AppManager([
    'versionId' => 123, // Add the version ID of your project here
    'cache' => [
        //'adapter' => new Symfony\Component\Cache\Adapter\PdoAdapter('yourdsn'),
        'dir' => __DIR__ . '/var/cache', // Default file cache directory
        'redis' => [
            'host' => 'localhost',
            'port' => 6379
        ]
    ], // Writable folder for file cache. Check the cache section for more options
    'apikey' => 'ABCDEFGHIJKLMNOPQRSTUVW' // Add you API key here
]);
```

## Invalidating the cache

To invalidate the cache you can use a number of GET parameters:

| Query parameter | Valid for    | Options      | Description                                                                       |
|:----------------|:-------------|:-------------|:----------------------------------------------------------------------------------|
| cacheInvalidate | All entities | all          | Invalidates all caches of the currently requested entity                          |
| cacheInvalidate | All entities | channels     | Invalidates the channels cache of the currently requested entity and language     |
| cacheInvalidate | All entities | configs      | Invalidates the config cache of the currently requested entity and language       |
| cacheInvalidate | All entities | infos        | Invalidates the basic information of the currently requested entity and language  |
| cacheInvalidate | All entities | languages    | Invalidates the languages cache of the currently requested entity                 |
| cacheInvalidate | All entities | translations | Invalidates the translations cache of the currently requested entity and language |
| cacheInvalidate | Templates    | apps         | Invalidates all caches of all apps of the currently requested template            |
| cacheInvalidate | Templates    | templates    | Invalidates all caches of all sub-templates of the currently requested template   |

**Examples:**

1. To reset all config caches for the french language of template with
   ID 1234, you have to call your templates Base Url including these
   parameters:
   `https://www.templateBaseUrl.com/?templateId=1234&lang=fr_FR&cacheInvalidate=config`
2. To reset all caches of all apps created from template 1234, you have
   to call this Url:
   `https://www.templateBaseUrl.com/?templateId=1234&cacheInvalidate=apps`
