<?php

declare(strict_types=1);

require(__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class CoreTest extends TestCase
{
    public static function setUpBeforeClass(): void {}
    public function test_model_create_find_update()
    {
        $client = new Client();

        [$status, $body] = $client->model_create('testmodela', [
            // 'model_id_1' => 'string_1',
            // 'model_id_2' => 'model_id_2',
            'string_1' => 'string_1',
            'bool_1' => true,
            'date_1' => '2024-11-08',
            'datetime_1' => 1731042834887,
            'mail_1' => 'abc@gmail.com',
            'slug_1' => 'this-is-slug',
            'text_1' => 'text_1',
            'json_1' => '{"a":1}',
            'uint_1' => 1,
            'int_1' => 1,
            'float_1' => 1.1,
            'enum_1' => 'value_1',
            'status' => 'status_1',
            'testmodelb_id' => null,
            // 'created_by' => 'ignore',
            // 'updated_by' => 'ignore',
            // 'deleted_by' => 'ignore',
            'password_1' => '123456',

        ]);
        $this->assertEquals(200, $status, 'create returns 200');
        $this->assertEquals('success', $body['code'], 'create returns success');

        $model = isset($body['model']) ? $body['model'] : null;
        $this->assertNotNull($model, 'has response model');

        $this->assertIsString($model['id'], 'model has id');
        $this->assertIsNumeric($model['created_at'], 'created_at is set');

        $this->assertTrue('string_1' === $model['string_1'], 'string_1 is set');
        $this->assertTrue(true === $model['bool_1'], 'bool_1 is set');
        $this->assertTrue('2024-11-08' === $model['date_1'], 'date_1 is set');
        $this->assertTrue(1731042834887 === $model['datetime_1'], 'datetime_1 is set');
        $this->assertTrue('abc@gmail.com' === $model['mail_1'], 'mail_1 is set');
        $this->assertTrue('this-is-slug' === $model['slug_1'], 'slug_1 is set');
        $this->assertTrue('text_1' === $model['text_1'], 'text_1 is set');
        $this->assertTrue('{"a":1}' === $model['json_1'], 'json_1 is set');
        $this->assertTrue(1 === $model['uint_1'], 'uint_1 is set');
        $this->assertTrue(1 === $model['int_1'], 'int_1 is set');
        $this->assertTrue(1.1 === $model['float_1'], 'float_1 is set');
        $this->assertTrue('value_1' === $model['enum_1'], 'enum_1 is set');
        $this->assertTrue('status_1' === $model['status'], 'status is set');
        $this->assertNull($model['testmodelb_id'], 'testmodelb_id is set');
        $this->assertNull($model['created_by'], 'created_by is set');
        $this->assertNull($model['updated_by'], 'updated_by is set');
        $this->assertNull($model['deleted_by'], 'deleted_by is set');
        $this->assertTrue(strlen($model['password_1']) == 6, 'password is set');

        // test find
        $models = $client->model_find('testmodela', ['id' => $model['id']]);
        $model = $models;

        $model = isset($body['model']) ? $body['model'] : null;
        $this->assertNotNull($model, 'has response model');

        $this->assertIsString($model['id'], 'model has id');
        $this->assertIsNumeric($model['created_at'], 'created_at is set');

        $this->assertTrue('string_1' === $model['string_1'], 'string_1 is set');
        $this->assertTrue(true === $model['bool_1'], 'bool_1 is set');
        $this->assertTrue('2024-11-08' === $model['date_1'], 'date_1 is set');
        $this->assertTrue(1731042834887 === $model['datetime_1'], 'datetime_1 is set');
        $this->assertTrue('abc@gmail.com' === $model['mail_1'], 'mail_1 is set');
        $this->assertTrue('this-is-slug' === $model['slug_1'], 'slug_1 is set');
        $this->assertTrue('text_1' === $model['text_1'], 'text_1 is set');
        $this->assertTrue('{"a":1}' === $model['json_1'], 'json_1 is set');
        $this->assertTrue(1 === $model['uint_1'], 'uint_1 is set');
        $this->assertTrue(1 === $model['int_1'], 'int_1 is set');
        $this->assertTrue(1.1 === $model['float_1'], 'float_1 is set');
        $this->assertTrue('value_1' === $model['enum_1'], 'enum_1 is set');
        $this->assertTrue('status_1' === $model['status'], 'status is set');
        $this->assertNull($model['testmodelb_id'], 'testmodelb_id is set');
        $this->assertNull($model['created_by'], 'created_by is set');
        $this->assertNull($model['updated_by'], 'updated_by is set');
        $this->assertNull($model['deleted_by'], 'deleted_by is set');
        $this->assertTrue(strlen($model['password_1']) == 6, 'password is set');


        // test update
        [$status, $result] = $client->model_update('testmodela', [
            'id' => $model['id']
        ], [
            'string_1' => 'string_1_a',
            'bool_1' => false,
            'date_1' => '2024-11-09',
            'datetime_1' => 1731042834888,
            'mail_1' => 'abc_a@gmail.com',
            'slug_1' => 'this-is-slug-a',
            'text_1' => 'text_1_a',
            'json_1' => '{"b":1}',
            'uint_1' => 2,
            'int_1' => -1,
            'float_1' => 2.2,
            'enum_1' => 'value_2',
            'status' => 'status_2',
            'testmodelb_id' => null,
            // 'created_by' => 'ignore',
            // 'updated_by' => 'ignore',
            // 'deleted_by' => 'ignore',
            'password_1' => '123456_a',
        ]);

        $this->assertEquals(200, $status, 'update returns 200');
        $this->assertEquals('success', $result['code'], 'update returns success');
        $this->assertNotNull($result['model'], 'has result');
        $model = $result['model'];
        $old_model = $result['old_model'];

        $this->assertEquals('string_1_a', $model['string_1'], 'string_1 is has new value');
        $this->assertTrue(false === $model['bool_1'], 'bool_1 is has new value');
        $this->assertEquals('2024-11-09', $model['date_1'], 'date_1 is has new value');
        $this->assertTrue(1731042834888 === $model['datetime_1'], 'datetime_1 is has new value');
        $this->assertEquals('abc_a@gmail.com', $model['mail_1'], 'mail_1 is has new value');
        $this->assertEquals('this-is-slug-a', $model['slug_1'], 'slug_1 is has new value');
        $this->assertEquals('text_1_a', $model['text_1'], 'text_1 is has new value');
        $this->assertEquals('{"b":1}', $model['json_1'], 'json_1 is has new value');
        $this->assertTrue(2 === $model['uint_1'], 'uint_1 is has new value');
        $this->assertTrue(-1 === $model['int_1'], 'int_1 is has new value');
        $this->assertTrue(2.2 === $model['float_1'], 'float_1 is has new value');
        $this->assertEquals('value_2', $model['enum_1'], 'enum_1 is has new value');
        $this->assertEquals('status_2', $model['status'], 'status is has new value');
        $this->assertNull($model['testmodelb_id'], 'testmodelb_id is has new value');
        $this->assertNull($model['created_by'], 'created_by is has new value');
        $this->assertNull($model['updated_by'], 'updated_by is has new value');
        $this->assertNull($model['deleted_by'], 'deleted_by is has new value');
        $this->assertTrue(strlen($model['password_1']) == 6, 'password is has new value');

        $this->assertTrue('string_1' === $old_model['string_1'], 'string_1 is set');
        $this->assertTrue(true === $old_model['bool_1'], 'bool_1 is set');
        $this->assertTrue('2024-11-08' === $old_model['date_1'], 'date_1 is set');
        $this->assertTrue(1731042834887 === $old_model['datetime_1'], 'datetime_1 is set');
        $this->assertTrue('abc@gmail.com' === $old_model['mail_1'], 'mail_1 is set');
        $this->assertTrue('this-is-slug' === $old_model['slug_1'], 'slug_1 is set');
        $this->assertTrue('text_1' === $old_model['text_1'], 'text_1 is set');
        $this->assertTrue('{"a":1}' === $old_model['json_1'], 'json_1 is set');
        $this->assertTrue(1 === $old_model['uint_1'], 'uint_1 is set');
        $this->assertTrue(1 === $old_model['int_1'], 'int_1 is set');
        $this->assertTrue(1.1 === $old_model['float_1'], 'float_1 is set');
        $this->assertTrue('value_1' === $old_model['enum_1'], 'enum_1 is set');
        $this->assertTrue('status_1' === $old_model['status'], 'status is set');
        $this->assertNull($old_model['testmodelb_id'], 'testmodelb_id is set');
        $this->assertNull($old_model['created_by'], 'created_by is set');
        $this->assertNull($old_model['updated_by'], 'updated_by is set');
        $this->assertNull($old_model['deleted_by'], 'deleted_by is set');
        $this->assertTrue(strlen($old_model['password_1']) == 6, 'password is set');


        // test delete
        [$status, $result] = $client->model_delete('testmodela', [
            'id' => $old_model['id']
        ]);
        $old_model = $result['old_model'];
        
        $this->assertEquals('string_1_a', $old_model['string_1'], 'string_1 is has new value');
        $this->assertTrue(false === $old_model['bool_1'], 'bool_1 is has new value');
        $this->assertEquals('2024-11-09', $old_model['date_1'], 'date_1 is has new value');
        $this->assertTrue(1731042834888 === $old_model['datetime_1'], 'datetime_1 is has new value');
        $this->assertEquals('abc_a@gmail.com', $old_model['mail_1'], 'mail_1 is has new value');
        $this->assertEquals('this-is-slug-a', $old_model['slug_1'], 'slug_1 is has new value');
        $this->assertEquals('text_1_a', $old_model['text_1'], 'text_1 is has new value');
        $this->assertEquals('{"b":1}', $old_model['json_1'], 'json_1 is has new value');
        $this->assertTrue(2 === $old_model['uint_1'], 'uint_1 is has new value');
        $this->assertTrue(-1 === $old_model['int_1'], 'int_1 is has new value');
        $this->assertTrue(2.2 === $old_model['float_1'], 'float_1 is has new value');
        $this->assertEquals('value_2', $old_model['enum_1'], 'enum_1 is has new value');
        $this->assertEquals('status_2', $old_model['status'], 'status is has new value');
        $this->assertNull($old_model['testmodelb_id'], 'testmodelb_id is has new value');
        $this->assertNull($old_model['created_by'], 'created_by is has new value');
        $this->assertNull($old_model['updated_by'], 'updated_by is has new value');
        $this->assertNull($old_model['deleted_by'], 'deleted_by is has new value');
        $this->assertTrue(strlen($old_model['password_1']) == 6, 'password is has new value');
    }
}
