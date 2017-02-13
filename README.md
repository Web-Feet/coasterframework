<p align="center"><img src="https://www.coastercms.org/uploads/images/logo_coaster_github3.jpg"></p>
This is the codebase for Coaster CMS - all the inner workings are here and it is designed to work in conjunction with the Coaster CMS framework (https://github.com/Web-Feet/coastercms).

You can also use this as a stand alone to add CMS functionality to your project.

## Installing or adding to an existing Laravel project

The steps are are as follows:

1. Add "web-feet/coasterframework": "5.3.*" to the composer.json file and run composer update
2. Go to the root directory of your project.
3. Run the script <code>php vendor/web-feet/coasterframework/updateAssets</code>
4. Add the service provider CoasterCms\CmsServiceProvider::class to your config/app.php file.
5. Go to a web browser and follow the install script that should have appeared
6. Upload or create a theme
