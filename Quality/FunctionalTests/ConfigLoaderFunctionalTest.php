<?php

/**
 * ConfigLoader test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class ConfigLoaderFunctionalTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * @var ConfigLoader
     */
    private $oL;

    /**
     * @var string
     */
    private $sToday = "2012-04-04";

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

      $this->oL = new ConfigLoader();
      $this->oL->setToday($this->sToday);
      $this->oL->setPDO($this->oPDO);
    }

    /**
     * Cleans up the environment after running a test.
     */
    public function tearDown() {
      parent::tearDown();
    }

    function testConfigLoaderOutputIsOk() {

        $this->init();

        $idCampaign = "1331109874_test";
        $terminalNbr = "1";

        $oR = $this->oL->getCampaignData( $idCampaign, $terminalNbr );
        $this->assertInstanceOf('Campaign', $oR);
    }

}

