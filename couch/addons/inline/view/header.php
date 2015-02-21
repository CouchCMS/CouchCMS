<?php if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <script type="text/javascript">try { document.execCommand('BackgroundImageCache', false, true); } catch(e) {}</script>
    <script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/mootools-core-1.4.5.js'; ?>"></script>
    <script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/mootools-more-1.4.0.1.js'; ?>"></script>
    <script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/slimbox/slimbox.js'; ?>"></script>
    <script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/smoothbox/smoothbox.js?v=1.3.5'; ?>"></script>
    <?php
    foreach( $FUNCS->scripts as $k=>$v ){
        echo '<script type="text/javascript" src="'.$v.'"></script>'."\n";
    }
    ?>

    <link rel="stylesheet" href="<?php echo K_ADMIN_URL . 'includes/slimbox/slimbox.css'; ?>" type="text/css" media="screen" />
    <link rel="stylesheet" href="<?php echo K_ADMIN_URL . 'includes/smoothbox/smoothbox.css'; ?>" type="text/css" media="screen" />
    <link rel="stylesheet" href="<?php echo K_ADMIN_URL . 'theme/styles.css?ver='.K_COUCH_BUILD.''; ?>" type="text/css" media="screen" />
    <!--[if IE]>
    <link rel="stylesheet" href="<?php echo K_ADMIN_URL . 'theme/ie.css?ver='.K_COUCH_BUILD.''; ?>" type="text/css" media="screen, projection">
    <![endif]-->
    <?php
    foreach( $FUNCS->styles as $k=>$v ){
        echo '<link rel="stylesheet" href="'.$v.'" type="text/css" media="screen" />'."\n";
    }
    ?>
    <style>
        body{
            background:#fff !important;
        }
        #admin-wrapper{
            border: 0;
            padding: 0px 0px 15px 15px;
        }
    </style>
</head>
<body>

<div id="admin-wrapper">
    <div id="admin-wrapper-body">
