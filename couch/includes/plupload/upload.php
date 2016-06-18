<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    $tpl = ( isset($_GET['tpl']) && $FUNCS->is_non_zero_natural($_GET['tpl']) ) ? (int)$_GET['tpl'] : null;
    if( is_null($tpl) ) die( 'No template specified' );
    $fid = ( isset($_GET['fid']) && $FUNCS->is_non_zero_natural($_GET['fid']) ) ? (int)$_GET['fid'] : -1;
    $cid = ( isset($_GET['cid']) && $FUNCS->is_non_zero_natural($_GET['cid']) ) ? (int)$_GET['cid'] : null;
    $rid = ( isset($_GET['rid']) && $FUNCS->is_non_zero_natural($_GET['rid']) ) ? (int)$_GET['rid'] : null;
    $fn = ( isset($_GET['fn']) ) ? $_GET['fn'] : '/';
    $nonce = $FUNCS->create_nonce( 'bulk_upload_'.$tpl.'_'.$fid.'_'.$fn );

    $upload_link = K_ADMIN_URL . 'uploader.php?tpl='.$tpl.'&fid='.$fid.'&fn='.$fn.'&nonce='. $nonce;
    if( $cid && $rid ) $upload_link .= '&cid='.$cid.'&rid='.$rid;

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
<meta charset="utf-8"/>

<title>Plupload</title>

<link rel="stylesheet" href="<?php echo K_ADMIN_URL . 'includes/plupload/jquery.plupload.queue/css/'; ?>jquery.plupload.queue.css" type="text/css" />

<style>
    html,
    body,
    #uploader,
    .plupload_wrapper {
        height: 100%;
        margin: 0;
    }

    #uploader_container {
        height: 100%;
        padding: 0;
        box-sizing: border-box;
    }

    .plupload_scroll .plupload_filelist {
        height: 202px;
    }
</style>

<script src="<?php echo K_ADMIN_URL . 'includes/'; ?>jquery-3.x.min.js"></script>
<script src="<?php echo K_ADMIN_URL . 'includes/plupload/'; ?>plupload.full.min.js"></script>
<script src="<?php echo K_ADMIN_URL . 'includes/plupload/jquery.plupload.queue/'; ?>jquery.plupload.queue.min.js"></script>

</head>
<body>
<div id="uploader">
    <p>Your browser doesn't have Flash, Silverlight or HTML5 support.</p>
</div>

<script>
// Initialize the widget when the DOM is ready
$(function() {
    var str_log = "";
    function log() {
        var str = "";

        plupload.each(arguments, function(arg) {
            var row = "";

            if (typeof(arg) != "string") {
                plupload.each(arg, function(value, key) {
                    // Convert items in File objects to human readable form
                    if (arg instanceof plupload.File) {
                        // Convert status to human readable
                        switch (value) {
                            case plupload.QUEUED:
                            value = 'QUEUED';
                            break;

                            case plupload.UPLOADING:
                            value = 'UPLOADING';
                            break;

                            case plupload.FAILED:
                            value = 'FAILED';
                            break;

                            case plupload.DONE:
                            value = 'DONE';
                            break;
                        }
                    }

                    if (typeof(value) != "function") {
                        row += (row ? ', ' : '') + key + '=' + value;
                    }
                });

                str += row + " ";
            } else {
                str += arg + " ";
            }
        });

        alert(str);
    }

    plupload.addI18n({
        'Select files': 'Select images',
        'Add files to the upload queue and click the start button.': 'Add images to the upload queue and click the start button.',
        'Add Files': 'Add Images',
        'Drag files here.': 'Drag images here.'
    });

    $("#uploader").pluploadQueue({
        // General settings
        runtimes: 'html5,flash,silverlight,html4',
        url: '<?php echo $upload_link; ?>',

        file_data_name: 'NewFile',
        chunk_size: '1mb',

        filters: {
            // Maximum file size
            max_file_size: '2mb',
            // Specify what files to browse for
            mime_types: [
                {title: "Image files", extensions: "jpg,jpeg,gif,png,bmp"}
            ]
        },

        // Rename files by clicking on their titles
        rename: true,

        // Sort files
        sortable: false,

        // Enable ability to drag'n'drop files onto the widget (currently only HTML5 supports that)
        dragdrop: true,

        // Views to activate
        views: {
            list: true,
            thumbs: false,
            active: 'list'
        },

        // Flash settings
        flash_swf_url: '<?php echo K_ADMIN_URL . 'includes/plupload/'; ?>Moxie.swf',

        // Silverlight settings
        silverlight_xap_url: '<?php echo K_ADMIN_URL . 'includes/plupload/'; ?>Moxie.xap',

         init: {
            FileUploaded: function(up, file, info) {
                // Called when a file has finished uploading
                if( info.response ){
                    info.response = $.trim( info.response )
                    if( info.response.length ){
                        log('[Error] File:', file, "Info:", info);
                    }
                }
            },
            Error: function(up, args) {
                // Called when a error has occured
                log('[Error] ', args);
            },
            UploadComplete: function(up, files) {
                // Called when all the files has finished uploading
                window.top.k_bulk_upload_result( str_log );
            }
         }
    });
});
</script>
</body>
</html>
