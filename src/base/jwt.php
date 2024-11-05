<?php



/**
 * Sign - Static method to generate token
 *  
 * @param array $payload
 * @param string $key - The signature key
 * @param int $expire - (optional) Max age of token in seconds. Leave it blank for no expiration.
 * 
 * @return string token
 */
// PHP has no base64UrlEncode function, so let's define one that
// does some magic by replacing + with -, / with _ and = with ''.
// This way we can pass the string within URLs without
// any URL encoding.
function vnbiz_base64UrlEncode($text)
{
    return str_replace(
        ['+', '/', '='],
        ['-', '_', ''],
        base64_encode($text)
    );
}

/**
 * expire in second
 */
function vnbiz_token_sign($payload, $key, $expireTimeInSecond = null)
{

    // Header
    $headers = ['algo' => 'HS256', 'type' => 'JWT', "alg" => "HS256"];
    $headers_encoded = vnbiz_base64UrlEncode(json_encode($headers,));

    // Payload
    $payload['iat'] = time();
    if ($expireTimeInSecond) {
        $payload['exp'] = $expireTimeInSecond;
    }
    $payload_encoded = vnbiz_base64UrlEncode(json_encode($payload));

    // Signature
    $signature = hash_hmac('SHA256', $headers_encoded . '.' . $payload_encoded, $key);
    $signature_encoded = vnbiz_base64UrlEncode($signature);

    // Token
    $token = $headers_encoded . '.' . $payload_encoded . '.' . $signature_encoded;

    return $token;
}

/**
 * Verify - Static method verify token
 * 
 * @param string $token
 * @param string $key - The signature key
 * 
 * @return boolean false if token is invalid or expired
 * @return array payload
 */
function vnbiz_token_verify($token, $key)
{

    // Break token parts
    $token_parts = explode('.', $token);
    if (sizeof($token_parts) !== 3) {
        return false;
    }

    // Verify Signature
    $signature = vnbiz_base64UrlEncode(hash_hmac('SHA256', $token_parts[0] . '.' . $token_parts[1], $key));

    if ($signature != $token_parts[2]) {
        return false;
    }

    // Decode headers & payload
    // $headers = json_decode(base64_decode($token_parts[0]), true);
    $payload = json_decode(base64_decode($token_parts[1]), true);

    // Verify validity
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }

    // If token successfully verified
    return $payload;
}
