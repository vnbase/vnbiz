<?php

declare(strict_types=1);

require(__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class RefFieldTest extends TestCase
{
    public static function setUpBeforeClass(): void {}
    public function test_ref_and_back_ref()
    {
        $client = new Client();

        $modela_1 = $client->model_create_get('testrefa', [
            'string_1' => 'modela_1',
            'password_1' => '0000000'
        ]);

        $modela_2 = $client->model_create_get('testrefa', [
            'string_1' => 'modela_2',
            'parent_id' => $modela_1['id']
        ]);

        $modelb_1 = $client->model_create_get('testrefb', [
            'string_1' => 'active',
            'testrefa_id' => $modela_1['id'],
            'password_1' => '1111111'
        ]);

        $modelb_2 = $client->model_create_get('testrefb', [
            'string_1' => 'dont count this',
            'testrefa_id' => $modela_1['id'],
            'password_1' => '222222'
        ]);

        echo "modela_a id: " . vnbiz_decrypt_id($modela_1['id']) . "\n";
        echo "modela_b id: " . vnbiz_decrypt_id($modela_2['id']) . "\n";
        echo "modelb_1 id: " . vnbiz_decrypt_id($modelb_1['id']) . "\n";
        echo "modelb_2 id: " . vnbiz_decrypt_id($modelb_2['id']) . "\n";


        [$status, $body] = $client->model_find('testrefa', [
            'id' => $modela_1['id']
        ]);
        $this->assertEquals($status, 200);
        $model = $body['models'][0];

        $this->assertEquals(1, $model['testrefb_count'], 'has one testrefb');

        // update $model_b2 to active
        $client->model_update('testrefb', ['id' => $modelb_2['id']], [
            'string_1' => 'active'
        ]);

        [$status, $body] = $client->model_find('testrefa', [
            'id' => $modela_1['id']
        ]);
        $this->assertEquals($status, 200);
        $model = $body['models'][0];

        $this->assertEquals(2, $model['testrefb_count'], 'has one testrefb');

        // update $model_b2 refs to $modela_2
        $client->model_update('testrefb', ['id' => $modelb_2['id']], [
            'testrefa_id' => $modela_2['id']
        ]);

        // modela_1 should have 1 testrefb
        [$status, $body] = $client->model_find('testrefa', [
            'id' => $modela_1['id']
        ]);
        $this->assertEquals($status, 200);
        $model = $body['models'][0];
        
        $this->assertEquals(1, $model['testrefb_count'], 'has one testrefb');
        
        // modela_2 should have 1 testrefb
        [$status, $body] = $client->model_find('testrefa', [
            'id' => $modela_2['id']
        ]);
        $this->assertEquals($status, 200);
        $model = $body['models'][0];
        
        $this->assertEquals(1, $model['testrefb_count'], 'has one testrefb');

        // delete modelb_2
        $client->model_delete('testrefb', ['id' => $modelb_2['id']]);
        // modela_2 should have 0 testrefb
        [$status, $body] = $client->model_find('testrefa', [
            'id' => $modela_2['id']
        ]);
        $this->assertEquals($status, 200);
        $model = $body['models'][0];
        
        $this->assertEquals(0, $model['testrefb_count'], 'has one testrefb');
    }
}
