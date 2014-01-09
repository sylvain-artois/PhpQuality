<?php
/**
 * ScanTerminal test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class ScanTerminalFunctionalTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * @var PDOUtils
     */
    private $oPDO;


    /**
     * @var ScanTerminal
     */
    private $oS;

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

    public function setUp() {
        parent::setUp();
    }

    /**
     * Cleans up the environment after running a test.
     */
    public function tearDown() {
        parent::tearDown();
    }

    /**
     * Prepares the environment before running a test.
     */
    public function init() {

      $this->oPDO = new PDOUtils(DB_DBNAME, DB_HOST, DB_USER, DB_PASSWD );
      $GLOBALS['pdo'] = $this->oPDO;

      $this->oS = new ScanTerminal();
    }

    public function testScanTerminal()
    {
        $this->init();
        $this->markTestIncomplete("Need to define terminalRegistered table in fixtures");
    }

}