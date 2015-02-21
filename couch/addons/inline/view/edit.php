<?php
if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

$err_div = '<div class="error" style="margin-bottom:10px; color:red; display:';
if( $errors ){
    $err_div .= "block\">";
    $err_title = ($errors>1)?'ERRORS':'ERROR';
    $err_div .= $errors. ' ' .$err_title.':<br>';
}
else{
    $err_div .= "none\">&nbsp;";
}
$err_div .= '</div>';
echo $err_div;
?>
    <form name="frm_edit_page" id="frm_edit_page" action="" method="post" accept-charset="<?php echo K_CHARSET; ?>"<?php if($requires_multipart){echo ' enctype="multipart/form-data" ';}?>>
        <div id="admin-content">
        <?php
        foreach( $arr_fields as $k=>$v ){
           $f = &$arr_fields[$k];
           echo $f->render();
           unset( $f );
        }
        ?>
        </div>
        <p>
        <input type="hidden" name="op" value="save" />
        <a class="button" id="btn_submit" href="#" onclick="this.style.cursor='wait'; this.fireEvent('my_submit'); $('frm_edit_page').submit(); return false;"><span><?php echo $FUNCS->t('save'); ?></span></a>

    </form>
<?php require_once( K_COUCH_DIR.'addons/inline/view/footer.php' ); ?>
