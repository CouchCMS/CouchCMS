Requirements
------------
Please see http://www.couchcms.com/docs/requirements.html


Installation
------------
Please see http://www.couchcms.com/docs/tutorials/portfolio-site/building-a-real-world-site.html


Rebranding
----------
Please see http://www.couchcms.com/white-label.html


Localizing the Admin Panel
--------------------------
The default locale of Couch's admin panel is English.
It can be localized to German, French, Spanish, or Dutch by changing the following line in `couch/config.php` to `DE`, `FR`, `ES`, or `NL` respectively.

```PHP
define( 'K_ADMIN_LANG', 'EN' );
```

All these locales have their respective language files (`DE.php`, `FR.php`, etc&hellip;) present in the `couch/lang/` folder.
For locales other than these, any of these files can be renamed and modified to create the locale that suits you.


Localizing CKEditor
-------------------
CKEditor (used with `richtext` type editable regions) can also be localized to German, French, Spanish, or Dutch by changing the following line in `couch/includes/ckeditor/config.js` to `de`, `fr`, `es`, or `nl` respectively.

```JavaScript
config.language = 'en';
```

All these locales have their respective language files (`de.js`, `fr.js`, etc&hellip;) present in the `couch/includes/ckeditor/lang/` folder.
For locales other than these, you can download the corresponding language file from http://www.couchcms.com/ckeditor_431_lang.zip
