<?php

declare(strict_types=1);

require(__DIR__ . '/../lib/Client.php');

use PHPUnit\Framework\TestCase;

final class DatascopeTest extends TestCase
{

    // userA can access ".1.11."
    public static $userA;
    public static $userA_email;
    // userA can access "'.1.22.'
    public static $userB;
    public static $userB_email;

    // public function test_create_scope_datdddda()
    public static function setUpBeforeClass(): void
    {
        $userA_email = vnbiz_unique_text() . '@aaaa.com';

        $userB_email = vnbiz_unique_text() . '@bbbb.com';

        $super = new Client();
        [$status, $body]  = $super->callService('service_db_init_default', []);

        $super->loginSuper();

        $userA = $super->model_create_get(
            'user',
            [
                'password' => '12345678',
                'email' => $userA_email
            ]
        );

        //create user group and add user to group
        $groupA = $super->model_create_get(
            'usergroup',
            [
                'name' => 'groupa',
                'permissions_scope' => '{".1.11.": true}'
            ]
        );
        $super->model_create_get(
            'useringroup',
            [
                'user_id' => $userA['id'],
                'usergroup_id' => $groupA['id']
            ]
        );

        $userB = $super->model_create_get(
            'user',
            [
                'password' => '12345678',
                'email' => $userB_email
            ]
        );

        //create user group and add user to group
        $groupB =  $super->model_create_get(
            'usergroup',
            [
                'name' => 'groupb',
                'permissions_scope' => '{".1.22.": true}'
            ]
        );
        $super->model_create_get(
            'useringroup',
            [
                'user_id' => $userB['id'],
                'usergroup_id' => $groupB['id']
            ]
        );

        DatascopeTest::$userA_email = $userA_email;
        DatascopeTest::$userB_email = $userB_email;

        DatascopeTest::$userB = new Client();
        DatascopeTest::$userB->login($userA_email, '12345678');

        DatascopeTest::$userB = new Client();
        DatascopeTest::$userB->login($userB_email, '12345678');
    }
    public function test_create_scope_data()
    {
        $userA = new Client();
        [$status] = $userA->login(DatascopeTest::$userA_email, '12345678');
        $this->assertEquals(200, $status);
        $userA->callService('service_user_me');

        $userB = new Client();
        [$status] = $userB->login(DatascopeTest::$userB_email, '12345678');
        $this->assertEquals(200, $status);


        [$status, $result] = $userA->model_create(
            'testscope',
            [
                'datascope' => '.1.11.',
                'string_1' => 'string_1 ' . vnbiz_unique_text(),
                'string_2' => 'test2',
                'int_1' => 234
            ]
        );
        $this->assertEquals(200, $status, 'userA can create data with datascope .1.11.');


        [$status, $result] = $userB->model_create(
            'testscope',
            [
                'datascope' => '.1.22.1.',
                'string_1' => 'string_1 ' . vnbiz_unique_text(),
                'string_2' => 'test2',
                'int_1' => 234
            ]
        );
        $this->assertEquals(200, $status, 'userB can create data with datascope .1.22.1.');

        // user a can't create data with datascope .1.22.1.
        [$status, $result] = $userA->model_create(
            'testscope',
            [
                'datascope' => '.1.22.1.',
                'string_1' => 'string_1 ' . vnbiz_unique_text(),
                'string_2' => 'test2',
                'int_1' => 234
            ]
        );
        $this->assertEquals(403, $status, 'userA can not create data with datascope .1.22.1.');

        // user a find nothing on datascope .1.22.1.1
        [$status, $result] = $userA->model_find(
            'testscope',
            [
                'datascope' => '.1.22.1.'
            ]
        );
        $this->assertEquals(403, $status, 'userA are not allowed to find in .1.22.1.');

        // by finding, userA's filter auto add datascope .1.11.
        [$status, $result] = $userA->model_find(
            'testscope', [], ['text_search' => 'string_1']
        );
        $this->assertEquals(200, $status, 'userA can find data with datascope .1.11.');
        
        $this->assertIsArray($result['filter'], 'filter are added');
        $this->assertArrayHasKey('datascope', $result['filter'], 'filter are added');
        $this->assertEquals('.1.11.', $result['filter']['datascope'][0], 'filter are added');
    }
}
