<?php

declare(strict_types=1);

require(__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class RedisTest extends TestCase
{
    public static function setUpBeforeClass(): void {}

    public function test_redis_find_cache()
    {
        $client = new Client();

        $str = vnbiz_unique_text();

        [$status, $body] = $client->model_create('testmodela', [
            'string_1' => $str,

        ]);
        $this->assertEquals(200, $status, 'create returns 200');
        $this->assertEquals('success', $body['code'], 'create returns success');

        $model = isset($body['model']) ? $body['model'] : null;

        // test find
        // $client->model_find('testmodela', ['id' => $model['id']]);
        // test find
        [$status, $body] = $client->model_find('testmodela', ['id' => $model['id']]);
        $model = isset($body['models']) ? $body['models'][0] : null;

        $this->assertNotNull($model, 'has response model');

        $this->assertIsString($model['id'], 'model has id');

        $this->assertIsNumeric($model['created_at'], 'created_at is set');
        $this->assertTrue($str === $model['string_1'], 'string_1 is set');
    }
}
