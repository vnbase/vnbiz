<?php

declare(strict_types=1);

require (__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    public function setUp(): void {
        Client::loginSuper();
        [$status, $body] = Client::model_create('user', [
            'email' => 'project_user@vnbiz.com',
            'password' => '12345678'
        ]);
        $this->assertEquals(200, $status, 'Create project_user');
        
        [$status, $body] = CLIENT::login("project_user@vnbiz.com", '12345678');
        $this->assertEquals(200, $status, 'Login project_user');
    }

    /**
     * Admin changes user status to inactive
     */
    public function test_Project_createAndFind(): void
    {
        [$status, $body] = Client::model_create('project', [
            'name' => 'abc def',
            "description" => '111 2222'
        ]);
        $this->assertEquals(200, $status, 'create project');

        [$status, $body] = Client::model_create('project', [
            'name' => 'ghi lmn',
            "description" => '333 444'
        ]);
        $this->assertEquals(200, $status, 'create project');

        [$status, $body] = Client::model_find('project', [], ['text_search' => 'abc', 'count' => true]);
        $this->assertEquals(200, $status, 'Can search');
        $this->assertEquals(1, sizeof($body['models']), 'found only 1 model');
        $this->assertEquals(1, $body['meta']['count'], 'count ==1 1');

    }
}
