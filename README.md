# php-sdk
App-Manager PHP SDK

Read the documentation on http://app-arena.readthedocs.org/


## Usage

Use the Composer Autoloader to start using the App-Manager


```php
define("ROOT_PATH", realpath(dirname(__FILE__)) . "../../../../");
require ROOT_PATH . '/vendor/autoload.php';

$m_id = 123; // Set your app-arena Model ID here

// Clear the cache before, requestion Instance in for each
$am = new \AppManager\AppManager(
    $m_id, 
    array(
        "cache_dir" => ROOT_PATH . "/var/cache"
    )
);
// Get all necessary instance information to start working
$config = $am->getConfigs();
$translation = $am->getTranslations();
$info = $am->getInfos();
```

Now the connection is build up and you can start using you App-Instance. The App-Manager SDK automatically tries to get 
your Instance ID (i_id) from GET-Parameters or from Cookies. So your Url should be something like this:

```
http://www.domainofmyapp.com/mypage.php?i_id=1234
```

This will use Instance 1234 to setup your connection. Now these functions are available:

| Method                          | Description                                                                                                                                                                                                                                                                   | Parameters                                                         | Response |
|---------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------|----------|
| getBaseUrl()                    | Returns the BaseUrl your Sharing Url is generated with. By default it will use the currently used domain                                                                                                                                                                      |                                                                    | (string) |
| getBrowser()                    | Returns user browser information                                                                                                                                                                                                                                              |                                                                    | (array)  |
| getConfigs()                    | Returns all Config Elements of the current instance as array                                                                                                                                                                                                                  |                                                                    | (array)  |
| getDevice()                     | Returns user device information                                                                                                                                                                                                                                               |                                                                    | (array)  |
| getDeviceType()                 | Returns the device type of the current device 'mobile', 'tablet', 'desktop'                                                                                                                                                                                                   |                                                                    | (string) |
| getEnvironment()                | Returns if the app currently running on a 'website', 'facebook' or 'direct' 'website' means the app is embedded via iframe to a website 'facebook' means the app is embedded in a facebook page tab 'direct' means the app is being accessed directly without iframe embed    |                                                                    | (string) |
| getIId()                        | Returns the currently used Instance ID                                                                                                                                                                                                                                        |                                                                    | (int)    |
| getInfos()                      | Returns all basic information of the current instance                                                                                                                                                                                                                         |                                                                    | (array)  |
| getLang()                       | Returns the currently used Language as Language Code (e.g. de_DE, en_US, ...)                                                                                                                                                                                                 |                                                                    | (string) |
| getMId()                        | Returns the model ID of the currently selected instance                                                                                                                                                                                                                       |                                                                    | (int)    |
| getTranslations()               | Returns all translations of the current instance and language                                                                                                                                                                                                                 |                                                                    |          |
| getUrl()                        | Returns the SmartLink Url for Sharing                                                                                                                                                                                                                                         |                                                                    | (string) |
| getInfos()                      | Returns all basic information of the current instance                                                                                                                                                                                                                         |                                                                    | (array)  |
| getLang()                       | Returns the currently used Language as Language Code (e.g. de_DE, en_US, ...)                                                                                                                                                                                                 |                                                                    | (string) |
| renderSharePage($debug = false) | Renders the complete HTML of the Share page including all meta tags and redirection.                                                                                                                                                                                          | (bool) $debug - Show debug information on the page?                | (string) |
| setBaseUrl($base_url)           | Sets a new base url for your sharing links (-->getUrl()).                                                                                                                                                                                                                     | (string) $base_url New base url                                    | void     |
| setLang($lang)                  | Sets a new language for the current instance                                                                                                                                                                                                                                  | (string) $lang 5 char  Language Code, e.g. de_DE                   |          |
| setMeta($meta)                  | Sets the meta data for SmartLink Share page. All key value pairs will be generated as meta information into the head of the share page. The array keys 'title', 'desc', 'image' are the most important. The array values can be Strings or config identifiers of the instance | (array) $meta (see description)                                    | (array)  |
| setParams($params)              | This will add parameters to the smartLink Url. These parameters will be available as GET-Parameters, when a user* clicks on the smartLink. The parameters will be available as GET parameters as well in the facebook page tab* or within an iframe                           | (array) $params Array of parameters which should be passed through |          |


## Smart-Link

To setup a SmartLink, which is responsible for all your redirects, copy the `smartlink.template.php` file to your root
folder and rename it to `smartlink.template.php`. As you can see in the file you can customize Meta-Data for sharing 
by calling `setMetaData(..)`. Additional Parameters which will be passed to your app as GET-Parameters can be add via
 `addParams(..)` or by just adding your parameters as GET parameter to the "Smart-Link"-Url you will get, when calling
  `getUrl()`.  
  
  
### Device detection

The users device will be detected, when the user is being redirected to your app target. So mobile and tablet devices
will never be redirected to a facebook page tab, as it is not possible for them to display them (No Support from Facebook).


### Browser detection and the Safari Cookie Issue

As Safari is blocking third-party cookies within iframes, it is not possible to redirect Safari users to embedded apps.
Safari Users will be detected and automatically being redirected the direct app (unembedded).


### Website Embeds

If your app is being embedded in a website via iframe, you should add a GET-Parameter called `website` containing the URL
the app is embedded in to your smartlink.php Url. Your users will then be redirected the Website Url and not directly to
your app. Like this you can keep your traffic up on the website. :-)

Use the `website`-GET Parameter for your iframe-Source is the easiest way to keep your visitors on the website. The 
App-Manager SDK automatically detects `website`-GET Parameters and set them to a cookie.

### Easy parameter passthrough

The SmartLink Technology makes it easy to pass parameters to your application no matter if the application is embedded 
via iframe or into a Facebook Page-Tab. 
All GET Parameters passed to your `smartlink.php` file will be written to a cookie (Cookie-key `aa_1234_smartlink`, 1234
is the instance id of your application). When you initialize the app manager object in your application again, then all
parameters will be deleted from the cookie and written to the GET parameter again.

So you don't have to care about that... Pass GET parameters to your smartlink.php file and expect them in your app 
target file. :-)