<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    class KMosaicCTX{
        static function _render_fields( $f ){
            global $FUNCS, $CTX;

            if( $f->k_type=='datetime' ){
                $date = trim( $f->data );
                if( strlen($date) ){
                    $sep = $f->fields_separator;
                    $year = substr( $date, 0, 4 );
                    $month = substr( $date, 5, 2 );
                    $day = substr( $date, 8, 2 );

                    if( $f->months ){
                        $arrMonths = explode( ',', $f->months );
                    }
                    else{
                        $arrMonths = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
                    }
                    $month = $arrMonths[intval($month)-1];

                    switch( $f->format ){
                        case 'dmy':
                            $formatted_date = $day . $sep . $month . $sep . $year;
                            break;
                        case 'ymd':
                            $formatted_date = $year . $sep . $month . $sep . $day;
                            break;
                        case 'mdy':
                            $formatted_date = $month . $sep . $day . $sep . $year;
                    }

                    // Append time?
                    if( $f->allow_time ){
                        $h = substr( $date, 11, 2 );
                        $m = substr( $date, 14, 2 );

                        if( $f->am_pm ){
                            // 24-hour time to 12-hour time
                            list( $h, $m, $a ) = explode( ":", @date("h:i:A", strtotime("$h:$m")) );
                            $formatted_date .= "@ $h:$m $a";
                        }
                        else{
                            $formatted_date .= "@$h:$m";
                        }
                    }

                    if( $f->only_time ){
                        $h = substr( $date, 11, 2 );
                        $m = substr( $date, 14, 2 );

                        if( $f->am_pm ){
                            // 24-hour time to 12-hour time
                            list( $h, $m, $a ) = explode( ":", @date("h:i:A", strtotime("$h:$m")) );
                            $formatted_date = "$h:$m $a";
                        }
                        else{
                            $formatted_date = "$h:$m";
                        }
                    }

                    $CTX->set( 'k_date_formatted', $formatted_date );
                }
            }
            elseif( $f->k_type=='file' ){
                $data = $f->data;
                if( $data[0]==':' ){ // if local marker
                    $data = substr( $data, 1 );
                }
                $CTX->set( 'k_file_name', $data );
            }
            elseif( $f->k_type=='__repeatable' ){
                if( $f->stacked_layout ){
                    return array( 'display_field_repeatable_stacked' ); // candidate template
                }
            }
        }
    } // end class KMosaicCTX
