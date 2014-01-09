<?php

/**
 * ConfigLoader test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class ConfigLoaderTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * @var ConfigLoader
     */
    private $oL;

    /**
     * @var string
     */
    private $sCampaignId = "1331109874_test";

    /**
     * @var string
     */
    private $sToday = "2012-04-04";

    /**
     * @var Log
     */
    private $oLogger;

    /**
     * @var PDOUtils
     */
    private $oPDO;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection() {

        return
            new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection (
                new PDO( "mysql:host=".DB_HOST.";dbname=".DB_DBNAME , DB_USER, DB_PASSWD ),
                DB_DBNAME
            );
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet() {
        return $this->createXMLDataSet(dirname(__FILE__).'/../Fixtures/db_multiple_campaign.xml');
    }

    /**
     * Prepares the environment before running a test.
     */
    public function init() {

      $this->oPDO = new PDOUtils(DB_DBNAME, DB_HOST, DB_USER, DB_PASSWD );

      $this->oLogger = Log::factory('file', 'dtc.log', 'TEST');

      $this->oL = new ConfigLoader();
      $this->oL->setToday($this->sToday);
      $this->oL->setPDO($this->oPDO);
    }

    /**
     * Cleans up the environment after running a test.
     */
    public function tearDown() {
      $this->oD = null;
      parent::tearDown();
    }

    /**
     * @covers ConfigLoader::authenticate
     *
     * Test authentication with valid credentials
     */
    public function testAuthenticateWithAValidUser(){
      $this->init();

      $userCredential = new User();
      $userCredential->u_password = "azerty_admin";
      $userCredential->id_campaign = "1331109874_test";
      $user = $this->oL->authenticate($userCredential);

      $this->assertInstanceOf("User", $user);

      $expectedUser = new User();
      $expectedUser->u_id = 2;
      $expectedUser->u_email = "admin";
      $expectedUser->u_password = "azerty_admin";
      $expectedUser->u_type = "admin";
      $expectedUser->id_campaign = "1331109874_test";
      $this->assertEquals($expectedUser, $user);
    }

    /**
     * @covers ConfigLoader::authenticate
     *
     * Test authentication with invalid credentials
     */
    public function testAuthenticateWithInvalidCampaignId(){
      $this->init();

      $userCredential = new User();
      $userCredential->u_password = "azerty_admin";
      $userCredential->id_campaign = "11111_unknown_campaign";
      $user = $this->oL->authenticate($userCredential);

      $this->assertInstanceOf("User", $user);

      $expectedUser = new User();
      $this->assertEquals($expectedUser, $user);
    }


    /**
     * @covers ConfigLoader::authenticate
     *
     * Test authentication with the old method (email + password) that should
     * not work anymore.
     */
    public function testAuthenticateWithEmailAndPassword(){
      $this->init();

      $userCredential = new User();
      $userCredential->u_email = "admin";
      $userCredential->u_password = "azerty_admin";
      $user = $this->oL->authenticate($userCredential);

      $this->assertInstanceOf("User", $user);

      $expectedUser = new User();
      $this->assertEquals($expectedUser, $user);
    }

    /**
     * @covers ConfigLoader::authenticate
     *
     * Test authentication with an empty user
     */
    public function testAuthenticateWithEmptyUser() {
      $this->init();
      $expectedUser = new User();
      $user = $this->oL->authenticate(new User());
      $this->assertEquals($expectedUser, $user);
    }

}

