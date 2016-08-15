<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    // 0.
    // Set the following to 1 to put your site in maintenance mode.
    // In this mode only admins will be able to access the site while the visitors will be
    // shown the 'Site undergoing maintenance' message.
    define( 'K_SITE_OFFLINE', 0 );

    // 1.
    // If necessary, define the full URL of your site including the subdomain, if any.
    // V.IMP: Don't forget the trailing slash!
    //define( 'K_SITE_URL', 'http://www.test.com/subfolder1/subfolder2/mysite/' );

    // 1b.
    // For security purpose, the 'index.php' file of Couch can be renamed to anything else.
    // If you do so, uncomment the following line and enter the new name.
    //define( 'K_ADMIN_PAGE', 'index.php' );

    // 2.
    // Your Time Zone
    // Example values (note how :15, :30, :45 will be entered as .25, .5 and .75 respectively):
    // +12.75 New Zealand (UTC+12:45)
    // +8.75  Australia (UTC+08:45)
    // +5.5   India (UTC+05:30)
    // +1     Germany (UTC+01:00)
    // 0      United Kingdom (UTC±0)
    // -2     Brazil (UTC-02:00)
    // -4.5   Venezuela (UTC-04:30)
    // -6     United States (Central Time) (UTC-06:00)
    // -8     United States (Pacific Time) (UTC-08:00)
    define( 'K_GMT_OFFSET', +5.5 );

    // 3.
    // Define the charset used by your site. If in any doubt, leave the default utf-8.
    define( 'K_CHARSET', 'utf-8' );

    // MySQL settings. You need to get this info from your web host.
    // 4.
    // Name of the database
    define( 'K_DB_NAME', 'database_name' );
    // 5.
    // Database username
    define( 'K_DB_USER', 'database_username' );
    // 6.
    // Database password
    define( 'K_DB_PASSWORD', 'database_password' );
    // 7.
    // MySQL hostname (it will usually be 'localhost')
    define( 'K_DB_HOST', 'localhost' );
    // 7b.
    // Needed only if multiple instances of this CMS are to be installed in the same database
    // (please use only alphanumeric characters or underscore (NO hyphen))
    define( 'K_DB_TABLES_PREFIX', '' );

    // 8.
    // Set the following to '1' if you wish to enable Pretty URLS.
    // After enabling it, use gen_htaccess.php to generate an .htaccess file and place it in the root folder of your site.
    define( 'K_PRETTY_URLS', 0 );

    // 9.
    // If set, CMS will cache generated pages and serve them if possible.
    define( 'K_USE_CACHE', 0 );

    // 10.
    // When the cache is invalidated (by adding, deleting or modifying pages in admin),
    // existing files in cache become useless but are not deleted immediately.
    // A purge routine gets executed at interval set here (in hours)
    // during which this deletion of stale files occurs.
    define( 'K_CACHE_PURGE_INTERVAL', 24 );

    // 11.
    // Even if the cache does not become invalidated, as noted above, files in cache
    // are removed after this interval (set in hours).
    define( 'K_MAX_CACHE_AGE', 7 * 24 ); // Default is 7 days

    // 12.
    // Upload folder if not using the default upload folder within 'couch'.
    // Should be relative to your site (don't forget to set write permissions on it).
    // No leading or trailing slashes please.
    //define( 'K_UPLOAD_DIR', 'myuploads' );

    // 12b.
    // Folder containing the embedded snippets if not using the default 'snippets' folder within 'couch'.
    // Should be relative to your site. No leading or trailing slashes please.
    //define( 'K_SNIPPETS_DIR', 'mysnippets' );

    // 13.
    // Your Email address. Will be used in contact forms.
    define( 'K_EMAIL_TO', 'youremail@gmail.com' );

    // 14.
    // Will be used as the sender of messages delivered by contact forms to the address above.
    define( 'K_EMAIL_FROM', 'contact@yourdomain.com' );

    // 15.
    // By default the inbuilt php function 'mail()' is used to deliver messages.
    // On certain hosts this function might fail due to configuration problems.
    // In such cases, set the following to '1' to use an alternative method to send emails
    define( 'K_USE_ALTERNATIVE_MTA', 0 );

    // 16.
    // Google Maps API Key.
    // You'll have to get one for your site from 'http://code.google.com/apis/maps/'
    // if your site makes use of Google maps.
    define( 'K_GOOGLE_KEY', 'ABQIAAAAD7z_FToS5NSqosnG9No1ABQYPrehWcZJH1ec0SZqipYFbK_nfRT1ryCGKzl5KGpFG3y5jyPe_uClVg' );

    // Set the following if you use PayPal buttons to sell products.
    // 17.
    // Set this to zero once you are ready to go live
    define( 'K_PAYPAL_USE_SANDBOX', 1 );
    // 18.
    // Email address of your PayPal 'business' account selling the item
    define( 'K_PAYPAL_EMAIL', 'seller_1272492192_biz@gmail.com' );
    // 19.
    // A three letter code for the currency you do your business in.
    // Some valid values are: AUD (Australian Dollar), CAD (Canadian Dollar), EUR (Euro),
    // GBP (Pound Sterling), JPY (Japanese Yen) and USD (U.S. Dollar).
    // Please check PayPal to find yours.
    define( 'K_PAYPAL_CURRENCY', 'USD' );

    // 20.
    // A setting of '1' will necessitate the admin to approve comments before they get published.
    // '0' will publish comments immediately.
    // A setting of '1' is strongly recommended in order to avoid spam.
    define( 'K_COMMENTS_REQUIRE_APPROVAL', 1 );

    // 21.
    // Minimum time interval required between two comments posted by the same user (in seconds).
    // Prevents comment flooding. A setting of 5 minutes (300 seconds) is recommended.
    define( 'K_COMMENTS_INTERVAL', 5 * 60 );

    // 22.
    // Language used for localization of admin panel. Needs to have a corresponding language file in couch folder.
    // Change to 'DE' for German or 'FR' for French.
    define( 'K_ADMIN_LANG', 'EN' );

    // 23.
    // Uncomment the following line if you wish to format self-closing HTML tags the old way e.g. as <br> instead of <br/>
    //define( 'K_HTML4_SELFCLOSING_TAGS', 1 );

    // 24.
    // Set the following to '1' if you wish to extract EXIF data from images uploaded to Gallery
    define( 'K_EXTRACT_EXIF_DATA', 0 );

    // 25.
    // Set the following to '1' if you wish to use KCFinder as the default file-browser (will require PHP5 and modern browsers)
    define( 'K_USE_KC_FINDER', 1 );

    // 26.
    // If the admin-panel uses a custom theme, set the following to the folder-name of the theme.
    // Theme folder is expected to be within the 'couch/theme' folder. No leading or trailing slashes please.
    //define( 'K_ADMIN_THEME', 'sample' );

    // 27.
    // Google reCAPTCHA API Keys.
    // To use this captcha service, you need to sign up for an API key pair for your site.
    // Please visit 'https://www.google.com/recaptcha/admin' to get the keys and enter them below.
    define( 'K_RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI' );
    define( 'K_RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe' );

    // 99.
    // VERY IMPORTANT!
    // Set the following to '1' ONLY IF YOU HAVE BOUGHT A COMMERCIAL LICENSE for the site you are using this file on.
    // Doing so otherwise is NOT PERMITTED and will constitute a violation of the CPAL license this software is provided under.
    define( 'K_PAID_LICENSE', 0 );

        // Rebranding. Uncomment the following defines and add your info.
        // 99a. Company Logo on light background  (Multiple of [<= 450] x 57 pixels. Needs to be placed within 'couch/theme/images/' folder)
        //define( 'K_LOGO_LIGHT', 'couch_light.png' );

        // 99b. Company Logo on dark background  (Multiple of [<= 219] x 68 pixels. Needs to be placed within 'couch/theme/images/' folder)
        //define( 'K_LOGO_DARK', 'couch_dark.png' );

        // 99c. Footer content (Company name and link)
        //define( 'K_ADMIN_FOOTER', '<a href="http://www.yourcompany.com">COMPANY NAME</a>' );
