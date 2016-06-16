Upgrading
=========

For upgrading from a previous version of CouchCMS to version 2.0:

1. Unzip `couchcms-2.0.zip` to your local system.
  * Within the `couchcms-2.0` folder thus extracted will be found a folder named `couch`.
  * From within this `couch` folder:
    1. Delete the `snippets` and `uploads` folders.
    2. Delete the `config.example.php` file.

2. Rename the `ckeditor` folder found in your *existing* Couch installation's `includes` folder to `ckeditor_old` (or delete it completely).

3. Upload the remaining contents of the `couch` folder of step 1 to your existing installation's `couch` folder (or whatever you might have renamed it to).
  * This way we'll be overwriting all existing Couch core files with the newer version, while preserving anything you might have added to the `snippets` and `uploads` folders.

4. If you already have a commercial license for your website, append the following line to your site's `couch/config.php` file:

```PHP
define( 'K_PAID_LICENSE', 1 );
```

--------------------------------

**IMPORTANT:** If you have chosen as a security measure to rename the `index.php` file to something else, do make sure to delete your existing renamed `index.php` and then rename the new `index.php` to your name of choice.
