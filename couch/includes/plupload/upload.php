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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>Multi upload</title>
<style type="text/css">
	body {
		font-family:Verdana, Geneva, sans-serif;
		font-size:13px;
		color:#333;
      background-color: #DFDFDF;
      margin: 0;
      padding: 0;
	}
   .plupload_container{
      padding: 0 !important;
   }
</style>
<link rel="stylesheet" href="<?php echo K_ADMIN_URL . 'includes/plupload/'; ?>jquery.plupload.queue/css/jquery.plupload.queue.css" type="text/css" media="screen" />

<script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/'; ?>jquery.min-1.5.1.js"></script>
<script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/plupload/'; ?>plupload.full.js"></script>
<script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/plupload/'; ?>jquery.plupload.queue/jquery.plupload.queue.js"></script>

</head>
<body>

<form method="post" action="">
	<div id="gallery_uploader" style="width: 100%; height: 100%;">You browser does not have HTML5 or Flash support.</div>
</form>

<script type="text/javascript">

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

	 //str_log += str+"\n\n";
	 alert( str );

      }

      plupload.addI18n({
         'Select files' : 'Select images',
         'Add files to the upload queue and click the start button.' : 'Add images to the upload queue and click the start button.',
         'Add files' : 'Add images',
         'Drag files here.' : 'Drag images here.'
      });

      // Setup flash version
      $("#gallery_uploader").pluploadQueue({
         // General settings
         runtimes : 'html5,silverlight,flash,html4',
         url : '<?php echo $upload_link; ?>',
         max_file_size : '2mb',
	 file_data_name : 'NewFile',
         chunk_size : '1mb',
         unique_names : false,
         filters : [
            {title : "Image files", extensions : "jpg,jpeg,gif,png,bmp"}
         ],
         // Flash settings
         flash_swf_url : '<?php echo K_ADMIN_URL . 'includes/plupload/'; ?>plupload.flash.swf',
         // Silverlight settings
         silverlight_xap_url : '<?php echo K_ADMIN_URL . 'includes/plupload/'; ?>plupload.silverlight.xap',

         init : {
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
            UploadComplete : function(up, files) {
               // Called when all the files has finished uploading
	       window.top.k_bulk_upload_result( str_log );
            }
         }

      });

   });
</script>

</body>
</html>
