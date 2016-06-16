2.0 - (2016/m/d)
------------------

...


1.4.7 - (2015/11/24)
------------------

- Remove attribution link
- Fix security issues
  * http://seclists.org/fulldisclosure/2015/Dec/110
  * http://seclists.org/fulldisclosure/2015/Dec/111


1.4.5 - (2015/07/07)
------------------

- Added Extended Entities module
- Added events and hooks
- Added Custom Routes module introducing several new tags:
  * `route`, `route_constraints`, `route_validators`, `match_route`, and `route_link`

- Added three new tags:
  * `else_if`, `is_ajax`, and `validate`

- Added `searchable` parameter for editable regions
- Added `link_only` parameter for `gravatar` tag
- Added `html` parameter for `send_mail` tag
- Made `pages` tag relation-aware
- Updated Google Maps API to v3
- Added transliteration of titles
- Added prompt for unsaved changes in admin panel
- Improved HTTPS support
- Dropped support for PHP 4
- Fixed miscellaneous bugs


1.4 - (2013/10/15)
------------------

- Added On Page Editing module
- Added support for KCFinder as a new file manager
- Added Front-end submission module introducing several new tags:
  * `db_persist_form`, `db_persist`, `db_delete`, `db_begin_trans`, `db_commit_trans`, `db_rollback_trans`, `db_fields`, `create_nonce`, `validate_nonce`, `check_nonce`, `check_spam`, and `show_securefile`

- Added two new editable region types:
  * `securefile` and `datetime`

- Added three new form input types:
  * `bound`, `datetime`, and `throttle`

- Added custom admin screens functionality that allows executing regular Couch tags in the admin-panel
- Beefed-up the security of all editable regions in view of their possible use from the front-end
- Modified IPN handler in accordance to the new directives issued by PayPal
- Added Dutch translation
- Upgraded CKEditor to version 4.1.3


1.3.5 - (2013/05/16)
--------------------

- Open source


1.3.5 - (2013/02/10)
--------------------

- Added Shopping Cart module
- Added Session module - makes available the following tags:
  * `set_session`, `get_session`, `delete_session`, `set_flash`, and `get_flash`

- Added `query` tag to execute raw SQL `SELECT` statements
- Added `no_results` tag
  * Works when the `pages`, `comments`, `search`, `related_pages`, `reverse_related_pages`, and `query` tags don't find any records to show

- Added `addslashes` tag for use within `php` tag
  * Helps when setting a php variable using Couch's `show` tag

- Added `not` tag (for use with `if`)


1.3 - (2012/06/22)
------------------

- Added Repeatable Regions
- Added Relationships
- Added NicEdit editable region
- Added `thumbnail` tag


1.2.5 - (2012/02/22)
--------------------

- Added Image Gallery module


1.2 - (2011/12/16)
------------------

- Fixed all reported bugs since version 1.1.1
- Added the ability to create Shortcodes (http://docs.couchcms.com/miscellaneous/shortcodes.html)
  * This is the first of several steps meant to open up Couch's architecture to allow extenting it with external code

- Added support for Nested Pages (http://docs.couchcms.com/concepts/nested-pages-aka-menu-maker.html)
  * This feature can also double up as a menu-maker

- Added `random` as an acceptable value for the `orderby` parameter of the `pages` tag
- Added a parameter to the `excerpt` tag that makes it truncate the input based on characters instead of whole words


1.1.1 - (2011/05/30)
--------------------

- Added the ability to create drafts of already published pages
  * The end-user can now make changes to live pages without the fear of messing up anything or his incomplete changes being visible to the world

- Added support for dynamic folders
  * The `template` tag now has a new parameter named `dynamic_folders` that can be set to `1` to allow end users the ability to create and manage folders through the admin panel

- Made several changes that will now make migration of a Couch-managed site to a different machine easier (e.g. from local test machine to production server)
  * Items uploaded through the `image`, `thumbnail`, and `file` type of editable regions are no longer stored with the domain names in their paths
  * Added an import utility script - `gen_dump.php`
    - This may be used to create a dump file that will be applied automatically during installation (see documentation for details)

- Enhanced security by now allowing the `index.php` file within the `couch` folder to be renamed
  * This will allow only users knowing the new file name to access the admin panel

- Fixed the error that occurred when the `send_mail` tag was used more than once in a template
- Fixed the bug where the parser was stripping off all backslash characters that had a single quote following them
- Fixed the bug that was causing the captcha in forms to not appear
- Increased the length of the field storing names of templates to allow names up to 256 characters in length
- Removed the `options` directives from `.htaccess` files
  * This was causing errors on GoDaddy servers

- Fixed the bug occurring on some installations of WampServer that truncated the `ckeditor.js` file, causing the editor not to show up
  * Now using PHP to deliver the JavaScript file

- Upgraded CKEditor to version 3.5.4
  * Language files may be downloaded from http://www.couchcms.com/ckeditor_354_lang.zip

- Added option in `config.php` to set the location of the default folder to store snippets used by the `embed` tag


1.0.1 - (2011/01/11)
--------------------

- Added rebranding feature to CouchCMS
  * Users with a commercial license can replace Couch's name and logos with that of their own business
  * Added lines to `config.example.php` that can be changed by users to utilize their own information

- Added localization to the admin panel
  * Strings used in the admin panel have been moved to a separate file stored in the `lang` folder
    - Added three locales: `EN` (default), `DE`, and `FR`
    - Users can create their own language files

- CKEditor
  * Fixed the bug where CKEditor did not appear on non-English locales
  * Upgraded CKEditor to version 3.5
  * Added CKEditor `DE` and `FR` language files
    - The rest can be downloaded from http://www.couchcms.com/ckeditor_lang.zip

  * Added `spellchecker` as a valid toolbar button that can be used with the `custom_toolbar` parameter of `editable` tags of type `richtext`
  * Added the provision of creating toolbar buttons not natively supported by the `richtext` type by prefixing their names with a `#` character
    - This can be used to create, for example, buttons like Scayt, BidiRtl, BidiLtr, etc&hellip;:
    ```
custom_toolbar='cut,copy,paste,pastetext,-,spellchecker,#Scayt,#BidiRtl'
```

    - Note that these names are case-sensitive and should be used exactly as given in the CKEditor documentation
    - These buttons are not supported by Couch and might not work properly
    - They also may require further tweaks to the `couch/includes/ckeditor/config.js` file

- Fixed some warnings that appeared in `PasswordHash.php` on hosts with paranoid-level security
  * This was happening on hosts with functions `getmypid` and `is_readable` disabled

- Added `enforce_max` parameter to `editable` tags of type `thumbnail`
  * This will allow the created thumbnails to be simply scaled instead of always being cropped

- Modified the `start_on` parameter of the `pages` tag to use `>=` instead of `>`
  * This will cause the tag to fetch pages that match the exact time given in `start_on` also

- Added `return_url`, `cancel_url`, and `custom` parameters to the `paypal_button` tag
- Added `cc` and `bcc` parameters to the `send_mail` tag


1.0.0 - (2010/12/05)
--------------------

- First beta release
