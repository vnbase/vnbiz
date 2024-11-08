<?php

if (class_exists('Client')) {
    return;
}
ini_set('display_errors', 1);
error_reporting(E_ALL);
error_log("You messed up!", 3, "./test-error.log");
class Client
{
    private $client_access_token = null;
    private $client_refresh_token = null;
    public $user = null;

    public function log_send($form)
    {
        $payload = json_encode($form);
        // var_dump($form);
        $action = isset($form['action']) ? $form['action'] : '[action]';
        $model_name = isset($form['model_name']) ? $form['model_name'] : '[model_name]';
        // $filter = isset($form['filter']) ? json_encode($form['filter']) : '[filter]';
        // $model = isset($form['model']) ? json_encode($form['model']) : '[model]';
        print_r("< $action ON $model_name $payload \n");
    }
    public function log_receive($body)
    {
        $code = isset($body['code']) ? $body['code'] : '[code]';
        $message = isset($body['message']) ? $body['message'] : '[message]';
        $models = isset($body['models']) ? json_encode($body['models'], JSON_PRETTY_PRINT) : '[models]';
        $model = isset($body['model']) ? json_encode($body['model'], JSON_PRETTY_PRINT) : '[model]';
        $filter = isset($body['filter']) ? json_encode($body['filter'], JSON_PRETTY_PRINT) : '[filter]';
        $old_model = isset($body['old_model']) ? json_encode($body['old_model'], JSON_PRETTY_PRINT) : '[old_model]';

        print_r(">>> $code, $message, $model,  $models, $old_model, $filter \n");
    }
    /**
     * $formData = [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'file' => new CURLFile($filePath)  // Attach the file
        ];
     */
    public function REQUEST($formData, $headers = [], $url = 'http://localhost:8888/test/')
    {
        $url = 'http://localhost:8888/test/?debug=true&ns=' . vnbiz_encrypt_id(33);
        if ($this->client_access_token) {
            $headers[] = 'Content-Type: multipart/form-data';
            $headers[] = 'Authorization: Bearer ' . $this->client_access_token;
            // $headers[] = 'X-NAMESPACE: ' . vnbiz_encrypt_id(17); //TODO: Fix this
            //TODO: namespace in body doesn't work
            //TODO: namespace must be used by default, because old db don't work
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
        curl_close($ch);

        $jsonResponse = json_decode($response, true); // true = return as associative array

        // $this->log_send($formData);

        // Close the cURL session
        if (json_last_error() === JSON_ERROR_NONE) {

            // $this->log_receive($jsonResponse);

            return [$httpStatusCode, $jsonResponse];
        } else {
            throw new Error('Invalid JSON: ' . $response);
        }
    }
    public function callService($service, $params = [])
    {
        $payload = [
            'action' => $service
        ];
        foreach ($params as $key => $value) {
            $payload["params[$key]"] = $value;
        }

        return $this->REQUEST($payload);
    }

    public function model_find($model_name, $filter = [], $meta = [])
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
        return $this->REQUEST($payload);
    }

    public function model_create($model_name, $model)
    {
        $payload = [
            'action' => 'model_create',
            'model_name' => $model_name
        ];
        foreach ($model as $key => $value) {
            $payload["model[$key]"] = $value;
        }
        return $this->REQUEST($payload);
    }

    public function model_create_get($model_name, $model)
    {
        [$status, $body] = $this->model_create($model_name, $model);
        if ($status !== 200 || $body['code'] !== 'success') {
            throw new Error("Model Create Failed: " . json_encode($body));
        }
        return $body['model'];
    }

    public function model_update($model_name, $filter, $model)
    {
        $payload = [
            'action' => 'model_update',
            'model_name' => $model_name
        ];
        foreach ($filter as $key => $value) {
            $payload["filter[$key]"] = $value;
        }
        foreach ($model as $key => $value) {
            $payload["model[$key]"] = $value;
        }
        return $this->REQUEST($payload);
    }
    public function model_delete($model_name, $filter)
    {
        $payload = [
            'action' => 'model_delete',
            'model_name' => $model_name
        ];
        foreach ($filter as $key => $value) {
            $payload["filter[$key]"] = $value;
        }
        return $this->REQUEST($payload);
    }

    public function login($username, $password)
    {
        [$code, $body] = $this->callService('service_user_login', ['username' => $username, 'password' => $password]);
        $this->client_access_token = $body['access_token'];
        $this->client_refresh_token = $body['refresh_token'];
        if (isset($body['models']) && sizeof($body['models']) > 0) {
            $this->user = $body['models'][0];
        }
        return [$code, $body];
    }

    public function loginOAuthPassword($username, $password)
    {
        [$code, $body] = $this->REQUEST([
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password
        ]);
        $this->client_access_token = $body['access_token'];
        $this->client_refresh_token = $body['refresh_token'];
        if (isset($body['models']) && sizeof($body['models']) > 0) {
            $this->user = $body['models'][0];
        }
        return [$code, $body];
    }

    public function refreshToken($refresh_token = null)
    {
        $val = $this->client_refresh_token;
        if ($refresh_token !== null) {
            $val = $refresh_token;
        }

        $this->client_access_token = null;
        [$code, $body] = $this->REQUEST([
            'grant_type' => 'refresh_token',
            'refresh_token' => $val
        ]);

        if (isset($body['access_token'])) {
            $this->client_access_token = $body['access_token'];
        };
        if (isset($body['refresh_token'])) {
            $this->client_refresh_token = $body['refresh_token'];
        };

        return [$code, $body];
    }
    public function loginSuper()
    {
        [$code, $body] = $this->login('superadmin@vnbiz.com', 'superadmin');
        if ($code !== 200 || $body['code'] !== 'success') {
            throw new Error("Login Super Failed");
        }
        if (isset($body['models']) && sizeof($body['models']) > 0) {
            $this->user = $body['models'][0];
        }
        return [$code, $body];
    }

    public function logout()
    {
        unset($this->client_access_token);
        unset($this->client_refresh_token);
        $this->user = null;
    }
}
