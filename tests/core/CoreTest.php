<?php

declare(strict_types=1);

require(__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class CoreTest extends TestCase
{
    public function test_model_create()
    {
        $client = new Client();

        [$status, $body] = $client->model_create('testmodelb', [
            'name' => 'a'
        ]);
        $this->assertEquals(200, $status, 'create returns 200');
        $this->assertEquals('success', $body['code'], 'create returns success');

        $model = isset($body['model']) ? $body['model'] : null;
        $this->assertNotNull($model, 'has response model');

        $this->assertArrayHasKey('id', $model, 'model has id');

        $this->assertArrayHasKey('created_at', $model, 'model has created_at');
        $this->assertIsNumeric($model['created_at'], 'created_at is number');

    }
}
