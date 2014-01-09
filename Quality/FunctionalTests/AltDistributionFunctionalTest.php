<?php

/**
 * AltDistribution test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class AltDistributionFunctionalTest extends PHPUnit_Extensions_Database_TestCase {
    
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

    public function testGetInstantWinWithCyclicAlgorithm() {
        $this->init();

        // Actuellement, il n'y a aucune pile de lots enregistree en base
        $sSerializedPrizeStack = "";
        $this->oPDO->start()
            ->getField( "serializedData", "dotation_computed",
                        " id_campaign='{$this->sCampaignId}' AND day='{$this->sToday}' ",
                        $sSerializedPrizeStack )
            ->commit();
        $this->assertFalse($sSerializedPrizeStack);

        $aWonPrizes = array(
            "1341048521_mug" => 0,
            "1341048521_perdu" => 0
        );

        // Un appel a getInstantWin() devrait donc regenerer cette pile en
        // nous renvoyer l'un des lots
        $oRes = $this->play($this->sTerminalNbr, $this->sToday,
                            "4000", "2012-07-02 11:48:30", $aWonPrizes);

        // On teste que la pile de lots a bien été regénérée
        // La dotation comprenant 10 lots, il devrait y en avoir 9 dans la pile
        // car nous venons d'en gagner un.
        $sSerializedPrizeStack = "";
        $this->oPDO->start()
            ->getField( "serializedData", "dotation_computed",
                        " id_campaign='{$this->sCampaignId}' AND day='{$this->sToday}' ",
                        $sSerializedPrizeStack )
            ->commit();
        $aPrizeStack = unserialize($sSerializedPrizeStack);
        $this->assertInternalType("array", $aPrizeStack);
        $this->assertEquals(9, count($aPrizeStack));

        // On va maintenant jouer 19 fois pour vider la pile de lots 2 fois
        // et verifier que les lots gagné correspondent bien à la dotation
        for ($i = 1; $i <= 19; $i++) {
            $this->play($this->sTerminalNbr, $this->sToday,
                        (string) (4000 + $i), "2012-07-02 11:48:30", $aWonPrizes);
        }

        $aExpectedWonPrizes = array(
            "1341048521_mug" => 6,
            "1341048521_perdu" => 14
        );
        $this->assertEquals($aExpectedWonPrizes, $aWonPrizes);

    }

    /**
     * Appelle getInstantWin(), verifie le resultat et stock
     * dans un tableau de stats le cadeau gagné
     */
    protected function play($sTerminalNbr, $sToday, $sSerialInput, $sDateTime,
                            &$aWonPrizes, $bWin = TRUE, $sLostCause = "") {

        $oDateTimeInjector = new DateTimeInjector($sDateTime);

        $oAD = new AltDistribution( $this->oC, $sTerminalNbr,
                                    $sSerialInput );
        $oAD->setToday($sToday);
        $oAD->setNow($sDateTime);
        $oAD->setLoader($this->oL);
        $oAD->setDateTimeInjector($oDateTimeInjector);
        $oAD->setPDO($this->oPDO);

        $initialRowCount = $this->getConnection()->getRowCount('logs');

        $oRes = $oAD->getInstantWin();

        // Verification du resultat
        $this->assertInstanceOf("LotteryResult", $oRes);
        // Si l'on doit gagner
        if ($bWin) {
            $this->assertTrue($oRes->isWinner);
            $this->assertTrue(array_key_exists($oRes->oInstantWin->id_prize, $aWonPrizes));

            // On verifie qu'un log a bien été écrit
            $this->assertEquals($initialRowCount + 1,
                                $this->getConnection()->getRowCount('logs'));
            $this->assertLastLog($this->oC->id, $sTerminalNbr, $sDateTime,
                                 "", $sSerialInput, $oRes->oInstantWin->id_prize,
                                 Vlog::LOG_TYPE_PLAY);

            // Stock le cadeau gagne dans le tableau de stats
            $aWonPrizes[$oRes->oInstantWin->id_prize] += 1;
        }
        // Si l'on doit perdre
        else {
            $this->assertFalse($oRes->isWinner);
            // On verifie qu'un log a bien été écrit
            $this->assertEquals($initialRowCount + 1,
                                $this->getConnection()->getRowCount('logs'));
            $this->assertLastLog($this->oC->id, $sTerminalNbr, $sDateTime,
                                 $sLostCause, $sSerialInput, "0",
                                 Vlog::LOG_TYPE_PLAY);
        }

        $this->oL->reloadPrizeHasDotation($this->oC);
        return ($oRes);
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


    public function testGetInstantWinWithAllWinnerAlgo() {
        $sCampaignId = "1340987249_allwinner";
        $sToday = "2012-07-02";
        $this->init($sCampaignId, $this->sTerminalNbr, $sToday);

        // On initialise la pile de lots
        $oAD = new AltDistribution( $this->oC, $this->sTerminalNbr, "20000" );
        $oAD->setToday($this->sToday);
        $oAD->setPDO($this->oPDO);
        $oAD->setLoader($this->oL);
        $oAD->initTodayPrizeList($sCampaignId, $this->sTerminalNbr);

        // On verifie que la pile contient le bon nombre d'enregistrements
        $sSerializedPrizeStack = "";
        $this->oPDO->start()
            ->getField( "serializedData", "dotation_computed",
                        " id_campaign='$sCampaignId' AND day='{$this->sToday}' ",
                        $sSerializedPrizeStack )
            ->commit();
        $aPrizeStack = unserialize($sSerializedPrizeStack);

        $this->assertInternalType("array", $aPrizeStack);
        $this->assertEquals(18, count($aPrizeStack));

        // On va jouer 18 fois et verifier que les lots gagnés correspondent
        // bien à la dotation
        $aWonPrizes = array(
            "1340987249_jeu-video" => 0,
            "1340987249_perdu" => 0
        );
        for ($i = 1; $i <= 18; $i++) {
            $this->play( $this->sTerminalNbr, $sToday, (string) (20000 + $i),
                         "2012-07-02 10:40:00", $aWonPrizes );
        }
        $aExpectedWonPrizes = array(
            "1340987249_jeu-video" => 7,
            "1340987249_perdu" => 11
        );
        $this->assertEquals($aExpectedWonPrizes, $aWonPrizes);

        // Maintenant qu'il n'y a plus de lots normaux, on va vérifier
        // que getInstantWin() renvoie bien un lot de consolation
        // (il n'y en a qu'un a gagner aujourd'hui)
        $aWonPrizes = array("1340987249_magnet" => 0);
        $this->play( $this->sTerminalNbr, $sToday, "25000",
                     "2012-07-02 10:40:00", $aWonPrizes );
        $this->assertEquals(array("1340987249_magnet" => 1), $aWonPrizes);

        // Maintenant qu'il n'y a plus de lots du tout, on va vérifier
        // que getInstantWin() renvoie un LotteryResult perdant
        $this->play( $this->sTerminalNbr, $sToday, "25001",
                     "2012-07-02 10:40:00", $aWonPrizes, FALSE,
                     AltDistribution::PLAY_ERROR_ALT_NO_MORE_PRIZE );

    }


}

