<?php

/**
 * InstantWinDistribution test case
 *
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class InstantWinDistributionFunctionalTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * @var IntantWinDistribution
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
     * Prepares the environment for a specific campaign before running a test.
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

        $this->oD = new InstantWinDistribution();
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
    	if( !is_null($this->oPDO) ) {
            $this->oPDO->closeConnection();
        }
        parent::tearDown();
    }

    function testInstantWinOutputIsOk() {
        $this->init();

        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355571");
        $this->assertInstanceOf("LotteryResult", $oLR);
    }

    public function testThatInstantwindistributionReturnExpectedPrizesIfDistributionAlgorithmeIsSetToDemo(){
        $sCampaignId = "1340805973_demo";
        $sTerminalId = "1";
        $sToday = "2012-06-21";

        $this->init($sCampaignId, $sTerminalId, $sToday);

        $iWinCount = 0;
        $iLostCount = 0;
        // On va jouer une 50ène de fois avec l'algo de demo et vérifier lorsque l'on
        // gagne que le cadeau est l'un des cadeaux attendus.
        $aPrizes = array();
        for ($i = 0; $i < 50; $i++) {
            $oLR = $this->oD->instantWin($this->sCampaignId,
                                         $this->sTerminalNbr,
                                         "123456789");
            if ($oLR->isWinner) {
                $iWinCount++;
                $this->assertContains($oLR->oInstantWin->id_prize,
                                      array(
                                          "1340805980_voyage-berlin",
                                          "1340805999_bon-achat" ));
            }
            else {
                $iLostCount++;
                $this->assertEquals(DistributionComputer::PLAY_ERROR_NOT_INSTANT_WIN,
                                    $oLR->lostCause);
            }
        }
    }

    public function testThatInstantwindistributionReturnLostInstantwinIfCampaignNotStarted(){
        $this->init($this->sCampaignId, $this->sTerminalNbr, "2012-04-01");

        // On joue un jour ou la campagne n'est pas active
        $oDateTimeInjector = new DateTimeInjector("2012-04-01 10:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-01 10:00:00");
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355571");
        $this->assertFalse($oLR->isWinner);
        $this->assertEquals(InstantWinDistribution::PLAY_ERROR_NOT_IN_SCHEDULE,
                            $oLR->lostCause);
    }

    public function testThatInstantwindistributionReturnLostInstantwinIfNotInShedule(){
        $this->init();

        // On joue un jour ou la campagne est active, mais avant le début des
        // horaires de jeu
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 08:59:59");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 08:59:59");
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355572");
        $this->assertFalse($oLR->isWinner);
        $this->assertEquals(InstantWinDistribution::PLAY_ERROR_NOT_IN_SCHEDULE,
                            $oLR->lostCause);

        // On joue un jour ou la campagne est active, mais pendant la pause
        // du midi
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 13:00:01");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 13:00:01");
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355573");
        $this->assertFalse($oLR->isWinner);
        $this->assertEquals(InstantWinDistribution::PLAY_ERROR_NOT_IN_SCHEDULE,
                            $oLR->lostCause);

        $oDateTimeInjector = new DateTimeInjector("2012-04-04 13:59:59");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 13:59:59");
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355574");
        $this->assertFalse($oLR->isWinner);
        $this->assertEquals(InstantWinDistribution::PLAY_ERROR_NOT_IN_SCHEDULE,
                            $oLR->lostCause);

        // On joue un jour ou la campagne est active, mais après les horaires
        // de jeu
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 18:00:01");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 18:00:01");
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355575");
        $this->assertFalse($oLR->isWinner);
        $this->assertEquals(InstantWinDistribution::PLAY_ERROR_NOT_IN_SCHEDULE,
                            $oLR->lostCause);

        // Enfin, on va jouer durant les hohaires de jeu pou vérifier que l'on nous
        // renvoie pas l'erreur NOT_IN_SCHEDULE
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 16:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 16:00:00");
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355576");
        $this->assertNotEquals(InstantWinDistribution::PLAY_ERROR_NOT_IN_SCHEDULE,
                            $oLR->lostCause);
    }

    public function testThatInstantWinDistributionReturnLostInstantwinIfNoPrizeThatDay(){
        $this->init($this->sCampaignId, $this->sTerminalNbr, "2012-04-05");

        // On joue un jour ou il n'y a pas de dotation
        $oDateTimeInjector = new DateTimeInjector("2012-04-05 10:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-05 10:00:00");
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355571");
        $this->assertFalse($oLR->isWinner);
        $this->assertEquals(InstantWinDistribution::PLAY_ERROR_EMPTY_DOTATION,
                            $oLR->lostCause);
    }

    public function testThatInstantwindistributionReturnLostInstantWinIfPrizeQuantityMismatch(){
        $this->init();

        // On insert en base de données un instant gagnant pour lot normal
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1332320329_15-euros', '{$this->sToday} 15:00:00', 1, 0, 0 );" )
            // ...et on indique une valeur incorrecte a alreadydeal dans la table prizehasdotation
            ->exec("UPDATE `prizehasdotation` SET `alreadydeal`=0 WHERE `id_prize`='1332320329_15-euros' AND `id_dotation`='1329132446_dotation_1' AND `_date`='2012-04-04'")
            ->commit();

        // On recharge les donnees de la campagne suite a notre modification en BDD
        $this->oL->reloadPrizeHasDotation($this->oC);

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 15:00:01");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 15:00:01");

        $sExpectedError = "checkQuantity, quantity mismatch. prizehasdotation::alreadydeal is not the same as the logs amount";
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355571");
        $this->assertFalse($oLR->isWinner);
        $this->assertEquals($sExpectedError, $oLR->lostCause);
    }

    public function testThatInstantwindistributionReturnJackpotIfThereIsOneJackpotRegistered(){
        $this->init();

        // On insert en base de données un instant gagnant pour un jackpot
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1331109979_voyage-aux-seychelles', '{$this->sToday} 16:15:00', 1, 1, 0 );" )
            ->commit();

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 17:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 17:00:00");

        // On doit gagner le jackpot
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355571");
        $this->assertTrue($oLR->isWinner);
        $this->assertEquals("1331109979_voyage-aux-seychelles",
                            $oLR->oInstantWin->id_prize);
    }

    public function testThatInstantwindistributionReturnFixedPrizeIfThereIsOneFixedPrizeRegistered(){
        $this->init();

        // On insert en base de données un instant gagnant pour lot fixe
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1332320329_50-euros', '{$this->sToday} 15:00:00', 1, 0, 1 );" )
            ->commit();

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 17:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 17:00:00");

        // On doit gagner le lot fixe
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355571");
        $this->assertTrue($oLR->isWinner);
        $this->assertEquals("1332320329_50-euros",
                            $oLR->oInstantWin->id_prize);
    }

    public function testThatInstantwindistributionReturnJackpotIfThereIsOneJackpotAndOneFixedPrizeRegistered(){
        $this->init();

        // On insert en base de données un instant gagnant pour un jackpot et un instant
        // gagnant pour un lot fixe
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1331109979_voyage-aux-seychelles', '{$this->sToday} 16:15:00', 1, 1, 0 );" )
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1332320329_50-euros', '{$this->sToday} 15:00:00', 1, 0, 1 );" )
            ->commit();

        // On se place après les 2 instants gagnants
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 17:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 17:00:00");

        // On doit gagner le jackpot
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355571");
        $this->assertTrue($oLR->isWinner);
        $this->assertEquals("1331109979_voyage-aux-seychelles",
                            $oLR->oInstantWin->id_prize);
    }

    public function testThatInstantwindistributionCallsAltDistributionWhenTheDistributionAlgorithmeIsSetToAllwinner(){

        $sCampaignId = "1340987249_allwinner";
        $sTerminalNbr = "1";
        $sToday = "2012-07-02";
        $sSerialInput = "20001";

        $this->init($sCampaignId, $sTerminalNbr, $sToday);

        $oDateTimeInjector = new DateTimeInjector("2012-07-02 09:30:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-02 09:30:00");

        // Creation d'un mock pour l'algorithme AltDistribution
        $cyclicAlgo = $this->getMock('AltDistribution',
                                     array('getInstantWin'),
                                     array($this->oC, $sTerminalNbr, $sSerialInput));
        $this->oD->setAltAlgo($cyclicAlgo);


        // On s'attend a ce que le mock soit appelé une fois
        $cyclicAlgo->expects($this->once())
            ->method('getInstantWin');

         $this->oD->instantWin($sCampaignId, $sTerminalNbr, $sSerialInput);
    }

    public function testThatInstantwindistributionCallsAltDistributionWhenTheDistributionAlgorithmeIsSetToCyclic(){

        $sCampaignId = "1341048521_cyclic";
        $sTerminalNbr = "1";
        $sToday = "2012-07-02";
        $sSerialInput = "4001";

        $this->init($sCampaignId, $sTerminalNbr, $sToday);

        $oDateTimeInjector = new DateTimeInjector("2012-07-02 09:30:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-02 09:30:00");

        // Creation d'un mock pour l'algorithme AltDistribution
        $cyclicAlgo = $this->getMock('AltDistribution',
                                     array('getInstantWin'),
                                     array($this->oC, $sTerminalNbr, $sSerialInput));
        $this->oD->setAltAlgo($cyclicAlgo);


        // On s'attend a ce que le mock soit appelé une fois
        $cyclicAlgo->expects($this->once())
            ->method('getInstantWin');

         $this->oD->instantWin($sCampaignId, $sTerminalNbr, $sSerialInput);
    }


    public function testThatInstantwindistributionReturnPrizeIfThereIsOneInstantwinRegistered(){
        $this->init();

        // On insert en base de données un instant gagnant pour lot normal
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1332320329_15-euros', '{$this->sToday} 15:00:00', 1, 0, 0 );" )
            ->commit();

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 15:00:01");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 15:00:01");

        // On doit gagner le lot normal
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355571");
        $this->assertTrue($oLR->isWinner);
        $this->assertEquals("1332320329_15-euros",
                            $oLR->oInstantWin->id_prize);

        // La pile de cadeau etant vide, aucun nouvel instant gagnant ne doit avoir ete inséré
        $this->oPDO->start()
            ->query("SELECT * FROM  `winningtime_computed` ORDER BY id DESC LIMIT 1", $aRows)
            ->commit();
        $this->assertEquals("1332320329_15-euros", $aRows[0]['id_prize']);
        $this->assertEquals("2012-04-04 15:00:00", $aRows[0]['_datetime']);
        $this->assertEquals("0", $aRows[0]['isValid']);
        $this->assertEquals("0", $aRows[0]['isJackpot']);
        $this->assertEquals("0", $aRows[0]['isFixedPrize']);
    }

    public function testThatInstantwindistributionRegistersTheNextInstantWinWhenItGiveAPRize(){
        $this->init();

        $aPrizeStack = array("1332320329_15-euros");
        $this->oD->insertPrizeStack($this->oC->id, $aPrizeStack);

        // On insert en base de données un instant gagnant pour lot normal
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1332320329_15-euros', '{$this->sToday} 15:00:00', 1, 0, 0 );" )
            ->commit();

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 16:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 16:00:00");

        // On annule l'ecart variable pour le calcul du prochain instant gagnant
        $this->oC->randomRangePercent = 0;

        // On doit gagner le lot normal
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355571");
        $this->assertTrue($oLR->isWinner);
        $this->assertEquals("1332320329_15-euros",
                            $oLR->oInstantWin->id_prize);

        // Un nouvel instant gagnant doit avoir été enregistré en base
        $this->oPDO->start()
            ->query("SELECT * FROM  `winningtime_computed` ORDER BY id DESC LIMIT 1", $aRows)
            ->commit();
        $this->assertEquals("1332320329_15-euros", $aRows[0]['id_prize']);
        $this->assertEquals("2012-04-04 17:00:00", $aRows[0]['_datetime']);
        $this->assertEquals("1", $aRows[0]['isValid']);
        $this->assertEquals("0", $aRows[0]['isJackpot']);
        $this->assertEquals("0", $aRows[0]['isFixedPrize']);
    }


    public function testThatInstantwindistributionReturnErrorIfThereIsOneInstantwinRegisteredButNotEnoughAmount(){
        $this->init("1331111111_no_more_prize", "1", "2012-04-04");

        // On insert en base de données un instant gagnant pour lot normal
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1331111111_porte-cles', '{$this->sToday} 09:15:00', 1, 0, 0 );" )
            ->commit();

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 09:30:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 09:30:00");

        // On doit recevoir une erreur
        $oLR = $this->oD->instantWin("1331111111_no_more_prize", "1", "42100");
        $this->assertFalse($oLR->isWinner);
        $this->assertEquals(InstantWinDistribution::PLAY_ERROR_NOT_ENOUGH_AMOUNT,
                            $oLR->lostCause);
    }

    public function testThatInstantwindistributionReturnBoobyPrizeIfThereIsEnoughBoobyPrize(){
        $this->init();

        $oDateTimeInjector = new DateTimeInjector("2012-04-04 14:30:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 14:30:00");

        // On doit gagner le lot de consolation
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr, "355572");
        $this->assertTrue($oLR->isWinner);
        $this->assertEquals("1331109979_cle-usb",
                            $oLR->oInstantWin->id_prize);

    }

    public function testThatInstantwindistributionReturnLostIfThereIsNoInstantwinRegistered(){
        $this->init();

        $oDateTimeInjector = new DateTimeInjector("2012-04-04 14:30:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 14:30:00");

        // Il y a 5 lots de consolation à gagner aujourd'hui donc on va déjà
        // faire gagner les 5
        for ($iBarcode = 355571; $iBarcode <= 355575; $iBarcode++) {
            $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr,
                                         (string) $iBarcode);
            $this->assertTrue($oLR->isWinner);
            $this->assertEquals("1331109979_cle-usb",
                                $oLR->oInstantWin->id_prize);
        }

        // Maintenant qu'il n'y a plus de lot de consolation, on s'assure que
        // la fonction renvoie perdu.
        $oLR = $this->oD->instantWin($this->sCampaignId, $this->sTerminalNbr,
                                     "355576");
        $this->assertFalse($oLR->isWinner);
        $this->assertEquals(InstantWinDistribution::PLAY_ERROR_NOT_INSTANT_WIN,
                            $oLR->lostCause);
    }

    public function testThatInstantwindistributionWriteLogIfInstantwinIsCalledNotInShedule() {
        $this->init();

        // On joue un jour ou la campagne est active, mais avant le début des
        // horaires de jeu
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 08:59:59");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 08:59:59");

        $this->_testWriteLog($this->sCampaignId, $this->sTerminalNbr, "355572",
                             "2012-04-04 08:59:59",
                             InstantWinDistribution::PLAY_ERROR_NOT_IN_SCHEDULE,
                             "0");
    }

    public function testThatInstantwindistributionWriteLogIfInstantwinIsCalledWithEmptyDotation() {
        $this->init($this->sCampaignId, $this->sTerminalNbr, "2012-04-05");

        // On joue un jour ou il n'y a pas de dotation
        $oDateTimeInjector = new DateTimeInjector("2012-04-05 10:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-05 10:00:00");

        $this->_testWriteLog($this->sCampaignId, $this->sTerminalNbr, "355571",
                             "2012-04-05 10:00:00",
                             InstantWinDistribution::PLAY_ERROR_EMPTY_DOTATION,
                             "0");
    }


    public function testThatInstantwindistributionWriteLogIfInstantwinIsCalledWithNoInstantWin(){
        $this->init();

        $oDateTimeInjector = new DateTimeInjector("2012-04-04 14:30:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 14:30:00");

        // Il y a 5 lots de consolation à gagner aujourd'hui donc on va déjà
        // faire gagner les 5 et tester au passage qu'un log est ecrit a chaque
        // fois.
        for ($iBarcode = 355571; $iBarcode <= 355575; $iBarcode++) {
            $this->_testWriteLog($this->sCampaignId, $this->sTerminalNbr,
                                 (string) $iBarcode, "2012-04-04 14:30:00", "",
                                 "1331109979_cle-usb");
        }

        // Maintenant qu'il n'y a plus de lot de consolation, on peut rejouer
        // et tester l'ecriture du log
        $this->_testWriteLog($this->sCampaignId, $this->sTerminalNbr, "355576",
                             "2012-04-04 14:30:00",
                             InstantWinDistribution::PLAY_ERROR_NOT_INSTANT_WIN,
                             "0");
    }

    public function testThatInstantwindistributionWriteLogIfInstantwinIsCalledAndWinNormalPrize(){
        $this->init();

        // On insert en base de données un instant gagnant pour lot normal
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1332320329_15-euros', '{$this->sToday} 15:00:00', 1, 0, 0 );" )
            ->commit();

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 15:00:01");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 15:00:01");

        $this->_testWriteLog($this->sCampaignId, $this->sTerminalNbr, "355571",
                             "2012-04-04 15:00:01", "", "1332320329_15-euros");
    }

    public function testThatInstantwindistributionWriteLogIfInstantwinIsCalledAndWinJackpot(){
        $this->init();

        // On insert en base de données un instant gagnant pour un jackpot
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1331109979_voyage-aux-seychelles', '{$this->sToday} 16:15:00', 1, 1, 0 );" )
            ->commit();

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 17:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 17:00:00");

        $this->_testWriteLog($this->sCampaignId, $this->sTerminalNbr, "355571",
                             "2012-04-04 17:00:00", "",
                             "1331109979_voyage-aux-seychelles");
    }

    public function testThatInstantwindistributionWriteLogIfInstantwinIsCalledAndWinFixedPrize(){
        $this->init();

        // On insert en base de données un instant gagnant pour lot fixe
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1332320329_50-euros', '{$this->sToday} 15:00:00', 1, 0, 1 );" )
            ->commit();

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 17:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 17:00:00");

        $this->_testWriteLog($this->sCampaignId, $this->sTerminalNbr, "355571",
                             "2012-04-04 17:00:00", "",
                             "1332320329_50-euros");
    }

    public function testThatInstantwindistributionWriteLogIfInstantwinIsCalledAndReturnNotEnoughAmount(){
        $this->init("1331111111_no_more_prize", "1", "2012-04-04");

        // On insert en base de données un instant gagnant pour lot normal
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1331111111_porte-cles', '{$this->sToday} 09:15:00', 1, 0, 0 );" )
            ->commit();

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 09:30:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 09:30:00");

        $this->_testWriteLog("1331111111_no_more_prize", "1", "42100",
                             "2012-04-04 09:30:00",
                             InstantWinDistribution::PLAY_ERROR_NOT_ENOUGH_AMOUNT,
                             "0");
    }

    public function testThatInstantwindistributionWriteLogIfInstantwinIsCalledAndPrizeQuantityMismatch(){
        $this->init();

        // On insert en base de données un instant gagnant pour lot normal
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1332320329_15-euros', '{$this->sToday} 15:00:00', 1, 0, 0 );" )
            // ...et on indique une valeur incorrecte a alreadydeal dans la table prizehasdotation
            ->exec("UPDATE `prizehasdotation` SET `alreadydeal`=0 WHERE `id_prize`='1332320329_15-euros' AND `id_dotation`='1329132446_dotation_1' AND `_date`='2012-04-04'")
            ->commit();

        // On recharge les donnees de la campagne suite a notre modification en BDD
        $this->oL->reloadPrizeHasDotation($this->oC);

        // On se place après l'instant gagnant
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 15:00:01");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-04-04 15:00:01");

        $sExpectedError = "checkQuantity, quantity mismatch. prizehasdotation::alreadydeal is not the same as the logs amount";
        $this->_testWriteLog($this->sCampaignId, $this->sTerminalNbr, "355571",
                             "2012-04-04 15:00:01", $sExpectedError, "0");
    }


    /**
     * Permet de tester qu'un log a bien ete ecrit en appelant instantWin
     *
     * @param string $sCampaignId L'id de la campagne
     * @param string $sTerminalNbr L'id du terminal
     * @param string $sSerialInput La chaine recue sur le port serie
     * @param string $sLogDateTime la valeur du champ _datetime du log qui doit être écrit
     * @param string $sLogData la valeur du champ data du log qui doit être écrit
     * @param string $sLogPrizeId la valeur du champ prizeid du log qui doit être écrit
     */
    protected function _testWriteLog($sCampaignId, $sTerminalNbr, $sSerialInput,
                                     $sLogDatetime, $sLogData, $sLogPrizeId)
    {
        // On recupere le nombre de logs initialement presents dans la table logs
        $initialRowcount = $this->getConnection()->getRowCount( "logs" );

        // On appelle la methode instant win
        $oLR = $this->oD->instantWin($sCampaignId, $sTerminalNbr, $sSerialInput);

        // On teste qu'il y a bien une nouvelle entree dans les logs
        $this->assertEquals($initialRowcount + 1,
                            $this->getConnection()->getRowCount( "logs" ));

        // On teste que la derniere entree des logs contient bien les bonnes informations
        $this->oPDO->start()
            ->query("SELECT * FROM  `logs` ORDER BY id DESC LIMIT 1", $aLogs)
            ->commit();
        $this->assertEquals($sCampaignId, $aLogs[0]['id_campaign']);
        $this->assertEquals($sTerminalNbr, $aLogs[0]['id_terminal']);
        $this->assertEquals("9", $aLogs[0]['logtype']);
        $this->assertEquals($sLogDatetime, $aLogs[0]['_datetime']);
        $this->assertEquals($sLogData, $aLogs[0]['data']);
        $this->assertEquals($sSerialInput, $aLogs[0]['barcod']);
        $this->assertEquals($sLogPrizeId, $aLogs[0]['prizeid']);
    }

}

