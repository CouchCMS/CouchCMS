<?php
/*
    Modified from original source of TimThumb script created by Tim McDaniels and Darren Hoyt.
    Original license reproduced below.
*/
/*
    TimThumb script created by Tim McDaniels and Darren Hoyt with tweaks by Ben Gillbanks
    http://code.google.com/p/timthumb/

    MIT License: http://www.opensource.org/licenses/mit-license.php

    Paramters
    ---------
    w: width
    h: height
    zc: zoom crop (0 or 1)
    q: quality (default is 75 and max is 100)

    HTML example: <img src="/scripts/timthumb.php?src=/images/whatever.jpg&w=150&h=200&zc=1" alt="" />
*/

/*
$sizeLimits = array(
    "100x100",
    "150x150",
);

error_reporting(E_ALL);
ini_set("display_errors", 1);
*/

if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

function k_resize_image( $src, $dest=0, $new_width=0, $new_height=0, $zoom_crop=1, $enforce_max=0, $quality=80, $crop_position = 'middle', $check_thumb_exists=0 ){
        global $FUNCS;

    // check to see if GD function exist
    if(!function_exists('imagecreatetruecolor')) {
        return displayError('GD Library Error: imagecreatetruecolor does not exist - please contact your webhost and ask them to install the GD library');
    }

    if( trim($src)=='' ){
        return displayError('Source image not set');
    }

    // get mime type of src
    $mime_type = mime_type($src);

    ini_set('memory_limit', "64M");

    // make sure that the src is gif/jpg/png
    if(!valid_src_mime_type($mime_type)) {
        return displayError("Invalid src mime type: " .$mime_type);
    }

    if(strlen($src) && file_exists($src)) {

        // open the existing image
        $info = @getimagesize( $src );
        if( $info===false || !intval($info[0]) || !intval($info[1]) ){
            return displayError('Unable to open image : ' . $src);
        }

        // Get original width and height
        $width = $info[0];
        $height = $info[1];

        // generate new w/h if not provided
        if( $new_width && !$new_height ) {

            $new_height = $height * ( $new_width / $width );

        } elseif($new_height && !$new_width) {

            $new_width = $width * ( $new_height / $height );

        } elseif(!$new_width && !$new_height) {

            $new_width = $width;
            $new_height = $height;

        }

        // If new dimensions cannot exceed certain values
        if( $enforce_max ){

            // the supplied width and height were actually the max permissible values.
            $max_width = $new_width;
            $max_height = $new_height;

            // make the new values the same as that of the source image
            $new_width = $width;
            $new_height = $height;

            // if new dimensions already within bounds (and this not a thumbnail that we are creating), return.
            if( ($src==$dest) && ($new_width <= $max_width) && ($new_height <= $max_height) ){
                return;
            }

            if( $new_width > $max_width ){
                if( !$zoom_crop ){
                    $ratio = (real)($max_width / $new_width);
                    $new_width = ((int)($new_width * $ratio));
                    $new_height = ((int)($new_height * $ratio));
                }
                else{
                    $new_width = $max_width;
                }
            }

            // if new height still overshoots maximum value
            if( $new_height > $max_height ){
                if( !$zoom_crop ){
                    $ratio = (real)($max_height / $new_height);
                    $new_width = ((int)($new_width * $ratio));
                    $new_height = ((int)($new_height * $ratio));
                }
                else{
                    $new_height = $max_height;
                }
            }

        }

        // Create filename if not provided one (happens only for thumbnails)
        if( !$dest ){
            $path_parts = $FUNCS->pathinfo( $src );
            $thumb_name = $path_parts['filename'] . '-' . round($new_width) . 'x' . round($new_height) . '.' . $path_parts['extension'];
            $thumbnail = $path_parts['dirname'] . '/' . $thumb_name;
            if( $check_thumb_exists && file_exists($thumbnail) ){
                return $thumb_name;
            }

        }

        // create a new true color image
        $canvas = imagecreatetruecolor( $new_width, $new_height );
        imagealphablending($canvas, false);
        // Create a new transparent color for image
        $color = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        // Completely fill the background of the new image with allocated color.
        imagefill($canvas, 0, 0, $color);
        // Restore transparency blending
        imagesavealpha($canvas, true);

        if( $zoom_crop ) {

            $src_x = $src_y = 0;
            $src_w = $width;
            $src_h = $height;

            $cmp_x = $width  / $new_width;
            $cmp_y = $height / $new_height;

            // if new dimensions equal to the original (and this not a thumbnail that we are creating), return.
            if( ($src==$dest) && ($cmp_x==1) && ($cmp_y==1) ){
                return;
            }

            // calculate x or y coordinate and width or height of source

            if ( $cmp_x > $cmp_y ) {

                $src_w = round( ( $width / $cmp_x * $cmp_y ) );
                $src_x = round( ( $width - ( $width / $cmp_x * $cmp_y ) ) / 2 );

            } elseif ( $cmp_y > $cmp_x ) {

                $src_h = round( ( $height / $cmp_y * $cmp_x ) );
                $src_y = round( ( $height - ( $height / $cmp_y * $cmp_x ) ) / 2 );

            }

            switch( $crop_position ){
                case 'top_left':
                        $src_x = 0;
                        $src_y = 0;
                        break;
                case 'top_center':
                        $src_y = 0;
                        break;
                case 'top_right':
                        $src_x *= 2;
                        $src_y = 0;
                        break;
                case 'middle_left':
                        $src_x = 0;
                        break;
                case 'middle':
                        break;
                case 'middle_right':
                        $src_x *= 2;
                        break;
                case 'bottom_left':
                        $src_x = 0;
                        $src_y *= 2;
                        break;
                case 'bottom_center':
                        $src_y *= 2;
                        break;
                case 'bottom_right':
                        $src_x *= 2;
                        $src_y *= 2;
                        break;

            }

            $image = open_image($mime_type, $src);
            if( $image === false ){ return displayError('Unable to open image : ' . $src); }
            imagecopyresampled( $canvas, $image, 0, 0, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h );

        } else {

            // copy and resize part of an image with resampling
            $image = open_image($mime_type, $src);
            if( $image === false ){ return displayError('Unable to open image : ' . $src); }
            imagecopyresampled( $canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

        }

        if( !$dest ){
            $dest = $thumbnail;
        }

        // output image to browser based on mime type
        save_image($mime_type, $canvas, $dest, $quality);

        // remove image from memory
        imagedestroy($canvas);

        return $thumb_name;

    } else {

        if(strlen($src)) {
            return displayError("image " . $src . " not found");
        } else {
            return displayError("no source specified");
        }

    }
    return;
}
/**
 *
 */
function save_image($mime_type, $image_resized, $file, $quality=80) {

    if (@touch($file)) {

        // give 666 permissions so that the developer
        // can overwrite web server user
        @chmod ($file, 0666);

        switch ($mime_type) {

            case 'image/jpeg':
                imagejpeg($image_resized, $file, $quality);
                break;

            default :
                $quality = floor ($quality * 0.09);
                imagepng($image_resized, $file, $quality);

        }
    }

}

/**
 *
 */
function open_image($mime_type, $src) {

    $mime_type = strtolower($mime_type);

    if (stristr ($mime_type, 'gif')) {

        $image = @imagecreatefromgif($src);

    } elseif (stristr($mime_type, 'jpeg')) {

        @ini_set ('gd.jpeg_ignore_warning', 1);
        $image = @imagecreatefromjpeg($src);

    } elseif (stristr ($mime_type, 'png')) {

        $image = @imagecreatefrompng($src);

    }

    return $image;

}

/**
 * determine the file mime type
 */
function mime_type($file) {

    if (stristr(PHP_OS, 'WIN')) {
        $os = 'WIN';
    } else {
        $os = PHP_OS;
    }

    $mime_type = '';

    if (function_exists('mime_content_type')) {
        $mime_type = @mime_content_type($file);
    }

    // use PECL fileinfo to determine mime type
    if (!valid_src_mime_type($mime_type)) {
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME);
            if ($finfo != '') {
                $mime_type = finfo_file($finfo, $file);
                finfo_close($finfo);
            }
        }
    }

    // try to determine mime type by using unix file command
    // this should not be executed on windows
    if (!valid_src_mime_type($mime_type) && $os != "WIN") {
        if (preg_match("/FREEBSD|LINUX/", $os)) {
            $mime_type = trim(@shell_exec('file -bi ' . escapeshellarg($file)));
        }
    }

    // use file's extension to determine mime type
    if (!valid_src_mime_type($mime_type)) {

        // set defaults
        $mime_type = 'image/png';
        // file details
        $fileDetails = pathinfo($file);
        $ext = strtolower($fileDetails["extension"]);
        // mime types
        $types = array(
             'jpg'  => 'image/jpeg',
             'jpeg' => 'image/jpeg',
             'png'  => 'image/png',
             'gif'  => 'image/gif'
         );

        if (strlen($ext) && strlen($types[$ext])) {
            $mime_type = $types[$ext];
        }

    }

    return $mime_type;

}

/**
 *
 */
function valid_src_mime_type($mime_type) {

    if (preg_match("/jpg|jpeg|gif|png/i", $mime_type)) {
        return true;
    }

    return false;

}

/**
 * check to if the url is valid or not
 */
function valid_extension ($ext) {

    if (preg_match("/jpg|jpeg|png|gif/i", $ext)) {
        return TRUE;
    } else {
        return FALSE;
    }

}

/**
 * generic error message
 */
function displayError($errorString = '') {

    global $FUNCS;
    return $FUNCS->raise_error( $errorString );

}
