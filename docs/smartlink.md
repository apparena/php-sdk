# PHP-SDK - SmartLink

To setup a SmartLink, which is responsible for all your redirects, copy
the smartlink.template.php file to your root folder and rename it to
smartlink.template.php. As you can see in the file you can customize
Meta-Data for sharing by calling setMetaData(..). Additional Parameters
which will be passed to your app as GET-Parameters can be add via
addParams(..) or by just adding your parameters as GET parameter to the
“Smart-Link”-Url you will get, when calling getUrl().

The users device will be detected, when the user is being redirected to
your app target. So mobile and tablet devices will never be redirected
to a facebook page tab, as it is not possible for them to display them
(No Support from Facebook).

## GET Parameters

The Smart-Link technology manages redirects for app users depending on
their device, language and environment settings. The Smart-Link should
be used for all sharing functionality in your app. It offers an easy way
to generate sharing links and some GET-Parameters to modify the
Redirect-Behaviour.

Note

You can add all of the listed parameters to the SmartLink Url to modify
the Redirect behaviour.

| Parameter | Description                                                                                                                                                                                                                                        | Example                             |
|:----------|:---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:------------------------------------|
| device    | To simulate a different device type (mobile, tablet or desktop), just add a device-GET Parameter to your URL. The SmartLink will automatically use this device type then and respond with it. Allowed values are `mobile`, `tablet` and `desktop`. | Simulate the mobile view of an app: https://www.my-web-app.com/?i_id=1234&device=mobile |
| website | If your app is being embedded in a website via iframe, you should add a GET-Parameter called website containing the URL the app is embedded in to your smartlink.php Url. Your users will then be redirected the Website Url and not directly to your app. This will keep traffic up on your website. :-) | Redirect the user to a certain website the iframe with your app is embedded in e.g. https://www.app-arena.com/fotowettbewerb.html The SmartLink is: |
| fb_page_id | Submit this parameter to redirect to this Facebook fanpage Tab your app is embedded in. You can use this to have the same app installed on several fanpages and to control the redirects
| ref_app_env | Set this parameter to fb to force the redirection to the facebook fanpage the app is installed on. | https://www.my-app.com/?i_id=1234&ref_app_env=fb |
| lang | The language parameter controls the used language of the app | Show your app in french: https://www.my-web-app.com/?i_id=1234&lang=fr_FR
| debug | Add the debug parameter to disable redirects and show Debug info on the smartlink.php page | Show the Debug-Page for the SmartLink: https://www.my-web-app.com/smartlink.php?debug=1

## Embed an App via iframe ($_GET[‘website’])

>**Warning**
>
>Safari is blocking third-party cookies within iframes! You need to
>assure that users visiting your app will be redirected via SmartLink to
>your application, so the SmartLink can set a cookie as first-party.
>Within the iframe cookies from this domain will be allowed then. DO NOT
>link directly to the page, your app is embedded in.

Always link to the SmartLink redirecting to the page your app is
embedded in. If your app is being embedded in a website via iframe, you
should add a GET-Parameter called website containing the URL the app is
embedded in to your smartlink.php Url. Your users will then be
redirected the Website Url and not directly to your app. This will keep
traffic up on your website. :-)

Use the website-GET Parameter for your iframe-Source is the easiest way
to keep your visitors on the website. The App-Manager SDK automatically
detects website-GET Parameters and set them to a cookie.

Here is an example to embed a photocontest-app to the website
https://www.app-arena.com/fotowettbewerb.html

```html
<iframe src="https://stage.fotowettbewerb.co/?i_id=9713&website=https%3A%2F%2Fwww.app-arena.com%2Ffotowettbewerb.html"
width="100%" height="1200" frameBorder="0"></iframe>
```

## Easy parameter passthrough
The SmartLink Technology makes it easy to pass parameters to your application no matter if the application is embedded via iframe or into a Facebook Page-Tab. All GET Parameters passed to your smartlink.php file will be written to a cookie (Cookie-key aa_1234_smartlink, 1234 is the instance id of your application). When you initialize the app manager object in your application again, then all parameters will be deleted from the cookie and written to the GET parameter again.

So you don’t have to care about that... Pass GET parameters to your
smartlink.php file and expect them in your app target file. :-)
