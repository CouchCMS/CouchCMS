<?php
    /*
    The contents of this file are subject to the Common Public Attribution License
    Version 1.0 (the "License"); you may not use this file except in compliance with
    the License. You may obtain a copy of the License at
    http://www.couchcms.com/cpal.html. The License is based on the Mozilla
    Public License Version 1.1 but Sections 14 and 15 have been added to cover use
    of software over a computer network and provide for limited attribution for the
    Original Developer. In addition, Exhibit A has been modified to be consistent with
    Exhibit B.

    Software distributed under the License is distributed on an "AS IS" basis, WITHOUT
    WARRANTY OF ANY KIND, either express or implied. See the License for the
    specific language governing rights and limitations under the License.

    The Original Code is the CouchCMS project.

    The Original Developer is the Initial Developer.

    The Initial Developer of the Original Code is Kamran Kashif (kksidd@couchcms.com).
    All portions of the code written by Initial Developer are Copyright (c) 2009, 2010
    the Initial Developer. All Rights Reserved.

    Contributor(s):

    Alternatively, the contents of this file may be used under the terms of the
    CouchCMS Commercial License (the CCCL), in which case the provisions of
    the CCCL are applicable instead of those above.

    If you wish to allow use of your version of this file only under the terms of the
    CCCL and not to allow others to use your version of this file under the CPAL, indicate
    your decision by deleting the provisions above and replace them with the notice
    and other provisions required by the CCCL. If you do not delete the provisions
    above, a recipient may use your version of this file under either the CPAL or the
    CCCL.
    */
    ob_start();
    define( 'K_ADMIN', 1 );

    if ( !defined('K_COUCH_DIR') ) define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(realpath(__FILE__) ).'/') );
    require_once( K_COUCH_DIR.'header.php' );

    if( !isset($_GET['auth']{0}) ) { ob_end_clean(); die; }

    $data = $_GET['auth'];
    $data = str_replace( ' ', '+', $data ); // for some reason, '+' is getting converted to space in $_GET. (happens when urldecode is used on '+')
    list( $link, $key, $expiry, $access_level, $prompt_login, $action, $cache_for, $count_hits, $hash ) = explode( '|', $data );

    // First of all verify the hash to make sure the data has not been tampered with.
    $data = $link . '|' . $key . '|' . $expiry . '|' . $access_level . '|' . $prompt_login . '|' . $action . '|' . $cache_for . '|' . $count_hits;
    $key2 = $FUNCS->hash_hmac( $data, $FUNCS->get_secret_key() );
    $hash2 = $FUNCS->hash_hmac( $data, $key2 );
    if( $hash2 != $hash ) { ob_end_clean(); die; }

    if( $FUNCS->is_non_zero_natural($link) ){
        $is_attachment = 1; // editable region of type 'securefile'
        if( $key=='1' ) $is_thumb = 1;
    }

    // Next check if link has not expired
    if( $expiry ){
        if( time() > $expiry ){
            ob_end_clean();
            die( 'Link expired' );
        }
    }

    // Check if access level is ok
    if( $access_level ){
        $AUTH->check_access( $access_level, !$prompt_login );
    }

    // All checks ok. Get down with business
    if( !$is_attachment ){
        if( $action == 1 ){
            $redirect = 1;
        }
        elseif( $action == 2 ){
            $force_download = 1;
        }
        // Decrypt the link
        $link = base64_decode( $link );
        $link = $FUNCS->decrypt( $link, $key );

        $arr_mime_types = array(
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'asc' => 'text/plain',
            'au' => 'audio/basic',
            'avi' => 'video/x-msvideo',
            'bcpio' => 'application/x-bcpio',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'cdf' => 'application/x-netcdf',
            'class' => 'application/octet-stream',
            'cpio' => 'application/x-cpio',
            'cpt' => 'application/mac-compactpro',
            'csh' => 'application/x-csh',
            'css' => 'text/css',
            'dcr' => 'application/x-director',
            'dir' => 'application/x-director',
            'djv' => 'image/vnd.djvu',
            'djvu' => 'image/vnd.djvu',
            'dll' => 'application/octet-stream',
            'dms' => 'application/octet-stream',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dvi' => 'application/x-dvi',
            'dxr' => 'application/x-director',
            'eps' => 'application/postscript',
            'etx' => 'text/x-setext',
            'exe' => 'application/octet-stream',
            'ez' => 'application/andrew-inset',
            'gif' => 'image/gif',
            'gtar' => 'application/x-gtar',
            'hdf' => 'application/x-hdf',
            'hqx' => 'application/mac-binhex40',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ice' => 'x-conference/x-cooltalk',
            'ief' => 'image/ief',
            'iges' => 'model/iges',
            'igs' => 'model/iges',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/x-javascript',
            'kar' => 'audio/midi',
            'latex' => 'application/x-latex',
            'lha' => 'application/octet-stream',
            'lzh' => 'application/octet-stream',
            'm3u' => 'audio/x-mpegurl',
            'man' => 'application/x-troff-man',
            'me' => 'application/x-troff-me',
            'mesh' => 'model/mesh',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mif' => 'application/vnd.mif',
            'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpga' => 'audio/mpeg',
            'ms' => 'application/x-troff-ms',
            'msh' => 'model/mesh',
            'mxu' => 'video/vnd.mpegurl',
            'nc' => 'application/x-netcdf',
            'oda' => 'application/oda',
            'pbm' => 'image/x-portable-bitmap',
            'pdb' => 'chemical/x-pdb',
            'pdf' => 'application/pdf',
            'pgm' => 'image/x-portable-graymap',
            'pgn' => 'application/x-chess-pgn',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'ppm' => 'image/x-portable-pixmap',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ps' => 'application/postscript',
            'qt' => 'video/quicktime',
            'ra' => 'audio/x-realaudio',
            'ram' => 'audio/x-pn-realaudio',
            'rar' => 'application/x-rar-compressed',
            'ras' => 'image/x-cmu-raster',
            'rgb' => 'image/x-rgb',
            'rm' => 'audio/x-pn-realaudio',
            'roff' => 'application/x-troff',
            'rpm' => 'audio/x-pn-realaudio-plugin',
            'rtf' => 'text/rtf',
            'rtx' => 'text/richtext',
            'sgm' => 'text/sgml',
            'sgml' => 'text/sgml',
            'sh' => 'application/x-sh',
            'shar' => 'application/x-shar',
            'silo' => 'model/mesh',
            'sit' => 'application/x-stuffit',
            'skd' => 'application/x-koan',
            'skm' => 'application/x-koan',
            'skp' => 'application/x-koan',
            'skt' => 'application/x-koan',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'snd' => 'audio/basic',
            'so' => 'application/octet-stream',
            'spl' => 'application/x-futuresplash',
            'src' => 'application/x-wais-source',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'svg' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            't' => 'application/x-troff',
            'tar' => 'application/x-tar',
            'tcl' => 'application/x-tcl',
            'tex' => 'application/x-tex',
            'texi' => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'tr' => 'application/x-troff',
            'tsv' => 'text/tab-separated-values',
            'txt' => 'text/plain',
            'ustar' => 'application/x-ustar',
            'vcd' => 'application/x-cdlink',
            'vrml' => 'model/vrml',
            'wav' => 'audio/x-wav',
            'webm' => 'video/webm',
            'wbmp' => 'image/vnd.wap.wbmp',
            'wbxml' => 'application/vnd.wap.wbxml',
            'wml' => 'text/vnd.wap.wml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmls' => 'text/vnd.wap.wmlscript',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'wrl' => 'model/vrml',
            'xbm' => 'image/x-xbitmap',
            'xht' => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml' => 'text/xml',
            'xpm' => 'image/x-xpixmap',
            'xsl' => 'text/xml',
            'xwd' => 'image/x-xwindowdump',
            'xyz' => 'chemical/x-xyz',
            'zip' => 'application/zip'
        );
    }
    else{
        if( $action == 2 ){
            $force_download = 1;
        }

        // for attachments, only these image types are considered for display.
        // rest show the download box
        $arr_mime_types = array(
            'gif' => 'image/gif',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'png' => 'image/png'
        );
    }

    if( $redirect ){
        $link = $FUNCS->sanitize_url( $link );
        header("Location: ".$link);
        exit;
    }
    else{
        if( $is_attachment ){
            $rs = $DB->select( K_TBL_ATTACHMENTS, array('file_real_name', 'file_disk_name','file_extension','file_size'), "attach_id='" . $DB->sanitize( $link ). "'" );
            if( count($rs) ){
                $path = $Config['UserFilesAbsolutePath'] . 'attachments/' . $rs[0]['file_disk_name'];
                if( $is_thumb ) $path .= '_t';
                $path .= '.' . $rs[0]['file_extension'];
                $size = ( $is_thumb ) ? @filesize( $path ) : $rs[0]['file_size'];
                $fname = $rs[0]['file_real_name'];
                $ext = $rs[0]['file_extension'];

                // update hit count if required
                if( $count_hits && !$is_thumb ){
                    $sql = "UPDATE " . K_TBL_ATTACHMENTS . " SET hit_count = hit_count + 1 WHERE attach_id = '" . $DB->sanitize( $link ) . "'";
                    $DB->_query( $sql );
                }
            }
            else{
                header('HTTP/1.1 404 Not Found');
                header('Status: 404 Not Found');
                header('Content-Type: text/html; charset='.K_CHARSET );
                die( 'File not found' );
            }
        }
        else{
            $path = get_path( $link );
            $size = @filesize( $path );
            $pos = strrpos( $link,"/" );
            $fname = substr( $link, $pos+1, strlen($link)-$pos );
            $ext = substr( strrchr($fname, "."), 1 );
        }
        $mime_type = get_mime_type( $ext );

        ob_end_clean();
        header("Pragma: public");
        if( $is_attachment && $cache_for && !($expiry || $access_level || $prompt_login ) ){
            //$cache_for = 60 * 60 * 24 * 3;
            header("Expires: " . @gmdate("D, d M Y H:i:s", time() + $cache_for) . " GMT");
            header("Cache-Control: max-age=".$cache_for.", must-revalidate");
        }
        else{
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false);
        }
        if( $force_download ){
            header("Content-Description: File Transfer");
            header("Content-Type: application/force-download");
            header("Content-Disposition: attachment; filename=\"".$fname."\"\n");
        }
        else{
            header("Content-Disposition: filename=\"".$fname."\"\n");
            header("Content-Type: ".$mime_type."\n");
        }
        header("Content-transfer-encoding: binary\n");
        if( $size ){
            header("Content-Length: ".$size."\n");
        }

        $fp = @fopen( $path, "rb" );
        if( $fp ){
            @set_time_limit( 0 );
            while( !feof($fp) ){
                echo fread( $fp, 8192 );
                flush(); // this is essential for large downloads
                if( connection_status()!=0 ){
                    @fclose( $fp );
                    die();
                }
            }
            @fclose( $fp );
            exit;
        }
        else{
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
            header('Content-Type: text/html; charset='.K_CHARSET );
            die( 'File not found' );
        }
    }

    /////////////////
    function get_path( $link, $is_attachment=null, $is_thumb=null ){
        // If file resides on local server..
        if( strpos($link, K_SITE_URL)===0 ){
            // ..convert link to local path.
            $link = str_replace( K_SITE_URL, '', $link );
            $site_dir = realpath( K_SITE_DIR ) . '/';
            $site_dir = str_replace( '\\', '/', $site_dir );
            $link = $site_dir . $link;
        }
        return $link;
    }

    function get_mime_type( $ext ){
        global $arr_mime_types;
        if( array_key_exists($ext, $arr_mime_types) ){
            return $arr_mime_types[$ext];
        }
        else{
            return 'application/octet-stream';
        }
    }
