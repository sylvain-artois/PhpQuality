<?php

/**
 * PlayerApi test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class PlayerApiTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * @var PlayerApi
     */
    private $oA;

    /**
     * @var Campaign
     */
    private $oC;

    /**
     * @var ConfigLoader
     */
    private $oL;

    /**
     * @var PDOUtils
     */
    private $oPDO;

    /**
     * @var string Temporary directory
     */
    private $sTempDir;

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
    public function init($sCampaignId, $sTerminalNbr, $sToday) {

        $this->oPDO = new PDOUtils(DB_DBNAME, DB_HOST, DB_USER, DB_PASSWD );

        $this->oL = new ConfigLoader();
        $this->oL->setToday($sToday);
        $this->oL->setPDO($this->oPDO);
        $this->oC = $this->oL->getCampaignData( $sCampaignId, $sTerminalNbr);

        $this->oA = new PlayerApi();
        $this->oA->setPDO($this->oPDO);
        $this->oA->setToday($sToday);
        $this->oA->setLoader($this->oL);

	$devConfigPath =  realpath(dirname(__FILE__) . '/../Fixtures/config_device.xml');
        $this->oA->setDeviceConfigPath($devConfigPath);
    }

    public function setUp() {
        parent::setUp();

        // Creation d'un repertoire temporaire pour nos tests sur les fichiers csv
        $this->sTempDir = tempnam(sys_get_temp_dir(), "test-");
        @unlink($this->sTempDir);
        mkdir($this->sTempDir);
        chmod($this->sTempDir, 0777);
    }

    /**
     * Cleans up the environment after running a test.
     */
    public function tearDown() {
        parent::tearDown();
        // Suppression du repertoire temporaire
        $this->rrmdir($this->sTempDir);
     }

    /**
     * Get a method from a class, even if its private or protected
     *
     * Warning : you must use php >= 5.3.2
     *
     */
    protected static function getMethod($name) {
        $class = new ReflectionClass('PlayerApi');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }


    public function testGetCampaignSeries(){

        $sCampaignId = "1331109874_test";
        $this->init($sCampaignId, "1", "2012-04-04");

        $fGetCampaignSeries = self::getMethod("getCampaignSeries");
        $aSeries = $fGetCampaignSeries->invokeArgs($this->oA,
                                                   array($sCampaignId));
        $aExpectedSeries = array(
            array(
                "id" => "1",
                "id_campaign" => "1331109874_test",
                "number" => "355568",
                "series_start" => null,
                "series_end" => null,
                "toCompute" => "0"
            ),
            array(
                "id" => "2",
                "id_campaign" => "1331109874_test",
                "number" => "355569",
                "series_start" => null,
                "series_end" => null,
                "toCompute" => "0"
            ),
            array(
                "id" => "3",
                "id_campaign" => "1331109874_test",
                "number" => "355570",
                "series_start" => null,
                "series_end" => null,
                "toCompute" => "0"
            ),
            array(
                "id" => "4",
                "id_campaign" => "1331109874_test",
                "number" => "355571",
                "series_start" => null,
                "series_end" => null,
                "toCompute" => "0"
            ),
            array(
                "id" => "5",
                "id_campaign" => "1331109874_test",
                "number" => "355572",
                "series_start" => null,
                "series_end" => null,
                "toCompute" => "0"
            ),
            array(
                "id" => "6",
                "id_campaign" => "1331109874_test",
                "number" => "355573",
                "series_start" => null,
                "series_end" => null,
                "toCompute" => "0"
            ),
            array(
                "id" => "7",
                "id_campaign" => "1331109874_test",
                "number" => "355574",
                "series_start" => null,
                "series_end" => null,
                "toCompute" => "0"
            ),
            array(
                "id" => "8",
                "id_campaign" => "1331109874_test",
                "number" => "355575",
                "series_start" => null,
                "series_end" => null,
                "toCompute" => "0"
            ),
            array(
                "id" => "9",
                "id_campaign" => "1331109874_test",
                "number" => "355576",
                "series_start" => null,
                "series_end" => null,
                "toCompute" => "0"
            ),
        );
        $this->assertInternalType("array", $aSeries,
                                  "getCampaignSeries must return an array");
        $this->assertEquals($aExpectedSeries, $aSeries);


        $sCampaignId = "404_unknown_campaign";
        $aSeries = $fGetCampaignSeries->invokeArgs($this->oA,
                                                   array($sCampaignId));

        $this->assertInternalType("array", $aSeries,
                                  "getCampaignSeries must return an array");
        $this->assertTrue(empty($aSeries));
    }

    public function testIsBarcodValidReturnFalseIfCampaignHasNoSeries(){
        $this->init("1332000000_incomplete", "1", "2012-02-02");
        $this->assertFalse($this->oA->isBarcodValid( $this->oC, "1234"));
    }

    public function testIsBarcodValidThrowsExceptionIfFirstArgIsNotAPositiveInteger(){
        $this->init("1331109874_test", "1", "2012-04-04");
        $this->assertFalse($this->oA->isBarcodValid( $this->oC, "nimp"));
    }

    public function testIsBarcodValidForToComputeSeries(){
        $this->init("1331111111_no_more_prize", "1", "2012-04-04");

        $this->assertFalse($this->oA->isBarcodValid($this->oC, "41999"));
        $this->assertTrue($this->oA->isBarcodValid($this->oC, "42000"));
        $this->assertTrue($this->oA->isBarcodValid($this->oC, "43444"));
        $this->assertTrue($this->oA->isBarcodValid($this->oC, "44000"));
        $this->assertFalse($this->oA->isBarcodValid($this->oC, "44001"));
    }

    public function testIsBarcodValidForUncomputedSeries(){
        $this->init("1331109874_test", "1", "2012-04-04");
        $this->assertFalse($this->oA->isBarcodValid($this->oC, "355000"));
        $this->assertTrue($this->oA->isBarcodValid($this->oC, "355568"));
        $this->assertTrue($this->oA->isBarcodValid($this->oC, "355572"));
        $this->assertFalse($this->oA->isBarcodValid($this->oC, "355580"));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetLogsForBarcodThrowsExceptionIfParamMismatch(){
        $this->init("1331109874_test", "1", "2012-04-04");
        $fGetLogsForBarcod = self::getMethod("getLogsForBarcod");
        $fGetLogsForBarcod->invokeArgs($this->oA,
                                       array("","D","",""));
    }

    public function testGetLogsForBarcodOutputIsOk() {
        $this->init("1331109874_test", "1", "2012-04-04");
        $fGetLogsForBarcod = self::getMethod("getLogsForBarcod");

        $aParams = array( "355570", "D", "1331109874_test", "2012-04-04" );
        $aResult = $fGetLogsForBarcod->invokeArgs($this->oA, $aParams);
        $this->assertInternalType( "array", $aResult,
                                   "getLogsForBarcod must return an array" );
        $this->assertEquals( 2, count($aResult),
                             "getLogsForBarcod must return 2 logs for that test" );

        $aParams = array( "355570", "C", "1331109874_test", "" );
        $aResult = $fGetLogsForBarcod->invokeArgs($this->oA, $aParams);
        $this->assertInternalType( "array", $aResult,
                                   "getLogsForBarcod must return an array" );
        $this->assertEquals( 2, count($aResult),
                             "getLogsForBarcod must return 2 logs for that test" );

        $aParams = array( "355570", "D", "1331109874_test", "2012-04-03" );
        $aResult = $fGetLogsForBarcod->invokeArgs($this->oA, $aParams);
        $this->assertInternalType( "array", $aResult,
                                   "getLogsForBarcod must return an array" );
        $this->assertEquals( 0, count($aResult),
                             "getLogsForBarcod must return 0 logs for that test" );

        $aParams = array( "355567", "C", "1331109874_test", "" );
        $aResult = $fGetLogsForBarcod->invokeArgs($this->oA, $aParams);
        $this->assertInternalType( "array", $aResult,
                                   "getLogsForBarcod must return an array" );
        $this->assertEquals( 0, count($aResult),
                             "getLogsForBarcod must return 2 logs for that test" );
    }

    public function testIsBarcodAllowedToPlayInCampaignMode() {
        $this->init("1331111111_no_more_prize", "1", "2012-04-04");

        $this->assertTrue($this->oA->isBarcodAllowedToPlay($this->oC, "42001"));
        $this->assertFalse($this->oA->isBarcodAllowedToPlay($this->oC, "42000"));
    }

    public function testIsBarcodAllowedToPlayInDayMode() {
        $this->init("1331109874_test", "1", "2012-04-06");

        $this->assertTrue($this->oA->isBarcodAllowedToPlay($this->oC, "355570"));
        $this->assertTrue($this->oA->isBarcodAllowedToPlay($this->oC, "355571"));
        $this->assertFalse($this->oA->isBarcodAllowedToPlay($this->oC, "355568"));
    }

    public function testIsBarcodAllowedToPlayWithNumOccurZero() {
        $this->init("1340987249_allwinner", "1", "2012-07-02");

        $this->assertTrue($this->oA->isBarcodAllowedToPlay($this->oC, "20050"));
        $this->assertTrue($this->oA->isBarcodAllowedToPlay($this->oC, "20000"));
    }

    public function testThatIsBarcodValidWriteLogIfTheBarcodIsNotValid(){
        $sCampaignId = "1331111111_no_more_prize";
        $sTerminalNbr = "1";
        $this->init($sCampaignId, $sTerminalNbr, "2012-04-04");

        $oDateTimeInjector = new DateTimeInjector("2012-04-04 11:50:00");
        $this->oA->setDateTimeInjector($oDateTimeInjector);
        $this->oA->setNow("2012-04-04 11:50:00");

        // On recupere le nombre de logs initialement presents dans la table logs
        $initialRowcount = $this->getConnection()->getRowCount( "logs" );

        $this->assertFalse($this->oA->isBarcodValid($this->oC, "41999"));

        // On teste qu'il y a bien une nouvelle entrée dans les logs
        $this->assertEquals($initialRowcount + 1,
                            $this->getConnection()->getRowCount( "logs" ));

        // On teste les information du dernier log inséré en BDD
        $this->assertLastLog($sCampaignId, $sTerminalNbr, "2012-04-04 11:50:00",
                             DistributionComputer::PLAY_ERROR_INVALID_BARCOD,
                             "41999", "0", Vlog::LOG_TYPE_PLAY);
    }

    public function testThatIsBarcodAllowedToPlayWriteLogIfTheBarcodWasPlayerTooMuchTime(){
        $sCampaignId = "1331111111_no_more_prize";
        $sTerminalNbr = "1";
        $this->init($sCampaignId, $sTerminalNbr, "2012-04-04");

        $oDateTimeInjector = new DateTimeInjector("2012-04-04 10:30:00");
        $this->oA->setDateTimeInjector($oDateTimeInjector);
        $this->oA->setNow("2012-04-04 10:30:00");

        // On recupere le nombre de logs initialement presents dans la table logs
        $initialRowcount = $this->getConnection()->getRowCount( "logs" );

        $this->assertFalse($this->oA->isBarcodAllowedToPlay($this->oC, "42000"));

        // On teste qu'il y a bien une nouvelle entrée dans les logs
        $this->assertEquals($initialRowcount + 1,
                            $this->getConnection()->getRowCount( "logs" ));

        // On teste les information du dernier log inséré en BDD
        $this->assertLastLog($sCampaignId, $sTerminalNbr, "2012-04-04 10:30:00",
                             DistributionComputer::PLAY_ERROR_TOO_MUCH_PLAY,
                             "42000", "0", Vlog::LOG_TYPE_PLAY);
    }

    public function testThatIsBarcodAllowedToPlayAndValidReturnTrueIfTheSerialInputIsNotABarcod() {
        $this->init("1331109874_test", "1", "2012-04-06");

	$devConfigPath =  realpath(dirname(__FILE__) . '/../Fixtures/config_device_2_triggers.xml');
        $this->oA->setDeviceConfigPath($devConfigPath);

        $oDateTimeInjector = new DateTimeInjector("2012-04-06 11:50:00");
        $this->oA->setDateTimeInjector($oDateTimeInjector);
        $this->oA->setNow("2012-04-06 11:50:00");

        // On joue avec une chaine qui n'est pas un code barre et qui est
        // enregistré dans le fichier de conf
        $this->assertEquals(PlayerApi::VALID_BARCOD,
                            $this->oA->isBarcodAllowedToPlayAndValid($this->oC, "154212"));

        // ... puis avec un code barre valide
        $this->assertEquals(PlayerApi::VALID_BARCOD,
                            $this->oA->isBarcodAllowedToPlayAndValid($this->oC, "355570"));

        // ... puis avec un code barre invalide
        $this->assertEquals(PlayerApi::INVALID_BARCOD,
                           $this->oA->isBarcodAllowedToPlayAndValid($this->oC, "355500"));

        // ... puis avec un code barre valide mais deja utilise 2 fois
        $this->assertEquals(PlayerApi::TOO_MUCH_PLAY,
                           $this->oA->isBarcodAllowedToPlayAndValid($this->oC, "355568"));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIsCampaignHasTwiceTriggerTypeThrowsExceptionIfFirstArgIsEmpty(){
        $this->init("1331109874_test", "1", "2012-04-04");

        $fMethod = self::getMethod('isCampaignHasTwiceTriggerType');
        $fMethod->invokeArgs($this->oA, array(array()));
    }

    public function testIsCampaignHasTwiceTriggerTypeOutputIsOk(){
        $this->init("1331109874_test", "1", "2012-04-04");

        $fMethod = self::getMethod('isCampaignHasTwiceTriggerType');
        $aFixtures= array(
            (object)array(
                "action" => "gameinit",
                "string" => "BARCOD"
            ),
            (object)array(
                "action" => "gameinit",
                "string" => "GOULOTTE2"
            ),
        );

        $this->assertTrue( $fMethod->invokeArgs($this->oA, array($aFixtures)));

        $aFixtures= array(
            (object)array(
                "action" => "gamelaunch",
                "string" => "BARCOD"
            ),
            (object)array(
                "action" => "gameinit",
                "string" => "GOULOTTE2"
            ),
        );
        $this->assertFalse( $fMethod->invokeArgs($this->oA, array($aFixtures)));
    }

    public function testIsSerialnputNonBarcodTriggeroutputIsOk(){
        $this->init("1331109874_test", "1", "2012-04-04");
        $fMethod = self::getMethod('isSerialInputNonBarcodTrigger');

         $aFixtures= array(
            (object)array(
                "action" => "gameinit",
                "string" => "BARCOD"
            ),
            (object)array(
                "action" => "gameinit",
                "string" => "GOULOTTE2"
            )
        );

         $this->assertTrue( $fMethod->invokeArgs($this->oA, array("GOULOTTE2",
                                                                  $aFixtures) ) );


         $this->assertFalse( $fMethod->invokeArgs($this->oA, array("3944",
                                                                   $aFixtures) ) );
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRecordUserDataThrowsExceptionIfDestinationIsNotWritable()
    {
        $this->init("1331109874_test", "1", "2012-04-04");
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 11:12:34");
        $this->oA->setDateTimeInjector($oDateTimeInjector);
        $this->oA->setNow("2012-04-04 11:12:34");

        $oLotteryResult = new LotteryResult();
        $oLotteryResult->buildForLost(DistributionComputer::PLAY_ERROR_NOT_INSTANT_WIN);
        $this->oA->recordUserData("/unknown/directory/file.csv", $this->oC,
                                  $oLotteryResult, "toto@gmail.com", "1234");
    }

    public function testRecordUserDataCanCreateFileAndAppendData()
    {
        $this->init("1331109874_test", "1", "2012-04-04");
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 11:12:34");
        $this->oA->setDateTimeInjector($oDateTimeInjector);
        $this->oA->setNow("2012-04-04 11:12:34");

        // On va generer un nom de fichier unique qui n'existe pas
        $sFilePath = tempnam($this->sTempDir, "CSV");
        unlink($sFilePath);
        $this->assertFalse(file_exists($sFilePath));

        // Puis on va demander a recordUserData d'enregistrer un evenement dedans
        $oLotteryResult = new LotteryResult();
        $oLotteryResult->buildForLost(DistributionComputer::PLAY_ERROR_NOT_INSTANT_WIN);
        $this->oA->recordUserData($sFilePath, $this->oC, $oLotteryResult,
                                  "toto@gmail.com", "1234");

        $this->assertTrue(file_exists($sFilePath));

        // On enregistre un second evenement dans le fichier pour tester que le
        // append fonctionne.
        $oDateTimeInjector = new DateTimeInjector("2012-04-04 11:40:00");
        $this->oA->setDateTimeInjector($oDateTimeInjector);
        $this->oA->setNow("2012-04-04 11:40:00");

        $oLotteryResult = new LotteryResult();
        $oInstantWin = new InstantWin();
        $oInstantWin->setFromDB((object) array(
            "id" => "10002",
            "id_prize" => "1332320329_15-euros",
            "_datetime" => "2012-04-04 11:12:34",
            "isValid" => "1",
            "isJackpot" => "0",
            "isFixedPrize" => "0"
        ));
        $oLotteryResult->buildForWin($oInstantWin, array(), array());
        $this->oA->recordUserData($sFilePath, $this->oC, $oLotteryResult,
                                  "toto@gmail.com", "2222");


        // Maintenant, on va ouvrir le fichier CSV et verifier le contenu
        $fCSV = fopen($sFilePath, "r");
        $this->assertNotEquals(FALSE, $fCSV);
        $this->assertEquals(array("1333530754", "1234", "toto@gmail.com",
                                  "0", "Test", "1"),
                            fgetcsv($fCSV));
        $this->assertEquals(array("1333532400", "2222", "toto@gmail.com",
                                  "1332320329_15-euros", "Test", "1"),
                            fgetcsv($fCSV));
        $this->assertFalse(fgetcsv($fCSV));
        fclose($fCSV);
    }

    public function testIsUserDataAlreadyRegistered() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $sFile = realpath(dirname(__FILE__).'/../Fixtures/records1.csv');
        $this->assertFalse($this->oA->isUserDataAlreadyRegistered($sFile, ""));
        $this->assertFalse($this->oA->isUserDataAlreadyRegistered($sFile, "0"));
        $this->assertTrue($this->oA->isUserDataAlreadyRegistered($sFile, "2222"));
        $this->assertTrue($this->oA->isUserDataAlreadyRegistered($sFile, "1234"));
        $this->assertFalse($this->oA->isUserDataAlreadyRegistered($sFile, "111"));
    }

    public function testBackupCsvToSdCard() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $sSrc = realpath(dirname(__FILE__).'/../Fixtures/records1.csv');
        $sDst = $this->sTempDir . '/backup.csv';
        $this->assertFalse(file_exists($sDst));
        $this->assertTrue($this->oA->backupCsvtoSdcard($sSrc, $sDst));
        $this->assertTrue(file_exists($sDst));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testBackupCsvToSdCardThrowsExceptionIfDestDirectoryDoesNotExist() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $sSrc = realpath(dirname(__FILE__).'/../Fixtures/records1.csv');
        $sDst = "/unknown/directory/backup.csv";
        $this->assertFalse(file_exists($sDst));
        $this->assertFalse($this->oA->backupCsvtoSdcard($sSrc, $sDst));
        $this->assertFalse(file_exists($sDst));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testBackupCsvToSdCardThrowsExceptionIfSourceFileDoesNotExist() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $sSrc = "/unknown/directory/test.csv";
        $sDst = $this->sTempDir . '/backup.csv';
        $this->assertFalse(file_exists($sDst));
        $this->assertFalse($this->oA->backupCsvtoSdcard($sSrc, $sDst));
        $this->assertFalse(file_exists($sDst));
    }

    public function testGetPreviousWinnersBarcode()
    {
        $this->init("1331109874_test", "1", "2012-04-09");

        $fMethod = self::getMethod("getPreviousWinnersBarcode");

        $aRes = $fMethod->invokeArgs($this->oA, array("1331109874_test"));
        $this->assertEquals(array("355568"), $aRes);

        $aRes = $fMethod->invokeArgs($this->oA, array("1331111111_no_more_prize"));
        $this->assertEquals(array(), $aRes);
    }

    public function testLogFinalDraw()
    {
        $this->init("1331109874_test", "1", "2012-04-09");
        $fLog = self::getMethod("logFinalDraw");

        // On recupere le nombre de logs initialement presents dans la table logs
        $initialRowcount = $this->getConnection()->getRowCount( "logs" );

        $oDate = new Zend_Date();
        $oDate->set( strtotime( "2012-04-09 18:42:50" ), Zend_Date::TIMESTAMP );

        $oWinner = new StdClass();
        $oWinner->line_nbr = 208;
        $oWinner->barcod = "4677";
        $oWinner->email = "toto@gmail.com";
        $fLog->invokeArgs($this->oA, array($this->oC, $oWinner, $oDate));

        // On teste qu'il y a bien une nouvelle entrée dans les logs
        $this->assertEquals($initialRowcount + 1,
                            $this->getConnection()->getRowCount( "logs" ));

        $sExpectedData = 'a:2:{s:8:"line_nbr";i:208;s:5:"email";s:14:"toto@gmail.com";}';
        $this->assertLastLog("1331109874_test", "1", "2012-04-09 18:42:50",
                             $sExpectedData, "4677", "0", Vlog::LOG_TYPE_FINAL_DRAW);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetPlayersFromCSVThrowsExceptionIfFileDoesNotExist()
    {
        $this->init("1331109874_test", "1", "2012-04-09");

        $fMethod = self::getMethod("getPlayersFromCSV");
        $fMethod->invokeArgs($this->oA,
                             array("/unknown/directory/file.csv", array()));
    }


    public function testGetPlayersFromCSV()
    {
        $this->init("1331109874_test", "1", "2012-04-09");

        $fMethod = self::getMethod("getPlayersFromCSV");
        $sFile = realpath(dirname(__FILE__).'/../Fixtures/records2.csv');

        // Test without barcod filter
        $aPlayers = $fMethod->invokeArgs($this->oA, array($sFile, array()));
        $aExpectedResults = array(
            (object) array(
                "line_nbr" => 1,
                "barcod" => "355568",
                "email" => "toto@gmail.com"
            ),
            (object) array(
                "line_nbr" => 2,
                "barcod" => "355569",
                "email" => "titi@gmail.com"
            ),
            (object) array(
                "line_nbr" => 3,
                "barcod" => "355570",
                "email" => "tutu@gmail.com"
            ),
            (object) array(
                "line_nbr" => 4,
                "barcod" => "355571",
                "email" => "tata@gmail.com"
            ),
            (object) array(
                "line_nbr" => 5,
                "barcod" => "355571",
                "email" => "tata2@gmail.com"
            )
        );
        $this->assertEquals($aExpectedResults, $aPlayers);

        // Test without barcod filter
        $aBlackList = array("355568", "355570", "355571");
        $aPlayers = $fMethod->invokeArgs($this->oA, array($sFile, $aBlackList));
        $aExpectedResults = array(
            (object) array(
                "line_nbr" => 2,
                "barcod" => "355569",
                "email" => "titi@gmail.com"
            )
        );
        $this->assertEquals($aExpectedResults, $aPlayers);
    }

    /**
     * @expectedException DomainException
     */
    public function testLaunchFinalDrawThrowsExceptionIfNoEligibleEntry() {
        $this->init("1331109874_test", "1", "2012-04-09");
        $sFile = realpath(dirname(__FILE__).'/../Fixtures/records3.csv');
        $oWinner = $this->oA->launchFinalDraw($this->oC, $sFile);
    }

    public function testLaunchFinalDraw() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $oDateTimeInjector = new DateTimeInjector("2012-04-09 19:15:00");
        $this->oA->setDateTimeInjector($oDateTimeInjector);
        $this->oA->setNow("2012-04-09 19:15:00");

        $sFile = realpath(dirname(__FILE__).'/../Fixtures/records2.csv');
        $oWinner = $this->oA->launchFinalDraw($this->oC, $sFile);

        $this->oPDO->start()
            ->query("SELECT * FROM  `logs` ORDER BY id DESC LIMIT 1", $aLogs)
            ->commit();

        $this->assertEquals($aLogs[0]['id'], $oWinner->logid);
        $this->assertEquals($oDateTimeInjector->getNow(), $oWinner->time);
        $this->assertTrue(isset($oWinner->line_nbr));
        $this->assertTrue(isset($oWinner->barcod));
        $this->assertTrue(isset($oWinner->email));

        $aExpectedData = array(
            "line_nbr" => $oWinner->line_nbr,
            "email" => $oWinner->email
        );

        $this->assertLastLog("1331109874_test", "1", "2012-04-09 19:15:00",
                             serialize($aExpectedData), $oWinner->barcod,
                             "0", Vlog::LOG_TYPE_FINAL_DRAW);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testLoadCSVRecordsThrowException() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $fMethod = self::getMethod("loadCSVRecords");

        $sFile = realpath('/unknown/directory/unknown-file.csv');
        $aExpectedResult = array();
        $this->assertEquals($aExpectedResult,
                            $fMethod->invokeArgs($this->oA, array($sFile)));
    }

    public function testLoadCSVRecords() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $fMethod = self::getMethod("loadCSVRecords");

        $sFile = realpath(dirname(__FILE__).'/../Fixtures/records1.csv');
        $aExpectedResult = array(
            "1234" => array("1333530754", "1234",
                            "toto@gmail.com", "0", "Test", "1"),
            "2222" => array("1333532400", "2222", "toto@gmail.com",
                            "1332320329_15-euros", "Test", "1")
        );
        $this->assertEquals($aExpectedResult,
                            $fMethod->invokeArgs($this->oA, array($sFile)));
    }


    /**
     * @expectedException DomainException
     */
    public function testWriteRecordsIntoFileThrowsExceptionIfCSVRowsIsNotAnArray() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $fMethod = self::getMethod("writeRecordsIntoFile");
        $sFile = $this->sTempDir . '/test_export.csv';
        $fMethod->invokeArgs($this->oA, array("test", $sFile));
    }

    /**
     * @expectedException DomainException
     */
    public function testWriteRecordsIntoFileThrowsExceptionIfCSVRowsDoesNotContainArrays() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $fMethod = self::getMethod("writeRecordsIntoFile");
        $sFile = $this->sTempDir . '/test_export.csv';
        $fMethod->invokeArgs($this->oA, array(array("test"), $sFile));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWriteRecordsIntoFileThrowsExceptionIfCannotCreateFile() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $fMethod = self::getMethod("writeRecordsIntoFile");
        $sFile = '/unknown/directory/test_export.csv';
        $fMethod->invokeArgs($this->oA, array(array("test"), $sFile));
    }

    public function testWriteRecords() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $fMethod = self::getMethod("writeRecordsIntoFile");
        $sFile = $this->sTempDir . '/test_export.csv';
        $this->assertFalse(file_exists($sFile));

        // On fait une premiere ecriture dans le fichier qui n'existe pas
        $sData = array(
            "7228" => array("1333530001", "7228",
                            "test1@gmail.com", "0", "Test", "1"),
            "3212" => array("1333534600", "3212", "test2@gmail.com",
                            "1332320329_15-euros", "Test", "1")
        );
        $fMethod->invokeArgs($this->oA, array($sData, $sFile));

        // On va verifier que le fichier a bien été créé
        $this->assertTrue(file_exists($sFile));

        // Maintenant, on va ouvrir le fichier CSV et verifier le contenu
        $fCSV = fopen($sFile, "r");
        $this->assertNotEquals(FALSE, $fCSV);
        $this->assertEquals(array("1333530001", "7228",
                            "test1@gmail.com", "0", "Test", "1"),
                            fgetcsv($fCSV));
        $this->assertEquals(array("1333534600", "3212", "test2@gmail.com",
                            "1332320329_15-euros", "Test", "1"),
                            fgetcsv($fCSV));
        $this->assertFalse(fgetcsv($fCSV));
        fclose($fCSV);

        // On fait une seconde ecriture dans le fichier
        $sData = array(
            "1111" => array("1322000000", "1111",
                            "test3@gmail.com", "0", "Test", "1"),
            "2222" => array("1339999999", "2222", "test4@gmail.com",
                            "1332320329_15-euros", "Test", "1")
        );
        $fMethod->invokeArgs($this->oA, array($sData, $sFile));

        // Puis on verifie que le fichier a bien été écrasé et contient
        // les bonnes données
        $fCSV = fopen($sFile, "r");
        $this->assertNotEquals(FALSE, $fCSV);
        $this->assertEquals(array("1322000000", "1111",
                            "test3@gmail.com", "0", "Test", "1"),
                            fgetcsv($fCSV));
        $this->assertEquals(array("1339999999", "2222", "test4@gmail.com",
                            "1332320329_15-euros", "Test", "1"),
                            fgetcsv($fCSV));
        $this->assertFalse(fgetcsv($fCSV));
        fclose($fCSV);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testMergeDataFileForFinalDrawThrowExceptionIfFile1DoesNotExist() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $sSrcFile1 = realpath(dirname(__FILE__).'/../Fixtures/records2.csv');
        $sSrcFile2 = "/unknown/directory/records404.csv";

        $this->oA->mergeDataFileForFinalDraw($this->oC, $sSrcFile1, $sSrcFile2);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testMergeDataFileForFinalDrawThrowExceptionIfFile2DoesNotExist() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $sSrcFile1 = "/unknown/directory/unknown-records.csv";
        $sSrcFile2 = realpath(dirname(__FILE__).'/../Fixtures/records2.csv');

        $this->oA->mergeDataFileForFinalDraw($this->oC, $sSrcFile1, $sSrcFile2);
    }

    public function testMergeDataFileForFinalDraw() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $sSrcFile1 = realpath(dirname(__FILE__).'/../Fixtures/records2.csv');
        $sSrcFile2 = realpath(dirname(__FILE__).'/../Fixtures/records4.csv');

        $sFile1 = $this->sTempDir . "/file1.csv";
        $sFile2 = $this->sTempDir . "/file2.csv";
        $this->assertTrue(copy($sSrcFile1, $sFile1));
        $this->assertTrue(copy($sSrcFile2, $sFile2));

        $sMergedFile = $this->oA->mergeDataFileForFinalDraw($this->oC, $sFile1, $sFile2);

        // On verifie que le fichier a bien été créé
        $this->assertTrue(file_exists($sMergedFile));

        // On va l'ouvrir pour recuperer tous les barcod + email contenus dedans
        // pour verifier qu'ils sont ok
        $fCSV = fopen($sMergedFile, "r");
        $this->assertNotEquals(FALSE, $fCSV);
        $aBarcodes = array();
        $aEmails = array();
        while (($aRow = fgetcsv($fCSV)) !== FALSE) {
            $aBarcodes[] = $aRow[PlayerApi::DRAW_CSV_COL_USER_BARCOD];
            $aEmails[] = $aRow[PlayerApi::DRAW_CSV_COL_USER_EMAIL];
        }
        fclose($fCSV);

        sort($aEmails);
        sort($aBarcodes);

        $aExpectedEmails = array("toto@gmail.com", "titi@gmail.com",
                                 "tutu@gmail.com", "tata@gmail.com",
                                 "test41@gmail.com", "test42@gmail.com",
                                 "test43@gmail.com");

        $aExpectedBarcodes = array("355568", "355569", "355570", "355571",
                                   "355572", "355573", "355574");

        sort($aExpectedEmails);
        sort($aExpectedBarcodes);

        $this->assertEquals($aExpectedEmails, $aEmails);
        $this->assertEquals($aExpectedBarcodes, $aBarcodes);
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


    /**
     * Remove a directory recursively
     */
    protected function rrmdir($dir) {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file))
                $this->rrmdir($file);
            else
                unlink($file);
        }
        rmdir($dir);
    }


}

