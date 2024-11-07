<?php

declare(strict_types=1);

require(__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class CoreTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
    }
    public function test_model_create()
    {
        $client = new Client();

        [$status, $body] = $client->model_create('testmodela', [
            // 'model_id_1' => 'string_1',
            // 'model_id_2' => 'model_id_2',
            'string_1' => 'string_1',
            'string_2' => 'string_2',
            // 'created_by' => 'INVALID_VALUE',
        ]);
        $this->assertEquals(200, $status, 'create returns 200');
        $this->assertEquals('success', $body['code'], 'create returns success');

        $model = isset($body['model']) ? $body['model'] : null;
        $this->assertNotNull($model, 'has response model');

        $this->assertIsString($model['id'], 'model has id');
        $this->assertIsNumeric($model['created_at'], 'created_at is set');
    }
}
