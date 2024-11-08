<?php

declare(strict_types=1);

require(__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class OAuthTest extends TestCase
{
    public static function setUpBeforeClass(): void {
    }
    // public function setUp(): void {

    // }

    public function test_Login_OwnerPassword(): void
    {
        $client = new Client();
        [$status, $body]  = $client->callService('service_db_init_default', []);
        $client = new Client();
        [$status, $body] = $client->loginOAuthPassword('superadmin@vnbiz.com', 'superadmin');
        $this->assertEquals(200, $status, 'Login success');
        $this->assertArrayHasKey('access_token', $body, 'access_token');
        $this->assertArrayHasKey('refresh_token', $body, 'has access_token');
        $this->assertArrayHasKey('expires_in', $body, 'has expire_in');
        $this->assertIsNumeric($body['expires_in'], 'has expire_in');

        [$status, $body] = $client->refreshToken();
        $this->assertEquals(200, $status, 'Login success');
        $this->assertArrayHasKey('access_token', $body, 'access_token');
        $this->assertArrayHasKey('refresh_token', $body, 'has access_token');
        $this->assertArrayHasKey('expires_in', $body, 'has expire_in');
        $this->assertIsNumeric($body['expires_in'], 'has expire_in');
    }
}
