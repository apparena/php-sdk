# App-Arena.com php-sdk

## Getting started

Use this SDK to setup the connection to your
[App-Manager Project](https://my.app-arena.com/projects).


## Installation

Install the latest version of the SDK using
[composer](https://getcomposer.org/).

```bash
composer require app-arena/php-sdk
```

Initialize the SDK within our index.php or similar:

```php
// In your index.php
require __DIR__ . '/vendor/autoload.php';

// Add App-Arena App-Manager
$am = new \AppArena\AppManager(
    array(
        'versionId' => 123, // Add the version ID of your project here
        'root_path' => __DIR__,
        'cache' => [
            'directory' => __DIR__ . '/var/cache', // Writable folder for file cache. Check the cache section for more options
        ],'apikey' => 'ABCDEFGHIJKLMNOPQRSTUVW' // Add you API key here
    )
);
// Get config values, languages, translations and infos from the current app, template or version
$configs      = $am->getConfigs();
$infos        = $am->getInfos();
$languages    = $am->getLanguages();
$translations = $am->getTranslations();

```

Now the connection is setup up and you can receive content from the
App-Manager. The App-Manager SDK automatically detects if you want to
display an app, template or version by the Query parameters your are
sending with your request:

| Query Parameter | Description                                                             | Example                                         |
|:----------------|:------------------------------------------------------------------------|:------------------------------------------------|
| appId           | Returns all configs, translations and infos from the submitted app      | `http://www.domainofmyapp.com/?appId=1234`      |
| templateId      | Returns all configs, translations and infos from the submitted template | `http://www.domainofmyapp.com/?templateId=1234` |
| versionId       | Returns all configs, translations and infos from the submitted verion   | `http://www.domainofmyapp.com/?versionId=1234`  |

> If you specify multiple of these query parameters, then `versionId` is
> more important than `templateId` is more important than `appId`.

## Core concepts

- [SmartLink](docs/smartlink.md): Intelligent user redirection based on
  the users device and settings
- [Cache](docs/cache.md): Activate on of the build in backend caches to
  speed up load time for your users

## Methods

| Method                           | Description                                                                                                                                                                                                                                                             | Parameters                                                                                            | Response                               |
|:---------------------------------|:------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:------------------------------------------------------------------------------------------------------|:---------------------------------------|
| addParams($params)               | This will add parameters to the smartLink Url. These parameters will be available as GET-Parameters, when a user* clicks on the smartLink. The parameters will be available as GET parameters as well in the facebook page tab* or within an iframe                     | `array` $params Array of parameters which should be passed through                                    |                                        |
| cacheInvalidate($action = 'all') | Invalidate the cache of a submitted entity. See parameter settings in [cache section](docs/cache.md)                                                                                                                                                                    | `String` $action Can be 'all', 'configs', 'infos', 'languages', 'translations', 'apps' or 'templates' |                                        |
| getBaseUrl()                     | Returns the BaseUrl your Sharing Url is generated with. By default it will use the currently used domain                                                                                                                                                                |                                                                                                       | string                                 |
| getBrowser()                     | Returns a browser object you can                                                                                                                                                                                                                                        |                                                                                                       | array                                  |
| getCssFiles($css_config)         | Returns CSS Helper object to compile and concatenate Less, CSS and Config-Type (CSS) values                                                                                                                                                                             | `array` $css_config Array to define the compilation process (@see CSS Config. )                       | array List of compiled CSS file path’s |
| getDevice()                      | Returns user device information                                                                                                                                                                                                                                         |                                                                                                       | array                                  |
| getDeviceType()                  | Returns the device type of the current device mobile, tablet, desktop                                                                                                                                                                                                   |                                                                                                       | string                                 |
| getEnvironment()                 | Returns if the app currently running on a website, facebook or direct website means the app is embedded via iframe to a website facebook means the app is embedded in a facebook page tab direct means the app is being accessed directly without iframe embed          |                                                                                                       | string                                 |
| getFacebookInfo()                | Returns all available Facebook information, like currently used fanpage and canvas information.                                                                                                                                                                         |                                                                                                       |                                        |
| getLang()                        | Returns the currently used Language as Language Code (e.g. de_DE, en_US, ...)                                                                                                                                                                                           |                                                                                                       | string                                 |
| getParams()                      | Returns all params submitted to the SmartLink before redirection                                                                                                                                                                                                        |                                                                                                       | array                                  |
| getOperatingSystem()             | Returns the operating system of the current user                                                                                                                                                                                                                        |                                                                                                       | string                                 |
| getUrl()                         | Returns the SmartLink Url for Sharing                                                                                                                                                                                                                                   |                                                                                                       | string                                 |
| getUrlLong()                     | Returns the SmartLink Url without Url Shortener                                                                                                                                                                                                                         | `bool` $shorten Shorten URL using smartl.ink                                                          | string                                 |
| renderSharePage ($debug = false) | Renders the complete HTML of the Share page including all meta tags and redirection.                                                                                                                                                                                    | `bool` $debug - Show debug information on the page?                                                   | string                                 |
| setBaseUrl($base_url)            | Sets a new base url for your sharing links (->getUrl()).                                                                                                                                                                                                                | `string` $base_url New base url                                                                       | void                                   |
| setFilename($filename)           | Sets the filename for the SmartLink (default: smartlink.php)                                                                                                                                                                                                            | `string` $filename                                                                                    | void                                   |
| setLang($lang)                   | Sets a new language for the current instance                                                                                                                                                                                                                            | `string` $lang 5 char Language Code,e .g. de_DE                                                       |                                        |
| setMeta($meta)                   | Sets the meta data for SmartLink Share page. All key value pairs will be generated as meta information into the head of the share page. The array keys title, desc, image are the most important. The array values can be Strings or config identifiers of the instance | `array` $meta (see description)                                                                       | array                                  |
| setParams($params)               | This will reset all parameters of the smartLink Url. These parameters will be available as GET-Parameters, when a user* clicks on the smartLink. The parameters will be available as GET parameters as well in the facebook page tab* or within an iframe               | `array` $params Array of parameters which should be passed through                                    |                                        |
