<?php

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LogLevel;

$dateFormat = "Y-m-d\TH:i:sP";

// the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
// we now change the default output format according to our needs.
// $output = "%datetime% %level_name% : %message% %context% %extra%\n";
$output = "%level_name% %message% %context% %extra% | %channel%\n";

// finally, create a formatter
$formatter = new LineFormatter($output, $dateFormat);

/**
 *    DEBUG (100): Detailed debug information.
 *    INFO (200): Interesting events. Examples: User logs in, SQL logs.
 *    NOTICE (250): Normal but significant events.
 *    WARNING (300): Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
 *    ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
 *    CRITICAL (500): Critical conditions. Example: Application component unavailable, unexpected exception.
 *    ALERT (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
 *    EMERGENCY (600): Emergency: system is unusable.
 */

$VNBIZ_LOGGER = new Logger('app');

$VNBIZ_ERRORLOGHANDLER = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, LogLevel::INFO);
$VNBIZ_ERRORLOGHANDLER->setFormatter($formatter);

$GLOBALS['VNBIZ_LOGGER'] = $VNBIZ_LOGGER;
$GLOBALS['VNBIZ_ERRORLOGHANDLER'] = $VNBIZ_ERRORLOGHANDLER;


/**
 * Check if debug is enabled.
 * Debug will be enabled if:
 * - The environment variable VNBIZ_DEBUG is set to true
 * - The GET parameter VNBIZ_DEBUG is set to true
 * 
 * @return bool
 * @since 1.0.0
 * @version 1.0.0
 */
function vnbiz_debug_enabled() {
    return isset($_SERVER['VNBIZ_DEBUG']) && $_SERVER['VNBIZ_DEBUG'] === 'true' || isset($_GET['VNBIZ_DEBUG']) && $_GET['VNBIZ_DEBUG'] === 'true';
}

if (vnbiz_debug_enabled()) {
    $VNBIZ_ERRORLOGHANDLER->setLevel(LogLevel::DEBUG);
}

$VNBIZ_LOGGER->pushHandler($VNBIZ_ERRORLOGHANDLER);


function L()
{
    return $GLOBALS['VNBIZ_LOGGER'];
}

function L_withName(string $string)
{
    $GLOBALS['VNBIZ_LOGGER'] = $GLOBALS['VNBIZ_LOGGER']->withName($string);
}

