<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';
/**
 * Always load the controller file for CodeCoverage 
 */
require_once __DIR__ . '/../../../../../Alchemy/Phrasea/Controller/My/Controller.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * 
 * This class is a BoilerPlate for a Controller Test
 *
 *  - You should extends PhraseanetWebTestCaseAuthenticatedAbstract if the 
 * controller required authentication
 * 
 *  - The Class Name should end with "Test" to be detected by 
 *  
 */
class BoilerPlate extends \PhraseanetWebTestCaseAbstract
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
    return require __DIR__ . '/../../../../Path/To/Application.php';
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
