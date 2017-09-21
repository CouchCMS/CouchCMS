<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    // 1. Method used for running GC (Garbage Collector) in background.
    //
    // Four valid options available (apart from leaving empty) -
    // 'curl'    - uses Curl library if available
    // 'stream'  - uses stream_socket_client() function if available
    // 'ajax'    - uses JavaScript
    // 'manual'  - has to be triggered manually. Useful only for debugging/development.
    //
    // Leave empty for the system to automatically choose the first working method found
    $cfg['method'] = '';

    // 2. Force the use of only IPv4 on servers that have trouble with 'curl' or 'stream' methods on IPv6
    $cfg['use_ipv4'] = '0';

    // 3. The maximun memory limit (in MB) after which the background process will quit
    $cfg['memory_limit'] = '32'; // MB

    // 4. The maximun time limit (in seconds) after which the background process will quit
    $cfg['time_limit'] = '20'; // seconds

    // 5. Log verbose messages for debugging
    $cfg['debug'] = '0';
