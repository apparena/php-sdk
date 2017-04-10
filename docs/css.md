# CSS compilation

The PHP SDK provides easy to use methods to pre-compile **LESS**,
**SASS**, **SCSS** and pure **CSS** files. The compilation can be done
on the server via php packages [SCSS](https://github.com/leafo/scssphp)
and [LESS](https://github.com/oyejorge/less.php) or via API call for
faster compilation in the cloud.

The following functionality is available
- Compiles Less and SCSS files (including @imports), CSS Files and
  App-Manager config value
- Compiles Twitter Bootstrap SCSS
- Dynamically replace Variables with values you submit
- Searches and replaces strings

## CSS configuration

To request compiled CSS files, you need to tell the SDK, what files to include in your compiled files. Your "input"
is defined in an array:

```php
/**
 * This example config files will return 2 compiled css files: file1 and file2
 * array(
 *      files         -> Array list of files (using an absolute path) to include in the compilation
 *      config_values -> App-Manager config values of type CSS to include in the compilation
 *      variables     -> Less or Scss Variables which will be replaced in all files
 *      replacements  -> String replacements in all files (e.g. to fix a relative filepath')
 * )
 */
$css_config = array(
    'file1' => array(
        'files' => array(
            __DIR__ . '/css/less/bootstrap-custom.less',
            __DIR__ . '/css/scss/bootstrap-social.scss',
            __DIR__ . '/js/vendor_bower/font-awesome/css/font-awesome.min.css'
        ),
        'config_values' => array(),
        'variables' => array(
            'brand-primary' => $am->getConfig('color_primary'), // Use a config value to set a color
            'border-radius-base' => '0px',
            'border-radius-large' => '0px',
            'border-radius-small' => '0px',
        ),
        'replacements' => array(
            '../fonts/fontawesome' => '../../js/vendor_bower/font-awesome/fonts/fontawesome'
        ),
    ),
    'file2' => array(
        'files' => array(
            __DIR__ . '/css/style.css',
            __DIR__ . '/css/less/app.less',
        ),
        'config_values' => array( 'css_app', 'css_user' ), // A list of config value IDs to include in the CSS
        'variables' => array(
            'primary' => '#478AB8',
            'secondary' => '#2D343D',
            'highlight' => '#efefef',
        ),
        'replacements' => array()
    ),
);

$css_files = $am->getCssFiles($css_config);
```

Now you got an array containing two CSS file path's. To use them in your
app just print them in your HTML head section:

