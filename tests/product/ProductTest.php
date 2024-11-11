<?php

declare(strict_types=1);

require(__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public static function setUpBeforeClass(): void {
        $client = new Client();
        [$status, $body]  = $client->callService('service_db_init_default', []);
    }

    public function test_normal_user_can_order(): void
    {
        $admin = new Client();
        $admin->loginSuper();

        $product1 = $admin->model_create_get('product', [
            'name' => 'product 1',
            'price' => 100
        ]);
        $product1_option1 = $admin->model_create_get('productoption', [
            'name' => 'option 1',
            'product_id' => 1,
            'price' => 10
        ]);

        $client = new Client();
        $username = 'shooper.' . vnbiz_unique_text();
        $password = '12345678';

        [$status, $body] = $client->model_create('user', [
            'username' => $username,
            'password' => $password
        ]);
        
        $this->assertEquals(200, $status, 'Can create user');

        $client->login($username, $password);

        $contact = $client->model_create_get('contact', [
            'display_name' => 'Shooper'
        ]);

        $order = $client->model_create_get('productorder', [
            'contact_id' => $contact['id'],
            'marketing_source_id' => 'facebook'
        ]);

        $this->assertTrue('draft' === $order['status'], 'Default status is draft');

        $order_item = $client->model_create_get('productorderitem', [
            'productorder_id' => $order['id'],
            'product_id' => $product1['id'],
            'productoption_id' => $product1_option1['id'],
            'quantity' => 2
        ]);

        

    }

    // public function test_admin_see_other_projects(): void
    // {
    //     $client = new Client();

    //     $client->loginSuper();

    //     [$status, $body] = $client->model_find('project', [], ['text_search' => 'abc', 'count' => true, 'limit' => 100]);
    //     $this->assertEquals(200, $status, 'Can search');
    //     $this->assertEquals(2, sizeof($body['models']), 'find 2 models');
    //     $this->assertEquals(2, $body['meta']['count'], 'count == 2');
    // }
}
