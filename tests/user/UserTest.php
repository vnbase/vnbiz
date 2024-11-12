<?php

declare(strict_types=1);

require(__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    // public static function setUpBeforeClass(): void {
    // }

    public function test_User_FindSimple(): void
    {
        $client = new Client();
        [$status, $body]  = $client->callService('service_db_init_default', []);
        $client = new Client();

        [$httpcode, $body] = $client->model_find('user');
        $this->assertEquals(200, $httpcode,  '200 responses');
        $this->assertArrayHasKey('code', $body, 'body has code');
        $this->assertArrayHasKey('models', $body, 'body has models');
    }
    public function test_User_Register(): void
    {
        $client = new Client();

        [$httpcode, $body] = $client->model_create('user', [
            'email' => "user_1@vnbiz.com",
            'password' => '12345678'
        ]);
        $this->assertEquals(200, $httpcode,  '200 responses');
        $this->assertArrayHasKey('code', $body, 'body has code');
        $this->assertArrayHasKey('model', $body, 'body has model');
        $model = $body['model'];
        $this->assertArrayHasKey('id', $model, 'has user id');
    }

    public function test_User_PermissionDenied(): void
    {
        $client = new Client();

        [$httpcode, $body] = $client->model_find('usergroup');
        $this->assertEquals('permission', $body['code'],  'permission code.');
        $this->assertEquals(403, $httpcode,  'Permission denided.');
    }

    public function test_User_Login(): void
    {
        $client = new Client();

        [$httpcode, $body] = $client->login("user_1@vnbiz.com", 'wrongpass');
        $this->assertEquals(400, $httpcode,  'invalid params');


        [$httpcode, $body] = $client->login("user_1@vnbiz.com", '12345678');
        $this->assertEquals(200, $httpcode,  '200 responses');
        $this->assertArrayHasKey('access_token', $body, 'body has code');
        $this->assertArrayHasKey('models', $body, 'body has model');
        $this->assertEquals(1, sizeof($body['models']), 'only one model');
        [$model] = $body['models'];
        $this->assertArrayHasKey('id', $model, 'has user id');

        [$httpcode, $body] = $client->callService('service_user_me');
        $this->assertEquals(200, $httpcode,  'service_user_me: 200 responses');
        $this->assertArrayHasKey('models', $body, 'service_user_me: body has model');
        $this->assertEquals(1, sizeof($body['models']), 'service_user_me: only one model');
        [$model] = $body['models'];
        $this->assertArrayHasKey('id', $model, 'service_user_me: has user id');
    }

    /**
     * Admin changes user status to inactive
     */
    public function test_User_InActivated(): void
    {
        $super = new Client();
        $client = new Client();

        $super->loginSuper();
        [$status, $body] = $super->model_create('user', [
            'email' => 'user_inactive@vnbiz.com',
            'password' => '12345678'
        ]);
        //create user
        $this->assertEquals(200, $status,  'create user');
        $this->assertEquals(true, vnbiz_has_key($body, ['model', 'id']), 'has user in the model');
        $user = $body['model'];

        //login ok
        [$status, $body] = $client->login("user_inactive@vnbiz.com", '12345678');
        $this->assertEquals(200, $status,  'Login success');
        $this->assertArrayHasKey('access_token', $body,  'access_token responses');

        // admin changes status
        $super->loginSuper();
        [$status, $body] = $super->model_update('user', ['id' => $user['id']], ['status' => 'inactive']);
        $this->assertEquals(200, $status,  'update user by id successfully');
        $this->assertArrayHasKey('old_model', $body,  'has updated object');

        // // user get the error code
        [$httpcode, $body] = $client->refreshToken();
        $this->assertEquals(403, $httpcode,  'Invalid status');
        $this->assertEquals('user_status', $body['code'],  'user_status error');

        // // user can't login
        [$httpcode, $body] = $client->login("user_inactive@vnbiz.com", '12345678');
        $this->assertEquals(403, $httpcode,  'Invalid status');
        $this->assertEquals('user_status', $body['code'],  'user_status error');

    }
    
    /**
     * 1. User login success and get access_token & refresh_token,
     * 2. User change password
     * 3. User refresh token with the refresh_token
     * 4. Failer with 403
     **/
    public function test_Login_OwnerPassword_Changed(): void
    {
        $client = new Client();
        [$status, $body] = $client->model_create('user', [
            'email' => 'user_password_change@vnbiz.com',
            'password' => '12345678'
        ]);
        $this->assertEquals(200, $status,  'create user');

        [$status, $body] = $client->login("user_password_change@vnbiz.com", '12345678');
        $this->assertEquals(200, $status,  'Login success');
        $this->assertArrayHasKey('access_token', $body,  'access_token responses');
        $this->assertArrayHasKey('refresh_token', $body,  'refresh_token responses');

        [$status, $body] = $client->model_update('user', ['id' => $body['models'][0]['id']], ['password' => '87654321']);
        $this->assertEquals(200, $status,  'update user by id successfully');
        $this->assertArrayHasKey('old_model', $body,  'has updated object');

        [$status, $body] = $client->refreshToken();
        $this->assertEquals(401, $status,  'Invalid status');
    }
}
