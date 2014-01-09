<?php

/**
 * IntantWinDistribution test case
 * 
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class IntantWinDistributionDbTest extends PHPUnit_Extensions_Database_TestCase {
    
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
     * Prepares the environment before running a test.
     */
    public function init() {
        
        $this->oPDO = new PDOUtils(DB_DBNAME, DB_HOST, DB_USER, DB_PASSWD );
        
        $this->oLogger = Log::factory('file', 'dtc.log', 'TEST');
        
        $this->oL = new ConfigLoader();
        $this->oL->setToday($this->sToday);
        $this->oL->setPDO($this->oPDO);
        $this->oC = $this->oL->getCampaignData( $this->sCampaignId, $this->sTerminalNbr);
        
        $this->oD = new InstantWinDistribution();
        $this->oD->setToday($this->sToday);
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

    /**
     * @expectedException DomainException
     */
    public function testLoadConfigDeviceThrowsExceptionIfFileNotFound(){
        $this->init();
        $this->oL->loadConfigDevice( $this->oC->id, "C:\\Program Files\\nimportequoi\\" );
    }
    
    public function testLoadConfigDeviceOutputIsOk() {
        $this->init();
        $aExpected= array(
            (object)array(
                "action" => "gameinit",
                "string" => "GOULOTTE2"
            ),
            (object)array(
                "action" => "gamelaunch",
                "string" => "GOULOTTE2"
            ),
        );
	$devConfigPath =  realpath(dirname(__FILE__) . '/../Fixtures/config_device.xml');
	$this->assertFileExists( $devConfigPath );
	$aResult = $this->oL->loadConfigDevice( $this->oC->id, "", $devConfigPath );
        
        $this->assertInternalType( "array", $aResult, "loadConfigDevice must return an array" );
        $this->assertEquals( $aExpected, $aResult, "loadConfigDevice must return the expected array" );
    }
    
    
    public function testRegisterLogReallyInsertInDB() {

        $this->init();

        $iInit = $this->getConnection()->getRowCount( "logs" );

        $sResult = $this->oD->registerLog( new Vlog($this->oC->id, $this->sTerminalNbr, InstantWinDistribution::PLAY_ERROR_INVALID_BARCOD ), TRUE );

        $this->assertTrue( intval( $sResult ) > 0 );
        $this->assertTrue( ++$iInit == $this->getConnection()->getRowCount( "logs" ) ); 
    }

    public function testOuputErrorReallyOutputError() {

        $this->init();

        $oResult = $this->oD->outputError("Error cause");

        $this->assertTrue( get_class( $oResult )=== "LotteryResult", "ouputError must return LotteryResult instance" );
        $this->assertTrue( $oResult->lostCause === "Error cause" );
        $this->assertFalse( $oResult->isWinner );
    }


    public function testIsInAllowedTimeOutputIsOk(){
        $this->init();
        
        $oFixtures = new Schedule();
        $oFixtures->setFromDb( (object) array(
        "morning" => (object)array(
            "day" => $this->sToday,
            "begin" => "10:00:00",
            "end" => "12:00:00"
            )
        ) );
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday ." 09:00:00" ), Zend_Date::TIMESTAMP );
        
        $this->assertFalse( $this->oD->isInAllowedTime( $oFixtures, $oNow ) );
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday ." 10:05:00" ), Zend_Date::TIMESTAMP );
        
        $this->assertTrue( $this->oD->isInAllowedTime( $oFixtures, $oNow ) );
        
        $oFixtures = new Schedule();
        $oFixtures->setFromDb( (object) array(
            "morning"=>(object) array(
                "day" => $this->sToday,
                "begin" => "10:00:00",
                "end" => "12:00:00"
            ),
            "afternoon"=> (object) array(
                "day" => $this->sToday,
                "begin" => "14:00:00",
                "end" => "16:00:00"
            )
        ) );

        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday ." 09:00:00" ), Zend_Date::TIMESTAMP );

        $this->assertFalse( $this->oD->isInAllowedTime( $oFixtures, $oNow ) );

        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday ." 10:05:00" ), Zend_Date::TIMESTAMP );

        $this->assertTrue( $this->oD->isInAllowedTime( $oFixtures, $oNow ) );

        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday ." 12:00:01" ), Zend_Date::TIMESTAMP );

        $this->assertFalse( $this->oD->isInAllowedTime( $oFixtures, $oNow ) );

        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday ." 14:00:00" ), Zend_Date::TIMESTAMP );

        $this->assertTrue( $this->oD->isInAllowedTime( $oFixtures, $oNow ) );

        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday ." 16:00:01" ), Zend_Date::TIMESTAMP );

        $this->assertFalse( $this->oD->isInAllowedTime( $oFixtures, $oNow ) );
    }

    public function testGetValidInstantWin() {

        $this->init();

        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( '1', '{$this->sToday} 12:00:00', 1, 1, 0 );" )
            ->commit();

        //precondition
        $this->assertEquals( 1, $this->getConnection()->getRowCount( 'winningtime_computed' ), "winningtime_computed must have exactly one row before the test" );

        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday ." 14:00:00" ), Zend_Date::TIMESTAMP );

        $aResult = $this->oD->getValidInstantWin( InstantWinDistribution::INSTANT_WIN_TYPE_JACKPOT, $oNow, $this->sToday );
        $this->assertInternalType( "array", $aResult, "isThereInstantWinRegistered must return an array" );
        $this->assertNotEmpty( $aResult, "isThereInstantWinRegistered must return here one instantWin" );
        $this->assertCount(1, $aResult, "isThereInstantWinRegistered must return here exactly one instantWin");

        $oNow->set( strtotime( $this->sToday ." 11:59:59" ), Zend_Date::TIMESTAMP );

        $aResult = $this->oD->getValidInstantWin( InstantWinDistribution::INSTANT_WIN_TYPE_JACKPOT, $oNow, $this->sToday );
        $this->assertInternalType( "array", $aResult, "isThereInstantWinRegistered must return an array" );
        $this->assertEmpty( $aResult, "If we call isThereInstantWinRegistered before the regsitered instantWin, it must return an empty array"  );

        $oNow->set( strtotime( $this->sToday ." 12:01:01" ), Zend_Date::TIMESTAMP );

        $this->assertEmpty( $this->oD->getValidInstantWin( InstantWinDistribution::INSTANT_WIN_TYPE_NORMAL, $oNow, $this->sToday ));
        $this->assertEmpty( $this->oD->getValidInstantWin( InstantWinDistribution::INSTANT_WIN_TYPE_FIXED_PRIZE, $oNow, $this->sToday ));
        $this->assertNotEmpty( $this->oD->getValidInstantWin( InstantWinDistribution::INSTANT_WIN_TYPE_JACKPOT_OR_FIXED, $oNow, $this->sToday ));
    }

    public function testGetStrongestInstantWin(){
        $this->init();
        
        $aFixtures = array(
            array(
               "id" => "3",
               "id_prize" => "3",
               "_datetime" => "",
               "isValid" => "1",
               "isJackpot" => "0",
               "isFixedPrize" => "0"
            ),
            array(
               "id" => "2",
               "id_prize" => "2",
               "_datetime" => "",
               "isValid" => "1",
               "isJackpot" => "0",
               "isFixedPrize" => "1"
            ),
            array(
               "id" => "1",
               "id_prize" => "1",
               "_datetime" => "",
               "isValid" => "1",
               "isJackpot" => "1",
               "isFixedPrize" => "0"
            )
        );
        
        $this->assertEquals( 2, $this->oD->getStrongestInstantWinIndex( $aFixtures ) );
        
        array_pop($aFixtures);
        
        $this->assertEquals( 1, $this->oD->getStrongestInstantWinIndex( $aFixtures ) );
        
        $aFixtures = array(
            array(
               "id" => "3",
               "id_prize" => "3",
               "_datetime" => "",
               "isValid" => "1",
               "isJackpot" => "1",
               "isFixedPrize" => "0"
            ),
            array(
               "id" => "2",
               "id_prize" => "2",
               "_datetime" => "",
               "isValid" => "1",
               "isJackpot" => "0",
               "isFixedPrize" => "0"
            ),
            array(
               "id" => "1",
               "id_prize" => "1",
               "_datetime" => "",
               "isValid" => "1",
               "isJackpot" => "0",
               "isFixedPrize" => "1"
            )
        );
        
        $this->assertEquals( 0, $this->oD->getStrongestInstantWinIndex( $aFixtures ) );
    }

    public function testInvalidateInstantWin(){
        
        $this->init();
        
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->exec( "INSERT INTO `winningtime_computed` ( `id`, `id_prize`, `_datetime`, `isValid`, `isJackpot`, `isFixedPrize` ) VALUES ( 1, '1', '{$this->sToday} 12:00:00', 1, 1, 0 );" )
            ->commit();
        
        //precondition
        $this->assertEquals( 1, $this->getConnection()->getRowCount( 'winningtime_computed' ), "winningtime_computed must have exactly one row before the test" );
        
        $oFixture = new InstantWin();
        $oFixture->setFromDB((object)array(
            "id" => "1",
            "id_prize" => "1",
            "_datetime" => "{$this->sToday} 12:00:00",
            "isValid" => 1,
            "isJackpot" => 1,
            "isFixedPrize" => 0
        ));
        
        $this->oPDO->start();
        $iResult = $this->oD->invalidateInstantWin( $oFixture );
        $this->oPDO->commit();
         
        $this->assertEquals(1, $iResult);
        
        $aResult=array();
        
        $this->oPDO->start()
            ->query( "SELECT * FROM `winningtime_computed` WHERE `id`=1;",  $aResult )
            ->commit();
        
        $this->assertInternalType( "array", $aResult );
        $this->assertCount( 1, $aResult );
        $this->assertEquals( 0, $aResult[0]["isValid"] );

        $iResult = $this->oD->invalidateInstantWin( $oFixture );
        $this->assertEquals(0, $iResult);
    }

    function testCheckQuantity(){
        $this->init();

        $oIW = new InstantWin();
        $oIW->setFromDB((object)array(
            "id" => 1,
            "id_prize" => "1332320329_50-euros",
            "_datetime" => "{$this->sToday} 12:00:00",
            "isValid" => 1,
            "isJackpot" => 0,
            "isFixedPrize" => 1
        ));
        $aGavePrize = $this->oD->getGavePrizeFromLog( $this->oC, $this->sTerminalNbr );
        $this->assertTrue( $this->oD->checkQuantity( $oIW,
                                                     $this->oC->curPrizeHasDotation,
                                                     $aGavePrize));
        $oIW = new InstantWin();
        $oIW->setFromDB((object)array(
            "id" => 1,
            "id_prize" => "1331109979_cle-usb",
            "_datetime" => "{$this->sToday} 12:00:00",
            "isValid" => 1,
            "isJackpot" => 0,
            "isFixedPrize" => 1
        ));

        $this->assertTrue( $this->oD->checkQuantity( $oIW,
                                                     $this->oC->curPrizeHasDotation,
                                                     $aGavePrize));
    }

    /**
     * @expectedException DomainException
     */
    function testCheckQuantityThrowExceptionIfPrizeNotFound(){
        $this->init();

        $oIW = new InstantWin();
        $oIW->setFromDB((object)array(
            "id" => 1,
            "id_prize" => "nimportequoi",
            "_datetime" => "{$this->sToday} 12:00:00",
            "isValid" => 1,
            "isJackpot" => 0,
            "isFixedPrize" => 1
        ));
        $aGavePrize = $this->oD->getGavePrizeFromLog( $this->oC, $this->sTerminalNbr );
        $this->oD->checkQuantity( $oIW, $this->oC->curPrizeHasDotation, $aGavePrize );
    }

    function testUpdateAmount(){
        $this->init();

        $oPHD = new stdClass();

        $this->oPDO->start()
            ->getRow( "prizehasdotation", " `id_prize`='1331109979_cle-usb' AND `_date`='2012-04-04' ", $oPHD )
            ->commit();

        $oIW = new InstantWin();
        $oIW->setFromDB((object)array(
            "id" => 1,
            "id_prize" => "1331109979_cle-usb",
            "_datetime" => "{$this->sToday} 12:00:00",
            "isValid" => 1,
            "isJackpot" => 0,
            "isFixedPrize" => 1
        ));

        $this->oPDO->start();
        $this->oD->updateAmount( $oIW, $this->oC->curPrizeHasDotation );
        $this->oPDO->commit();

        $oPHD2 = new stdClass();
        $this->oPDO->start()
            ->getRow( "prizehasdotation", " `id_prize`='1331109979_cle-usb' AND `_date`='2012-04-04' ", $oPHD2 )
            ->commit();

        $this->assertTrue( ( (int)$oPHD->alreadydeal + 1 ) == ( (int)$oPHD2->alreadydeal ) );
    }
}

