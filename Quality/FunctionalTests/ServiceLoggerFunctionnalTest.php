<?php
/**
 * ServiceLogger test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class ServiceLoggerFunctionalTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * @var PDOUtils
     */
    private $oPDO;


    /**
     * @var ServiceLogger
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

      $this->oS = new ServiceLogger();
    }

    public function testLog()
    {
        $this->init();

        $sCampaignId = "1331109874_test";
        $sTerminalNbr = "1";
        $iLogType = Vlog::LOG_TYPE_PLAY;
        $sData = "TEST";
        $sNow = "2012-07-06 18:24:00";
        $sBarcod = "1234";
        $sPrize = "1331109979_cle-usb";
        $oLog = new Vlog($sCampaignId, $sTerminalNbr, $iLogType, $sData);
        $oLog->setFromDB( (object) array(
            "id_campaign" => $sCampaignId,
            "id_terminal"=> $sTerminalNbr,
            "logtype" => $iLogType,
            "_datetime" => $sNow,
            "data" => $sData,
            "barcod" => $sBarcod,
            "prizeid" => $sPrize
        ));

        $initialRowCount = $this->getConnection()->getRowCount('logs');
        $this->oS->log($oLog);
        $this->assertEquals($initialRowCount + 1,
                            $this->getConnection()->getRowCount('logs'));

        $this->assertLastLog($sCampaignId, $sTerminalNbr, $sNow,
                             $sData, $sBarcod, $sPrize, $iLogType);
    }

    /**
     * Teste les caracteristiques du dernier log ecrit en base de donnees
     *
     */
    protected function assertLastLog($sCampaignId, $sTerminalNbr, $sLogDatetime,
                                     $sLogData, $sSerialInput,  $sLogPrizeId,
                                     $iLogType)
    {
        // On teste que la derniere entree des logs contient bien les bonnes informations
        $this->oPDO->start()
            ->query("SELECT * FROM  `logs` ORDER BY id DESC LIMIT 1", $aLogs)
            ->commit();
        $this->assertEquals($sCampaignId, $aLogs[0]['id_campaign']);
        $this->assertEquals($sTerminalNbr, $aLogs[0]['id_terminal']);
        $this->assertEquals((string) $iLogType, $aLogs[0]['logtype']);
        $this->assertEquals($sLogDatetime, $aLogs[0]['_datetime']);
        $this->assertEquals($sLogData, $aLogs[0]['data']);
        $this->assertEquals($sSerialInput, $aLogs[0]['barcod']);
        $this->assertEquals($sLogPrizeId, $aLogs[0]['prizeid']);
    }


}