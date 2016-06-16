<?php

    ob_start();
    define( 'K_ADMIN', 1 );

    if ( defined('K_COUCH_DIR') ) die( 'ajax.php is meant to be invoked directly' );
    define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(dirname(dirname(realpath(__FILE__)))).'/') );

    require_once( K_COUCH_DIR.'header.php' );
    header( 'Content-Type: text/html; charset='.K_CHARSET );

    $AUTH->check_access( K_ACCESS_LEVEL_ADMIN, 1 );

    // at this point we have a logged in user with appropriate priveleges

    require_once( K_COUCH_DIR.'includes/timthumb.php' );

    $data = ( isset($_GET['data']) ) ? str_replace( ' ', '+', $_GET['data'] ) : null;
    $img_x = ( isset($_GET['x']) && $FUNCS->is_natural( $_GET['x'] ) ) ? (int)$_GET['x'] : null;
    $img_y = ( isset($_GET['y']) && $FUNCS->is_natural( $_GET['y'] ) ) ? (int)$_GET['y'] : null;
    $img_w = ( isset($_GET['w']) && $FUNCS->is_non_zero_natural( $_GET['w'] ) ) ? (int)$_GET['w'] : null;
    $img_h = ( isset($_GET['h']) && $FUNCS->is_non_zero_natural( $_GET['h'] ) ) ? (int)$_GET['h'] : null;

    if( is_null($data) || is_null($img_x) || is_null($img_y) || is_null($img_w) || is_null($img_h) ){
        die( 'Invalid parameters' );
    }
    list( $img, $thumb_w, $thumb_h, $t_quality, $nonce ) = explode( '|', $data );

    // validate passed data not tampered with
    $data = $img . '|' . $thumb_w . '|' . $thumb_h . '|' . $t_quality;
    $FUNCS->validate_nonce( 'jcrop_image_' . $data, $nonce );
    $img = base64_decode( $img );

    if( extension_loaded('gd') && function_exists('gd_info') ){
        $src = $Config['UserFilesAbsolutePath'] . 'image/' . $img;
        if( file_exists($src) ){

            // create thumbnail
            // Adapted from TimThumb script created by Tim McDaniels and Darren Hoyt with tweaks by Ben Gillbanks
            // http://code.google.com/p/timthumb/

            ini_set( 'memory_limit', "64M" );

            // get mime type of src
            $mime_type = mime_type( $src );

            // make sure that the src is gif/jpg/png
            if( !valid_src_mime_type($mime_type) ){
                die( "Invalid src mime type: " .$mime_type );
            }

            // open the existing image
            $image = open_image( $mime_type, $src );
            if( $image === false ){
                die( 'Unable to open image : ' . $src );
            }

            // fabricate thumbnail name
            $path_parts = $FUNCS->pathinfo( $src );
            if( !$thumb_w || !$thumb_h ){ // freeform selection
                // dimensions of the thumbnail will be taken from the selected area of src
                $thumb_w = $img_w;
                $thumb_h = $img_h;

                // name of the thumbnail will, however, contain the original width and height of src (to match the name given by core 'thumbnail' editable region)
                $thumb_name = $path_parts['filename'] . '-' . round(imagesx($image)) . 'x' . round(imagesy($image)) . '.' . $path_parts['extension'];
            }
            else{
                $thumb_name = $path_parts['filename'] . '-' . round($thumb_w) . 'x' . round($thumb_h) . '.' . $path_parts['extension'];
            }
            $thumbnail = $path_parts['dirname'] . '/' . $thumb_name;
            $thumbnail_url = $Config['k_append_url'] . $Config['UserFilesPath'] . 'image/' . pathinfo( $img,  PATHINFO_DIRNAME ) . '/' . $thumb_name ;

            // create a new true color image
            $canvas = imagecreatetruecolor( $thumb_w, $thumb_h );
            imagealphablending( $canvas, false );
            // Create a new transparent color for image
            $color = imagecolorallocatealpha( $canvas, 0, 0, 0, 127 );
            // Completely fill the background of the new image with allocated color.
            imagefill( $canvas, 0, 0, $color );
            // Restore transparency blending
            imagesavealpha( $canvas, true );

            // copy from src into the thumbnail
            imagecopyresampled( $canvas, $image, 0, 0, $img_x, $img_y, $thumb_w, $thumb_h, $img_w, $img_h );

            // save to disk
            save_image( $mime_type, $canvas, $thumbnail, $t_quality );

            // remove image from memory
            imagedestroy( $canvas );

            // Job done. Exit.
            die( 'OK:'.$thumbnail_url );

        }
        else{
            die( 'Image not found' );
        }
    }
    else{
        die( 'No GD image library installed' );
    }




