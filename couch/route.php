<?php
/**
 *
 * This file was originally part of the Aura Project for PHP.
 *
 * Modified for use with CouchCMS project
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */

class Route
{
    var $name;
    var $masterpage;
    var $path;
    var $params = array();
    var $values = array();
    var $method = array();
    var $secure = null;
    var $routable;
    var $is_match;
    var $generate;
    var $filters;
    var $validators = array();
    var $include_file;
    var $controller;
    var $action;
    var $access_callback;
    var $access_callback_params;
    var $module;


    var $regex;
    var $matches;
    var $debug;
    var $wildcard;
    var $resolved_values = array();

    function __construct(
        $name         = null,
        $masterpage   = null,
        $path         = null,
        $params       = null,
        $values       = null,
        $method       = null,
        $secure       = null,
        $routable     = true,
        $is_match     = null,
        $generate     = null,
        $filters      = null,
        $validators   = null,
        $include_file = null,
        $class        = null,
        $action       = null,
        $access_callback = null,
        $access_callback_params = null,
        $module       = null
    ) {

        $this->name         = (string) $name;
        $this->masterpage   = (string) $masterpage;
        $this->path         = (string) $path;
        $this->params       = (array) $params;
        $this->values       = (array) $values;
        $this->method       = ($method === null) ? null : (array) $method;
        $this->secure       = ($secure === null) ? null : (bool)  $secure;
        $this->routable     = (bool) $routable;
        $this->is_match     = $is_match;
        $this->generate     = $generate;
        $this->filters      = $filters;
        $this->validators   = (array) $validators;
        $this->include_file = trim( $include_file );
        $this->class        = trim( $class );
        $this->action       = $action;
        $this->access_callback          = $access_callback;
        $this->access_callback_params   = $access_callback_params;
        $this->module       = (string) $module;

        // convert path and params to a regular expression
        $this->setRegex();
    }

    /**
     *
     * Checks if a given path and server values are a match for this
     * Route.
     *
     * @param string $path The path to check against this Route.
     *
     * @param array $server A copy of $_SERVER so that this Route can check
     * against the server values.
     *
     * @return bool
     *
     */
    function isMatch($path, array $server)
    {
        if (! $this->routable) {
            $this->debug[] = 'Not routable.';
            return false;
        }

        $is_match = $this->isRegexMatch($path)
                 && $this->isMethodMatch($server)
                 && $this->isSecureMatch($server)
                 && $this->isCustomMatch($server)
                 && $this->isValidatorMatch();

        if (! $is_match) {
            return false;
        }

        // populate the path matches into the route values
        foreach ($this->matches as $key => $val) {
            if (is_string($key)) {
                $this->values[$key] = rawurldecode($val);
            }
        }

        // is a wildcard param specified?
        if ($this->wildcard) {

            // are there are actual wildcard values?
            if (empty($this->values[$this->wildcard])) {
                // no, set a blank array
                $this->values[$this->wildcard] = array();
            } else {
                // yes, retain and rawurldecode them
                $this->values[$this->wildcard] = array_map(
                    'rawurldecode',
                    explode('/', $this->values[$this->wildcard])
                );
            }

        }

        // done!
        $this->resolved_values = $this->values;
        return true;
    }

    /**
     *
     * Gets the path for this Route with data replacements for param tokens.
     *
     * @param array $data An array of key-value pairs to interpolate into the
     * param tokens in the path for this Route. Keys that do not map to
     * params are discarded; param tokens that have no mapped key are left in
     * place.
     *
     * @return string
     *
     */
    function generate(array $data = null)
    {
        global $FUNCS;

        // use a callable to modify the path data?
        if ($this->generate) {
            $callback = $FUNCS->is_callable( $this->generate );
            if( $callback ){
                call_user_func_array( $callback, array(&$this, &$data) );
            }
        }

        // interpolate into the path
        $replace = array();
        $data = array_merge($this->values, (array) $data);
        foreach ($data as $key => $val) {
            $replace["{:$key}"] = rawurlencode($val);
        }
        return strtr($this->path, $replace);
    }

    /**
     *
     * Sets the regular expression for this Route based on its params.
     *
     * @return void
     *
     */
    function setRegex()
    {

        // is a required wildcard indicated at the end of the path?
        $match = preg_match("/\/\{:([a-z_][a-z0-9_]+)\+\}$/i", $this->path, $matches);
        if ($match) {
            $this->wildcard = $matches[1];
            $pos = strrpos($this->path, $matches[0]);
            $this->path = substr($this->path, 0, $pos) . "/{:{$this->wildcard}:(.+)}";
        }

        // is an optional wildcard indicated at the end of the path?
        $match = preg_match("/\/\{:([a-z_][a-z0-9_]+)\*\}$/i", $this->path, $matches);
        if ($match) {
            $this->wildcard = $matches[1];
            $pos = strrpos($this->path, $matches[0]);
            $this->path = substr($this->path, 0, $pos) . "(/{:{$this->wildcard}:(.*)})?";
        }

        // now extract inline token params from the path. converts
        // {:token:regex} to {:token} and retains the regex in params.
        $find = "/\{:(.*?)(:(.*?))?\}/";
        preg_match_all($find, $this->path, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $whole = $match[0];
            $name  = $match[1];
            if (isset($match[3])) {
                // there is an inline token pattern; retain it, overriding
                // the existing param ...
                $this->params[$name] = $match[3];
                // ... and replace in the path without the pattern.
                $this->path = str_replace($whole, "{:$name}", $this->path);
            } elseif (! isset($this->params[$name])) {
                // use a default pattern when none exists
                $this->params[$name] = "([^/]+)";
            }
        }

        // now create the regular expression from the path and param patterns
        $this->regex = $this->path;
        if ($this->params) {
            $keys = array();
            $vals = array();
            foreach ($this->params as $name => $subpattern) {
                if ($subpattern[0] != '(') {
                    $message = "Route '$this->name' - Subpattern for route_params '$name' must start be enclosed within '(' ')'.";
                    die($message);
                } else {
                    $keys[] = "{:$name}";
                    $vals[] = "(?P<$name>" . substr($subpattern, 1);
                }
            }
            $this->regex = str_replace($keys, $vals, $this->regex);
        }
    }

    /**
     *
     * Checks that the path matches the Route regex.
     *
     * @param string $path The path to match against.
     *
     * @return bool True on a match, false if not.
     *
     */
    function isRegexMatch($path)
    {
        $regex = "#^{$this->regex}$#";
        $match = preg_match($regex, $path, $this->matches);
        if (! $match) {
            $this->debug[] = 'Not a regex match.';
        }
        return $match;
    }

    /**
     *
     * Checks that the Route `$method` matches the corresponding server value.
     *
     * @param array $server A copy of $_SERVER.
     *
     * @return bool True on a match, false if not.
     *
     */
    function isMethodMatch($server)
    {
        if (isset($this->method)) {
            if (! isset($server['REQUEST_METHOD'])) {
                $this->debug[] = 'Method match requested but REQUEST_METHOD not set.';
                return false;
            }
            if (! in_array(strtoupper($server['REQUEST_METHOD']), $this->method)) {
                $this->debug[] = 'Not a method match.';
                return false;
            }
        }
        return true;
    }

    /**
     *
     * Checks that the Route `$secure` matches the corresponding server values.
     *
     * @param array $server A copy of $_SERVER.
     *
     * @return bool True on a match, false if not.
     *
     */
    function isSecureMatch($server)
    {
        if ($this->secure !== null) {

            $is_secure = (isset($server['HTTPS']) && $server['HTTPS'] == 'on')
                      || (isset($server['SERVER_PORT']) && $server['SERVER_PORT'] == 443);

            if ($this->secure == true && ! $is_secure) {
                $this->debug[] = 'Secure required, but not secure.';
                return false;
            }

            if ($this->secure == false && $is_secure) {
                $this->debug[] = 'Non-secure required, but is secure.';
                return false;
            }
        }
        return true;
    }

    /**
     *
     * Checks that the custom Route `$is_match` callable returns true, given
     * the server values.
     *
     * @param array $server A copy of $_SERVER.
     *
     * @return bool True on a match, false if not.
     *
     */
    function isCustomMatch($server)
    {
        if (! $this->is_match) {
            return true;
        }

        // pass the matches as an object, not as an array, so we can avoid
        // tricky hacks for references
        $matches = new ArrayObject($this->matches);
        $is_match = $this->is_match;
        $result = $is_match($server, $matches);

        // convert back to array
        $this->matches = $matches->getArrayCopy();

        // did it match?
        if (! $result) {
            $this->debug[] = 'Not a custom match.';
        }

        return $result;
    }

    function isValidatorMatch(){
        global $FUNCS;

        if( !$this->validators ){
            return true;
        }
        extract( $this->validators );

        // route validator specified?
        $_route = trim( $_route );
        if( strlen($_route) ){
            if( strpos($_route, '::')!==false ){
                $arr = explode( '::', $_route );
                if( is_callable(array($arr[0], $arr[1])) ){
                    $err = call_user_func_array( array($arr[0], $arr[1]), array(&$this) );
                }
                else{
                    $this->debug[] = "route_validators - '_route' function '".$_route."' not found";
                    return false;
                }
            }
            else{
                if( function_exists($_route) ) {
                    $err = call_user_func_array( $_route, array(&$this) );
                }
                else{
                    $this->debug[] = "route_validators - '_route' function '".$_route."' not found";
                    return false;
                }
            }
            if( $FUNCS->is_error($err) ){
                $this->debug[] = "route_validators - '_route': ". $err->err_msg;
                return false;
            }
        }

        // move on to individual parameters
        foreach( $this->matches as $key => $val ){
            if( is_string($key) && isset($$key) ){
                $validator = $$key;
                $validator = strtolower( trim($$key) );
                if( !strlen($validator) ) continue;

                // piggy-back on validation routine of field
                $field_info = array(
                    'id' => -1,
                    'name' => 'dummy',
                    'k_type' => 'text',
                    'data' => rawurldecode($val),
                    'validator' => $validator,
                    'k_separator' => $_separator,
                    'val_separator' => $_val_separator,
                    'search_type'=>'text',
                );

                $siblings = array();
                $f = new KFieldForm( $field_info, $siblings );

                if( !$f->validate() ){
                    $this->debug[] = "route_validators - '$key': ". $f->err_msg;
                    return false;
                }
            }
        }
        return true;
    }
}
