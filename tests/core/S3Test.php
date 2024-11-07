<?php

declare(strict_types=1);

require(__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class S3Test extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $client = new Client();
        [$status, $body]  = $client->callService('service_db_init_default', []);
    }
    public function test_s3_upload_image_from_url()
    {
        $client = new Client();

        [$status, $body] = $client->model_create('testmodela', [
            'string_1' => 'image test',
            'image_1' => 'https://i.pravatar.cc/300'
        ]);
        $this->assertEquals(200, $status, 'upload image returns 200');
        $this->assertEquals('success', $body['code'], 'upload image returns success');

        $model = isset($body['model']) ? $body['model'] : null;
        $this->assertNotNull($model, 'has response model');

        $this->assertArrayHasKey('id', $model, 'model has id');

        $this->assertArrayHasKey('created_at', $model, 'model has created_at');
        $this->assertIsNumeric($model['created_at'], 'created_at is number');

        $this->assertArrayHasKey('image_1', $model, 'model has image_1');

        $this->assertArrayHasKey('@image_1', $model, 'model has image_1');
        $this->assertIsArray($model['@image_1'], 'image_1 is array');

        $this->assertArrayHasKey('path_thumbnail', $model['@image_1'], 'image_1 has path_thumbnail');
        $this->assertArrayHasKey('url_thumbnail', $model['@image_1'], 'image_1 has url_thumbnail');

        $this->assertArrayHasKey('path_0', $model['@image_1'], 'image_1 has path_0');
        $this->assertArrayHasKey('url_0', $model['@image_1'], 'image_1 has url_0');

        [$status, $body] = $client->model_find('testmodela', ['id' => $model['id']]);
        $this->assertEquals(200, $status, 'find model returns 200');
        $this->assertArrayHasKey('models', $body, 'has mdels');
        $models = $body['models'];
        $this->assertEquals(1, count($models), 'has 1 model');
        $model = $models[0];
        $this->assertArrayHasKey('@image_1', $model, 'model has @image_1');
        $this->assertArrayHasKey('url_0', $model['@image_1'], 'has s3 url');
        $this->assertIsString($model['@image_1']['url_0'], 'has s3 url');
    }
}
