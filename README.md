Simple JSON-RPC http client
===========================

Install
-------

Add to composer from command line

    composer require timiki/rpc-client "^3.3"

Or add in composer.json

    "require"     : {
        "timiki/rpc-client" : "^3.3"
    }
    
    
Options
-------

attempts_on_error (int) - Count of attempts on connection or response  error (default: 10)

attempts_on_response_error (bool) - Attempt on response error  (default: false)

attempts_delay (int) - Delay in msec between attempts (default: 1000)