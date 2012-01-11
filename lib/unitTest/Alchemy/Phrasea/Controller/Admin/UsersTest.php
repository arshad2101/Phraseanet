<?php

namespace Alchemy\Phrasea\Controller\Admin;

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

require_once __DIR__ . '/../../../../../Alchemy/Phrasea/Controller/Admin/Users.php';

/**
 * Test class for Users.
 * Generated by PHPUnit on 2012-01-11 at 18:25:04.
 */
class ControllerUsersTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

  /**
   * As controllers use WebTestCase, it requires a client 
   */
  protected $client;
  /**
   * If the controller tests require some records, specify it her
   * 
   * For example, this will loacd 2 records 
   * (self::$record_1 and self::$record_2) :
   * 
   * $need_records = 2; 
   * 
   */
  protected static $need_records = false;

  /**
   * The application loader
   */
  public function createApplication()
  {
    return require __DIR__ . '/../../../../../Alchemy/Phrasea/Application/Admin.php';
  }
  
  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public function tearDown()
  {
    $this->feed->delete();
    parent::tearDown();
  }

  /**
   * Default route test
   */
  public function testRouteSlash()
  {
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

}
