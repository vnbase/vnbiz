<?php

declare(strict_types=1);

include_once("lib/Client.php");

use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    // public function setUp(): void {

    // }

    public function testUserFindSimple(): void
    {
        [$httpcode, $body] = CLIENT::model_find('user');
        $this->assertEquals(200, $httpcode,  '200 responses');
        $this->assertArrayHasKey('code', $body, 'body has code');
        $this->assertArrayHasKey('models', $body, 'body has models');
    }
    public function testUserRegister(): void
    {
        [$httpcode, $body] = CLIENT::model_create('user', [
            'email' => "admin@vnbiz.com",
            'password' => '12345678'
        ]);
        $this->assertEquals(200, $httpcode,  '200 responses');
        $this->assertArrayHasKey('code', $body, 'body has code');
        $this->assertArrayHasKey('model', $body, 'body has model');
        $model = $body['model'];
        $this->assertArrayHasKey('id', $model, 'has user id');
    }

    public function testPermissionDenied(): void
    {
        [$httpcode, $body] = CLIENT::model_find('usergroup');
        $this->assertEquals('permission', $body['code'],  'permission code.');
        $this->assertEquals(403, $httpcode,  'Permission denided.');
    }

    public function testUserLogin(): void
    {
        [$httpcode, $body] = CLIENT::login("admin@vnbiz.com", '12345678');
        $this->assertEquals(200, $httpcode,  '200 responses');
        $this->assertArrayHasKey('access_token', $body, 'body has code');
        $this->assertArrayHasKey('models', $body, 'body has model');
        $this->assertEquals(1, sizeof($body['models']), 'only one model');
        [$model] = $body['models'];
        $this->assertArrayHasKey('id', $model, 'has user id');

        [$httpcode, $body] = CLIENT::callService('service_user_me');
        $this->assertEquals(200, $httpcode,  'service_user_me: 200 responses');
        $this->assertArrayHasKey('models', $body, 'service_user_me: body has model');
        $this->assertEquals(1, sizeof($body['models']), 'service_user_me: only one model');
        [$model] = $body['models'];
        $this->assertArrayHasKey('id', $model, 'service_user_me: has user id');
    }
}
