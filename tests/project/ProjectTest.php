<?php

declare(strict_types=1);

require (__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    public static function setUpBeforeClass(): void {
        $client = new Client();
        [$status, $body]  = $client->callService('service_db_init_default', []);
        [$status, $body] = $client->model_create('user', [
            'email' => 'project_user@vnbiz.com',
            'password' => '12345678'
        ]);
    }

    public function test_admin_create_project(): void
    {
        $client = new Client();

        $client->loginSuper();

        [$status, $body] = $client->model_create('project', [
            'name' => 'abc lmn',
            "description" => '333 444'
        ]);
        $this->assertEquals(200, $status, 'create project');
    }

    public function test_Project_createAndFind(): void
    {
        $client = new Client();

        [$status, $body] = $client->login("project_user@vnbiz.com", '12345678');
        $this->assertEquals(200, $status, 'Login project_user');

        [$status, $body] = $client->model_create('project', [
            'name' => 'abc def',
            "description" => '111 2222'
        ]);
        $this->assertEquals(200, $status, 'create project');

        [$status, $body] = $client->model_create('project', [
            'name' => 'ghi lmn',
            "description" => '333 444'
        ]);
        $this->assertEquals(200, $status, 'create project');

        //user can't see admin project
        [$status, $body] = $client->model_find('project', [], ['text_search' => 'abc', 'count' => true]);

        $this->assertEquals(200, $status, 'Can search');
        $this->assertEquals(1, sizeof($body['models']), 'found only 1 model');
        $this->assertEquals(1, $body['meta']['count'], 'count ==1 1');
    }

    public function test_admin_see_other_projects(): void
    {
        $client = new Client();

        $client->loginSuper();

        [$status, $body] = $client->model_find('project', [], ['text_search' => 'abc', 'count' => true]);
        $this->assertEquals(200, $status, 'Can search');
        $this->assertEquals(2, sizeof($body['models']), 'find 2 models');
        $this->assertEquals(2, $body['meta']['count'], 'count == 2');
    }
}
