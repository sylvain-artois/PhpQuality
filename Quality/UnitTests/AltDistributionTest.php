<?php

/**
 * AltDistribution test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class AltDistributionTest extends PHPUnit_Extensions_Database_TestCase {
    
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
    private $sCampaignId = "1341048521_cyclic";
    
    /**
     * @var string
     */
    private $sToday = "2012-07-02";
    
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
    public function init($sCampaignId = null,
                         $sTerminalNbr = null,
                         $sToday = null) {

        $sCampaignId = isset($sCampaignId) ? $sCampaignId : $this->sCampaignId;
        $sTerminalNbr = isset($sTerminalNbr) ? $sTerminalNbr : $this->sTerminalNbr;
        $sToday = isset($sToday) ? $sToday : $this->sToday;

        $this->oPDO = new PDOUtils(DB_DBNAME, DB_HOST, DB_USER, DB_PASSWD );

        $this->oL = new ConfigLoader();
        $this->oL->setToday($sToday);
        $this->oL->setPDO($this->oPDO);
        $this->oC = $this->oL->getCampaignData( $sCampaignId, $sTerminalNbr);
    }

    /**
     * Cleans up the environment after running a test.
     */
    public function tearDown() {
        $this->oD = null;
        parent::tearDown();
    }

    public function testGetInstantWinWithAllWinnerAlgoAndQuantityMismatch() {
        $sCampaignId = "1340987249_allwinner";
        $sToday = "2012-07-02";
        $sNow = $sToday . " 15:05:00";
        $this->init($sCampaignId, $this->sTerminalNbr, $sToday);
        $oDateTimeInjector = new DateTimeInjector($sNow);

        $oAD = new AltDistribution( $this->oC, $this->sTerminalNbr, "20000" );
        $oAD->setToday($this->sToday);
        $oAD->setNow($sNow);
        $oAD->setDateTimeInjector($oDateTimeInjector);
        $oAD->setPDO($this->oPDO);
        $oAD->setLoader($this->oL);

        // On initialise une pile de lots qui contient 1 lot
        $aPrizeStack = array("1340987249_jeu-video");
        $oAD->insertPrizeStack($sCampaignId, $aPrizeStack);

        // On va modifier la dotation pour indiquer qu'un jeu vidéo
        // a déjà été gagné aujourd'hui, alors qu'il n'y a aucun log
        // correspondant en base.
        $oPrizeHasDotation = $oAD->getPrizeHasDotationForThatPrize("1340987249_jeu-video",
                                                                   $this->oC->curPrizeHasDotation);
        $oPrizeHasDotation->alreadydeal = 1;

        // Maintenant on peut jouer et vérifier que l'on a bien une erreur
        $initialRowCount = $this->getConnection()->getRowCount('logs');

        $oRes = $oAD->getInstantWin();
        $sError = "checkQuantity, quantity mismatch. prizehasdotation::alreadydeal is not the same as the logs amount";

        $this->assertInstanceOf("LotteryResult", $oRes);
        $this->assertFalse($oRes->isWinner);
        $this->assertEquals($sError, $oRes->lostCause);

        // On verifie qu'un log a bien été écrit
        $this->assertEquals($initialRowCount + 1,
                            $this->getConnection()->getRowCount('logs'));
        $this->assertLastLog($sCampaignId, $this->sTerminalNbr, $sNow, $sError,
                             "20000", "0", Vlog::LOG_TYPE_PLAY);
    }

    public function testGetInstantWinWithAllWinnerAlgoAndNotEnoughAmount() {
        $sCampaignId = "1340987249_allwinner";
        $sToday = "2012-07-02";
        $sNow = $sToday . " 15:05:00";
        $this->init($sCampaignId, $this->sTerminalNbr, $sToday);
        $oDateTimeInjector = new DateTimeInjector($sNow);

        $oAD = new AltDistribution( $this->oC, $this->sTerminalNbr, "20000" );
        $oAD->setToday($this->sToday);
        $oAD->setNow($sNow);
        $oAD->setDateTimeInjector($oDateTimeInjector);
        $oAD->setPDO($this->oPDO);
        $oAD->setLoader($this->oL);

        // On initialise une pile de lots qui contient 1 lot qui a déjà été
        // distribué autant que l'on pouvait aujourd'hui
        $aPrizeStack = array("1340987249_ventilateur-usb");
        $oAD->insertPrizeStack($sCampaignId, $aPrizeStack);

        // Maintenant on peut jouer et vérifier que l'on a bien une erreur
        $initialRowCount = $this->getConnection()->getRowCount('logs');

        $oRes = $oAD->getInstantWin();
        $sError = AltDistribution::PLAY_ERROR_NOT_ENOUGH_AMOUNT;

        $this->assertInstanceOf("LotteryResult", $oRes);
        $this->assertFalse($oRes->isWinner);
        $this->assertEquals($sError, $oRes->lostCause);

        // On verifie qu'un log a bien été écrit
        $this->assertEquals($initialRowCount + 1,
                            $this->getConnection()->getRowCount('logs'));
        $this->assertLastLog($sCampaignId, $this->sTerminalNbr, $sNow, $sError,
                             "20000", "0", Vlog::LOG_TYPE_PLAY);
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

