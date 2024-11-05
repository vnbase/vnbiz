<?php

use VnBiz\VnBizError;
use League\OAuth2\Client\Provider\Google;


function vnbiz_getIPAddress()
{
    //whether ip is from the share internet  
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    //whether ip is from the proxy  
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    //whether ip is from the remote address  
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function vnbiz_getBrowserInfo()
{
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version = "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    } elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }

    $ub = '';
    // Next get the name of the useragent yes seperately and for good reason
    if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    } elseif (preg_match('/Firefox/i', $u_agent)) {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    } elseif (preg_match('/Chrome/i', $u_agent)) {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    } elseif (preg_match('/Safari/i', $u_agent)) {
        $bname = 'Apple Safari';
        $ub = "Safari";
    } elseif (preg_match('/Opera/i', $u_agent)) {
        $bname = 'Opera';
        $ub = "Opera";
    } elseif (preg_match('/Netscape/i', $u_agent)) {
        $bname = 'Netscape';
        $ub = "Netscape";
    }

    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
        ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }

    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
            $version = $matches['version'][0];
        } else {
            $version = $matches['version'][1];
        }
    } else {
        $version = $matches['version'][0];
    }

    // check if we have a number
    if ($version == null || $version == "") {
        $version = "?";
    }

    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
}


function vnbiz_init_module_useractivity()
{

    vnbiz_model_add('useractivity')
        ->ui([
            'icon' => 'sensor_occupied',
            'title' => 'action',
            'subtitle' => 'ip'
        ])
        ->ref('user_id', 'user')
        ->string('action', 'model_name')
        ->string('ip')
        ->json('context')
        ->string('browser', 'browser_version', 'platform')
        ->uint('process_time', 'request_body_size')
        ->int('http_response_code')
        ->string('code')
        ->text('error', 'message', 'stack', 'text_search')
        ->no_update()
        ->no_delete()
        ->read_permission('super', 'user_read')
        ->index('fast', ['created_at', 'user_id', 'action', 'model_name'])
    ;

    vnbiz_add_action('web_before', function (&$context) {
        $GLOBALS['start_at'] = round(microtime(true) * 1000);
    });
    vnbiz_add_action('web_after', function (&$context) {
        $user = vnbiz_user();
        if (!$user) {
            return;
        }
        if (!isset($context['action'])) {
            return;
        }
        $c = [];
        if (isset($context['filter'])) {
            $c['filter'] = $context['filter'];
        }
        $text_search = null;
        if (isset($context['meta'])) {
            $text_search = vnbiz_get_var($context['meta']['text_search']);
        }
        $ip = vnbiz_getIPAddress();
        $browser_info = vnbiz_getBrowserInfo();
        $browser = $browser_info['name'];
        $browser_version = $browser_info['version'];
        $platform = $browser_info['platform'];
        $process_time = (int) ((round(microtime(true) * 1000)) - $GLOBALS['start_at']);
        $http_response_code = http_response_code();
        $request_body_size = (int) vnbiz_get_var($_SERVER['CONTENT_LENGTH'], null);

        vnbiz_model_create('useractivity', [
            'user_id' => $user['id'],
            'action' => $context['action'],
            'model_name' => vnbiz_get_var($context['model_name'], null),
            'context' => $c,
            // 'filter_id' => 
            'ip' => $ip,
            'text_search' => $text_search,
            'browser' => $browser,
            'browser_version' => $browser_version,
            'platform' => $platform,
            'process_time' => $process_time,
            'http_response_code' => $http_response_code,
            'request_body_size' => $request_body_size,
            'code' => vnbiz_get_var($context['code'], null),
            'error' => vnbiz_get_var($context['error'], null),
            'message' => vnbiz_get_var($context['message'], null),
            'stack' => vnbiz_get_var($context['stack'], null),
        ], true);
    });
}
