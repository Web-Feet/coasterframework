<p align="center"><img src="https://www.coastercms.org/uploads/images/logo_coaster_github4.jpg"></p>

<p align="center">
  <a href="https://packagist.org/packages/web-feet/coasterframework"><img src="https://poser.pugx.org/web-feet/coasterframework/downloads.svg"></a>
  <a href="https://packagist.org/packages/web-feet/coasterframework"><img src="https://poser.pugx.org/web-feet/coasterframework/version.svg"></a>
  <a href="https://www.gnu.org/licenses/gpl-3.0.en.html"><img src="https://poser.pugx.org/web-feet/coasterframework/license.svg"></a>
</p>

This is the codebase for Coaster CMS - all the inner workings are here and it is designed to work in conjunction with the Coaster CMS framework (https://github.com/Web-Feet/coastercms).

You can also use this as a stand-alone library to add content management functionality to your project.

## Add to an Existing Laravel Project

The steps are are as follows:

1. Add "web-feet/coasterframework": "5.3.*" to the composer.json file and run composer update
2. Go to the root directory of your project.
3. Run the script <code>php vendor/web-feet/coasterframework/updateAssets</code>
4. Add the service provider CoasterCms\CmsServiceProvider::class to your config/app.php file.
5. Go to a web browser and follow the install script that should have appeared
6. Upload or create a theme
