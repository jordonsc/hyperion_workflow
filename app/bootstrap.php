<?php

require_once(__DIR__.'/../vendor/autoload.php');
require(__DIR__.'/app.php');

function hyperionExceptionHandler($errno, $errstr, $errfile, $errline, array $errcontext)
{
    // Error was suppressed with the @-operator
    if (error_reporting() === 0) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

set_error_handler('hyperionExceptionHandler');

