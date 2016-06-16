<?php
    /*
     *  This file has been adapted from the Symfony package for CouchCMS project.
     *
     *  (c) Fabien Potencier <fabien@symfony.com>
     *
     *   Permission is hereby granted, free of charge, to any person obtaining a copy
     *   of this software and associated documentation files (the "Software"), to deal
     *   in the Software without restriction, including without limitation the rights
     *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
     *   copies of the Software, and to permit persons to whom the Software is furnished
     *   to do so, subject to the following conditions:
     *
     *   The above copyright notice and this permission notice shall be included in all
     *   copies or substantial portions of the Software.
     *
     *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
     *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
     *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
     *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
     *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
     *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
     *   THE SOFTWARE.
     *
     */

    class EventDispatcher{

        var $listeners = array();
        var $sorted = array();

        function dispatch( $event_name, $args=array() ){

            if( !isset($this->listeners[$event_name]) ){
                return;
            }

            return $this->_do_dispatch( $this->get_listeners($event_name), $args );

        }

        function get_listeners( $event_name = null ){
            if( null !== $event_name ){
                if( !isset($this->sorted[$event_name]) ){
                    $this->_sort_listeners($event_name);
                }

                return $this->sorted[$event_name];
            }

            foreach( array_keys($this->listeners) as $event_name ){
                if( !isset($this->sorted[$event_name]) ){
                    $this->_sort_listeners( $event_name );
                }
            }

            return $this->sorted;
        }

        function has_listeners( $event_name = null ){
            return (bool) count( $this->get_listeners($event_name) );
        }

        function add_listener( $event_name, $listener, $priority = 0 ){
            $this->listeners[$event_name][$priority][] = $listener;
            unset( $this->sorted[$event_name] );
        }

        function remove_listener( $event_name, $listener ){
            if( !isset($this->listeners[$event_name]) ){
                return;
            }

            foreach( $this->listeners[$event_name] as $priority => $listeners ){
                if( false !== ($key = array_search($listener, $listeners, true)) ){
                    unset( $this->listeners[$event_name][$priority][$key], $this->sorted[$event_name] );
                }
            }
        }

        function has_listener( $event_name, $listener ){
            if( !isset($this->listeners[$event_name]) ){
                return false;
            }

            foreach( $this->listeners[$event_name] as $priority => $listeners ){
                if( false !== ($key = array_search($listener, $listeners, true)) ){
                    return true;
                }
            }

            return false;
        }

        function _do_dispatch( $listeners, &$args ){
            foreach( $listeners as $listener ){
                $stop_propogation = call_user_func_array( $listener, $args );
                if( $stop_propogation ){
                    return true;
                }
            }
        }

        function _sort_listeners( $event_name ){
            $this->sorted[$event_name] = array();

            if( isset($this->listeners[$event_name]) ){
                krsort( $this->listeners[$event_name] );
                $this->sorted[$event_name] = call_user_func_array( 'array_merge', $this->listeners[$event_name] );
            }
        }
    }// end class
