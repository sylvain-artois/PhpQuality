<?php

/**
 * Distribution test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class DistributionComputerFunctionalTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * @var DistributionComputer
     */
    private $oD;

    /**
     * @var Campaign
     */
    private $oC;

    /**
     * @var ConfigLoader
     */
    private $oL;

    /**
     * @var string
     */
    private $sTerminalNbr = "1";

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
    public function init($sCampaignId = null, $sTerminalNbr = null, $sToday = null) {

        $sCampaignId = isset($sCampaignId) ? $sCampaignId : $this->sCampaignId;
        $sTerminalNbr = isset($sTerminalNbr) ? $sTerminalNbr : $this->sTerminalNbr;
        $sToday = isset($sToday) ? $sToday : $this->sToday;


        $this->oPDO = new PDOUtils(DB_DBNAME, DB_HOST, DB_USER, DB_PASSWD );

        $this->oLogger = Log::factory('file', 'dtc.log', 'TEST');

        $this->oL = new ConfigLoader();
        $this->oL->setToday($sToday);
        $this->oL->setPDO($this->oPDO);
        $this->oC = $this->oL->getCampaignData( $sCampaignId, $sTerminalNbr);

        $this->oD = new DistributionComputer();
        $this->oD->setToday($sToday);
        $this->oD->setLoader($this->oL);
        $this->oD->setCampaign($this->oC);
        $this->oD->setPDO($this->oPDO);
        $this->oD->setLogger($this->oLogger);
    }

    /**
     * Cleans up the environment after running a test.
     */
    public function tearDown() {
        $this->oD = null;
        parent::tearDown();
    }

    public function testThatDistributioncomputerReturnLostLotteryresultIfTheDotationIsEmpty() {
        $sToday = "2012-04-05";
        $this->init($this->sCampaignId, $this->sTerminalNbr, $sToday);

        $res = $this->oD->initTodayPrizeList($this->sCampaignId, $this->sToday);
        $this->assertInstanceOf("LotteryResult", $res);
        $this->assertFalse($res->isWinner);
        $this->assertEquals(DistributionComputer::PLAY_ERROR_EMPTY_DOTATION,
                            $res->lostCause);
    }

}

