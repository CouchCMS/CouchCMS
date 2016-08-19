Requirements
------------
Please see http://docs.couchcms.com/requirements.html


Installation
------------
Please see http://docs.couchcms.com/tutorials/portfolio-site/building-a-real-world-site.html


Rebranding
----------
Please see http://docs.couchcms.com/miscellaneous/white-label.html


Localizing the Admin Panel
--------------------------
The default locale of Couch's admin panel is English.
It can be localized to German, French, Spanish, or Dutch by changing the following line in `couch/config.php` to `DE`, `FR`, `ES`, or `NL` respectively.

```PHP
define( 'K_ADMIN_LANG', 'EN' );
```

All these locales have their respective language files (`DE.php`, `FR.php`, etc...) present in the `couch/lang/` folder.
For locales other than these, any of these files can be renamed and modified to create the locale that suits you.
Please see https://github.com/CouchCMS/Translations for a collection of additional community-contributed language files.


Localizing CKEditor
-------------------
CKEditor (used with `richtext` type editable regions) can also be localized to German, French, Spanish, or Dutch by changing the following line in `couch/includes/ckeditor/config.js` to `de`, `fr`, `es`, or `nl` respectively.

```JavaScript
config.language = 'en';
```

All these locales have their respective language files (`de.js`, `fr.js`, etc...) present in the `couch/includes/ckeditor/lang/` folder.
For locales other than these, you can download the corresponding language file from http://www.couchcms.com/ckeditor_459_lang.zip
