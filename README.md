# Coaster CMS - coasterframework
This is the codebase for Coaster CMS - all the inner workings are here and it is designed to work in conjunction with the Coaster CMS framework (https://github.com/Web-Feet/coastercms).

You can also use this as a stand alone to add CMS functionality to your project.

## Installing or adding to an existing Laravel project

The steps are are as follows:

1. Add "web-feet/coasterframework": "5.2.*" to the composer.json file and run composer update
2. Go to the root directory of your project. 
3. Add the folders /coaster and /uploads to your public folder.
4. Run the script <code>php vendor/web-feet/coasterframework/updateAssets</code>
5. Add the service provider CoasterCms\CmsServiceProvider::class to your config/app.php file.
