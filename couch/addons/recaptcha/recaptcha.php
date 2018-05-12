<?php

if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

if( !defined('K_RECAPTCHA_SITE_KEY') ) define( 'K_RECAPTCHA_SITE_KEY', '' );
if( !defined('K_RECAPTCHA_SECRET_KEY') ) define( 'K_RECAPTCHA_SECRET_KEY', '' );

class KReCaptchaForm extends KUserDefinedFormField{
    static function handle_params( $params, $node ){
        global $FUNCS;
        $attr = $FUNCS->get_named_vars(
                    array( 'theme'=>'light', // light, dark
                           'size'=>'normal', // normal, compact
                          ),
                    $params);
        $attr['theme'] = ( $attr['theme']=='dark' ) ? $attr['theme'] : 'light';
        $attr['size'] = ( $attr['size']=='compact' ) ? $attr['size'] : 'normal';
        return $attr;
    }

    function validate(){
        if( $this->k_inactive ) return true;

        if( empty($_REQUEST['g-recaptcha-response']) ){
            $this->err_msg = 'reCAPTCHA is incomplete'; // TODO: localize string
            return false;
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $params = array(
            'secret' => K_RECAPTCHA_SECRET_KEY,
            'response' => $_REQUEST['g-recaptcha-response'],
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        );

        if( extension_loaded('curl') ){
            $ch = curl_init();

            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query( $params ),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
                CURLINFO_HEADER_OUT => false,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
            );

            foreach( $options as $k=>$v ){
                curl_setopt( $ch, $k, $v );
            }

            $response = curl_exec( $ch );
            curl_close( $ch );
        }
        else{
            $peer_key = version_compare( PHP_VERSION, '5.6.0', '<' ) ? 'CN_name' : 'peer_name';
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query( $params ),
                    'verify_peer' => true,
                    $peer_key => 'www.google.com',
                ),
            );
            $context = stream_context_create( $options );
            $response = @file_get_contents( $url, false, $context );
        }

        if( $response!==false ){
            $response = json_decode( $response, true );

            if( !$response ){
                $this->err_msg = 'Malformed reCAPTCHA response'; // TODO: localize string
                return false;
            }

            if( $response['success'] ){
                return true;
            }

            if( empty( $response['error-codes'] ) ){
                $this->err_msg = 'Unknown reCAPTCHA error(s)'; // TODO: localize string
            }
            else {
                $this->err_msg = 'reCAPTCHA Error(s): ' . implode( ', ', $response['error-codes'] ); // TODO: localize string
            }

            return false;
        }
        else {
            $this->err_msg = 'Unable to verify reCAPTCHA response'; // TODO: localize string
            return false;
        }
    }

    function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
        static $count=0;

        if( !$count ){
            ob_start();
            ?>
            <script>
                window.k_recaptchas = [ {name:"<?php echo $input_id. '_k_'.$count; ?>",theme:"<?php echo $this->theme; ?>",size:"<?php echo $this->size; ?>"} ];

                function k_onload_recaptcha_callback(){
                    if( window.k_recaptchas ){
                        for( var i=0;i<window.k_recaptchas.length;i++ ){
                            grecaptcha.render(window.k_recaptchas[i].name, {
                                'sitekey': '<?php echo K_RECAPTCHA_SITE_KEY; ?>',
                                'theme': window.k_recaptchas[i].theme,
                                'size': window.k_recaptchas[i].size
                            });
                        }
                    }
                };
            </script>
            <script src="https://www.google.com/recaptcha/api.js?onload=k_onload_recaptcha_callback&amp;render=explicit" async defer></script>
            <div id="<?php echo $input_id. '_k_'.$count; ?>"></div>
            <?php
            $html = ob_get_contents();
            ob_end_clean();
        }
        else{
            if( is_null($this->cached) ){
                // only one captcha per form allowed
                $ok = 1;
                foreach( $this->siblings as $sib ){
                    if( $sib->k_type==$this->k_type && $sib->name!=$this->name ){
                        $html = 'Only one reCAPTCHA per form allowed!';
                        $ok=0;
                        break;
                    }
                }

                if( $ok ){
                    $html = '<script>window.k_recaptchas.push( {name:"'.$input_id.'_k_'.$count.'",theme:"'.$this->theme.'",size:"'.$this->size.'"} );</script>';
                    $html .= '<div id="'. $input_id.'_k_'.$count .'"></div>';
                }
            }
            else{
                $html = $this->cached;
            }
        }
        $count++;

        return $this->wrap_fieldset( $html );
    }
}

$FUNCS->register_udform_field( 'recaptcha', 'KReCaptchaForm' );
