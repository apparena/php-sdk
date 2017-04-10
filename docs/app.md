# The app object

You can use the app object to retrieve config-values, translations and
basic information about an app. The PHP SDK tries to get the current app
ID from a REQUEST-Parameter or a previous set cookie.

To get the app-object call

```php
// In your index.php
$app = $am->getApp();
$appId = $app->getId();
$appConfigs = $app->getConfigs();
$appTranslations = $app->getTranslations();
$appInfo = $appInfos();
```

## Methods

| Method                                          | Description                                                                           | Parameters                                                                                                                                                                | Response                                                            |
|:------------------------------------------------|:--------------------------------------------------------------------------------------|:--------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:--------------------------------------------------------------------|
| getConfig($config_id, $attr = “value”)          | Returns the value of a config value                                                   | String $config_id Config identifier to get the data for String                                                                                                            | string array $attr Attribute or Attributes which should be returned |
| getConfigs()                                    | Returns all Config Elements of the current instance as array                          |                                                                                                                                                                           | array                                                               |
| getId()                                         | Returns the currently used App ID                                                     |                                                                                                                                                                           | int                                                                 |
| getInfo($attr)                                  | Returns an attribute of the instance                                                  | String $attr Attribute you want to return                                                                                                                                 | string                                                              |
| getInfos()                                      | Returns all basic information of the current instance                                 |                                                                                                                                                                           | array                                                               |
| getLanguages()                                  | Returns all available and activated languages of the app                              |                                                                                                                                                                           | array                                                               |
| getTranslation($translation_id, $args = array() | Returns the translation for the submitted ID                                          | String $translation_id Config identifier to get the data Array $args Array of values to replace in the translation (@see http://php.net/manual/de/function.vsprintf.php ) | string                                                              |
| getTranslations()                               | Returns all translations for the currently set language                               |                                                                                                                                                                           | array                                                               |
| isAdmin($projectSecret)                         | Returns if the current request contains admin authentication information (GET-params) | String $projectSecret The project secret to validate the Hash                                                                                                             | bool                                                                |
