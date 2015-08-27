<?php

namespace Cronario\Logger;

interface LoggerInterface
{
    const LEVEL_DEBUG = 6;
    const LEVEL_TRACE = 5;
    const LEVEL_INFO = 4;
    const LEVEL_ATTEMPT = 3;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 1;
    const LEVEL_EXCEPTION = 0;

    public function __construct($options);

    public function log($level, $msg);

    public function debug($msg);

    public function trace($msg);

    public function info($msg);

    public function attempt($msg);

    public function warning($msg);

    public function error($msg);

    public function exception(\Exception $e);

}