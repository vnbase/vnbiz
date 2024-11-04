<?php



class Client
{
    /**
     * $formData = [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'file' => new CURLFile($filePath)  // Attach the file
        ];
     */
    static function REQUEST($formData, $headers = [], $url = 'http://localhost:80/test/')
    {

        if ($GLOBALS['client_access_token']) {
            $headers[] = 'Content-Type: multipart/form-data';
            $headers[] = 'Authorization: Bearer ' . $GLOBALS['client_access_token'];
        }

        // Initialize a cURL session
        $ch = curl_init();

        // Set the URL where the request is to be sent
        curl_setopt($ch, CURLOPT_URL, $url);

        // Set the HTTP method to POST
        curl_setopt($ch, CURLOPT_POST, true);

        // Pass the form data as a URL-encoded string (or array)
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


        // Set headers (if needed). For form-data, content type is not necessary
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'Content-Type: multipart/form-data',
        // ]);

        // Return the transfer as a string instead of outputting it directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the cURL session and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if ($response === false) {
            curl_close($ch);
            throw new Error('cURL Error: ' . curl_error($ch));
        }

        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // print_r("<<< " . json_encode($formData) . "\n");
        // print_r(">>> " . $response . "\n");
        curl_close($ch);

        $jsonResponse = json_decode($response, true); // true = return as associative array

        // Close the cURL session
        if (json_last_error() === JSON_ERROR_NONE) {
            return [$httpStatusCode, $jsonResponse];
        } else {
            throw new Error('Invalid JSON: ' . $response);
        }
    }
    static function callService($service, $params = [])
    {
        $payload = [
            'action' => $service
        ];
        foreach ($params as $key => $value) {
            $payload["params[$key]"] = $value;
        }

        return Client::REQUEST($payload);
    }

    static function model_find($model_name, $filter = [], $meta = [])
    {
        $payload = [
            'action' => 'model_find',
            'model_name' => $model_name,
        ];
        foreach ($filter as $key => $value) {
            $payload["filter[$key]"] = $value;
        }
        foreach ($meta as $key => $value) {
            $payload["meta[$key]"] = $value;
        }
        return Client::REQUEST($payload);
    }

    static function model_create($model_name, $model)
    {
        $payload = [
            'action' => 'model_create',
            'model_name' => $model_name
        ];
        foreach ($model as $key => $value) {
            $payload["model[$key]"] = $value;
        }
        return Client::REQUEST($payload);
    }

    static function login($email, $password)
    {
        [$code, $body] = Client::callService('service_user_login', ['email' => $email, 'password' => $password]);
        if ($code !== 200 || !isset($body['access_token'])) {
            throw new Error("Login Failed");
        }
        $GLOBALS['client_access_token'] = $body['access_token'];
        return [$code, $body];
    }

    static function logout()
    {
        unset($GLOBALS['client_access_token']);
    }
}
