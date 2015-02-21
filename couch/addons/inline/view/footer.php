<?php if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly ?>
    </div>
</div>

<script type="text/javascript">

    function k_browse_result( id, fileurl ){
        $(id).set( 'value', fileurl );
        try{
            $(id + "_preview").set( {href: fileurl, style:{visibility:'visible'}} );
            $(id + "_img_preview").set( 'src', fileurl );
        }
        catch( e ){}

        TB_remove();
    }

    function k_crop_image( tpl_id, page_id, field_id, nonce ){
        var el_notice = 'k_notice_f_' + field_id;
        var el_preview = 'f_'+field_id+'_preview';
        var crop_pos = $('f_k_crop_pos_' + field_id).value;
        var qs = '<?php echo K_ADMIN_URL; ?>ajax.php?act=crop&tpl='+tpl_id+'&p='+page_id+'&tb='+encodeURIComponent( field_id )+'&nonce='+ encodeURIComponent( nonce )+'&cp='+encodeURIComponent(crop_pos);
        var requestHTMLData = new Request (
            {
                url: qs,
                onComplete: function(response){
                    if( response=='OK' ){
                        var href = $(el_preview).get('href');
                        if( href.indexOf('?') != -1 ){
                            href = href.substr(0, href.indexOf('?'));
                        }
                        href = href + '?rand=' + Math.random();
                        $(el_preview).set('href', href);
                        try{
                            $('f_'+field_id+'_tb_preview').set('src', href);
                        }
                        catch( e ){}

                        alert('<?php echo $FUNCS->t('thumb_recreated'); ?>');
                    }
                    else{
                        alert(response);
                    }
                }
            }
        ).send();
    }
</script>

</body>
</html>
