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


    public function test_customer_can_order(): void
    {
        $admin = new Client();
        $admin->loginSuper();

        $product1 = $admin->model_create_get('product', [
            'name' => 'product 1',
            'price' => 100
        ]);
        $product1_option1 = $admin->model_create_get('productoption', [
            'name' => 'option 1',
            'product_id' => $product1['id'],
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

        [$status, $body] = $client->model_update('productorderitem', [
            'id' => $order_item['id'],
            'created_by' => $client->user['id']
        ], [
            'quantity' => 3
        ]);
        $this->assertEquals(200, $status, 'Can update order item quantity');

        [$status, $body] = $client->model_update('productorder', [
            'id' => $order['id'],
            'created_by' => $client->user['id']
        ], [
            'status' => 'ordered'
        ]);
        $this->assertEquals(200, $status, 'Can update order status to ordered');

        [$status, $body] = $client->model_update('productorderitem', [
            'id' => $order_item['id'],
            'created_by' => $client->user['id']
        ], [
            'quantity' => 1
        ]);

        $this->assertEquals(400, $status, 'Cannot add item when not in draft');
        $this->assertEquals('invalid_status', $body['code'], 'Customer can NOT change item when not in draft');

        [$status, $body] = $client->model_find('productorder', [
            'created_by' => $client->user['id']
        ], ['ref' => true]);
        $this->assertEquals(200, $status, 'as a customer, I can find my orders');
        $this->assertTrue(count($body['models']) >= 1, 'Can find orders');

        [$status, $body] = $client->model_find('productorderitem', [
            'created_by' => $client->user['id']
        ], ['ref' => true]);
        $this->assertEquals(200, $status, 'as a customer, I can find my order items');
        $this->assertTrue(count($body['models']) >= 1, 'I can find my order items');
    }


    public function test_customer_find_order(): void
    {
        $client = new Client();
        $username = 'shooper.' . vnbiz_unique_text();
        $password = '12345678';

        [$status, $body] = $client->model_create('user', [
            'username' => $username,
            'password' => $password
        ]);
        
        $client->login($username, $password);

        //==========

        [$status] = $client->model_find('productorder');
        $this->assertEquals(403, $status, 'Iam not allowed to see all orders');

        [$status] = $client->model_find('productorderitem');
        $this->assertEquals(403, $status, 'Iam not allowed to see all orders');

        [$status] = $client->model_find('productorderpromotion');
        $this->assertEquals(403, $status, 'Iam not allowed to see all orders');
    }

}
