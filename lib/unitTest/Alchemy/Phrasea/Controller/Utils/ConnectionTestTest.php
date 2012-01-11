<?php

namespace Alchemy\Phrasea\Controller\Utils;

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';

require_once __DIR__ . '/../../../../../Alchemy/Phrasea/Controller/Utils/ConnectionTest.php';

/**
 * Test class for ConnectionTest.
 * Generated by PHPUnit on 2012-01-11 at 18:20:20.
 */
class ControllerConnectionTestTest extends \PhraseanetWebTestCaseAbstract
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

