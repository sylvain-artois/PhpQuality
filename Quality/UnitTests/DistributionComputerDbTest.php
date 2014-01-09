<?php

/**
 * Distribution test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class DistributionComputerDbTest extends PHPUnit_Extensions_Database_TestCase {
    
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
    
    /**
     * @covers DistributionComputer::getCampaign
     */
    public function testGetCampaign(){
        $this->init();
        
        $this->assertInstanceOf("Campaign",$this->oD->getCampaign());
        
        $this->oD->resetCampaign();
        $this->assertInstanceOf("Campaign",$this->oD->getCampaign($this->sCampaignId,$this->sTerminalNbr));
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @covers DistributionComputer::getCampaign
     */
    public function testGetCampaignThrowExceptionIfBadParam(){
        $this->init();    
        $this->oD->resetCampaign();
        $this->oD->getCampaign();
    }
    
    /**
     * @covers DistributionComputer::getGavePrizeFromLog
     */
    public function testGavePrizeFromLog(){
        $this->init();    
        $aExpectedResult=array(
            "1332320329_15-euros"=>1,
            "1332320329_50-euros"=>1
        );
        
        $aGavePrize = $this->oD->getGavePrizeFromLog($this->oC, $this->sTerminalNbr);
        $this->assertInternalType('array', $aGavePrize);
		
        $this->assertTrue($aExpectedResult==$aGavePrize);
    }
    
    /**
     * @covers DistributionComputer::emptyWinningtimeComputeForThatDay
     */
    public function testEmptyWinningtimeComputeForThatDay(){
        
        $this->init();
        
        //Precondition
        $this->assertEquals(0, $this->getConnection()->getRowCount('winningtime_computed'), "winningtime_computed is not empty");
        
        //On insère une entrée
        $sWinningTimeId="";
        $this->oPDO->start()
            ->insert("winningtime_computed", array("id_prize","_datetime","isValid"), array("'0'","'2012-04-04 00:00:00'","1"), $sWinningTimeId )
            ->commit();
        
        $this->assertNotNull($sWinningTimeId);
        $this->assertTrue(intval($sWinningTimeId)>0);
        
        $this->oD->emptyWinningtimeComputeForThatDay($this->sToday);
        
        $this->assertEquals(0, $this->getConnection()->getRowCount('winningtime_computed'), "winningtime_computed has not exactly one row");
    }
    
    /**
     * @covers DistributionComputer::howManyTimeThatPrizeWereGived
     */
    public function testHowManyTimeThatPrizeWereGived(){
        $this->init();
        
        $aLogs = array(
            array(
                "id"=>1,
                "id_campaign"=>"1331109874_test",
                "id_terminal"=>1,
                "logtype"=>9,
                "_datetime"=>"2012-04-04 10:00:00",
                "tickets"=>    NULL,
                "data"=> NULL,
                "barcod"=> "355568",
                "prizeid"=>"0"
            ),
            array(
                "id"=>2,
                "id_campaign"=>"1331109874_test",
                "id_terminal"=>1,
                "logtype"=>9,
                "_datetime"=>"2012-04-04 11:00:00",
                "tickets"=>    NULL,
                "data"=> NULL,
                "barcod"=> "355569",
                "prizeid"=>"1332320329_15-euros"
            ),
            array(
                "id"=>3,
                "id_campaign"=>"1331109874_test",
                "id_terminal"=>1,
                "logtype"=>9,
                "_datetime"=>"2012-04-04 11:30:00",
                "tickets"=>    NULL,
                "data"=> NULL,
                "barcod"=> "355570",
                "prizeid"=>"0"
            ),
            array(
                "id"=>4,
                "id_campaign"=>"1331109874_test",
                "id_terminal"=>1,
                "logtype"=>9,
                "_datetime"=>"2012-04-04 11:35:00",
                "tickets"=>    NULL,
                "data"=>NULL,
                "barcod"=> "355570",
                "prizeid"=>"1332320329_50-euros"
            ),
        );
        
        $this->assertEquals($this->oD->howManyTimeThatPrizeWereGived("1331109979_voyage-aux-seychelles", $aLogs),0);
    }
    
    /**
     * @covers DistributionComputer::computeJackpotAmount
     */
    public function testComputeJackpotAmountReturnZeroIfNotJackpotPredef() {
    	
        $this->init();
        
        $oPrizeHasDotation = $this->oD->getPrizeHasDotationForThatPrize( $this->oC->jackpotDesc->prize_id, $this->oC->curPrizeHasDotation );
        
        $this->oC->jackpotType = Campaign::JACKPOT_NONE;
        $this->assertEquals( $this->oD->computeJackpotAmount( $this->oC, $oPrizeHasDotation, 0 ), 0 );
        
        $this->oC->jackpotType = Campaign::JACKPOT_PREDEF;
        $this->oC->jackpotDesc="";
        $this->assertEquals( $this->oD->computeJackpotAmount($this->oC, $oPrizeHasDotation, 0 ), 0 );
    }
    
    /**
     * @covers DistributionComputer::computeJackpotAmount
     */
    public function testComputeJackpotAmountReturnsZeroIfBadParam(){
        
        $this->init();
        
        $oPrizeHasDotation = $this->oD->getPrizeHasDotationForThatPrize( $this->oC->jackpotDesc->prize_id, $this->oC->curPrizeHasDotation );
        
        $oPHD = new PrizeHasDotation();
		$oPHD->setFromDB((object)array(
        	"id"=>"",
            "id_prize"=>"", 
            "id_dotation"=>"", 
            "alreadydeal"=>0,
            "winningtime"=>"",
            "_date" => $this->sToday,
            "amount" => 0
        ));
		
        $this->assertEquals( $this->oD->computeJackpotAmount( $this->oC, new PrizeHasDotation(), 0 ), 0 );
        $this->assertEquals( $this->oD->computeJackpotAmount( $this->oC, $oPHD, 0 ), 0 );
        $this->assertEquals( $this->oD->computeJackpotAmount( $this->oC, $oPrizeHasDotation, 999999 ), 0 );
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error
     * @covers DistributionComputer::computeJackpotAmount
     */
    public function testComputeJackpotAmountThrowsErrorIfBadParam() {
    	
        $this->init();
        
        $this->assertEquals( $this->oD->computeJackpotAmount( $this->oC, "", 0 ), 0 );
    }
    
    /**
     * @covers DistributionComputer::computeJackpotAmount
     */
    public function testComputeJackpotAmount() {
    	
        $this->init();
        
        $oPrizeHasDotation = $this->oD->getPrizeHasDotationForThatPrize( $this->oC->jackpotDesc->prize_id, $this->oC->curPrizeHasDotation );
        $this->assertEquals( $this->oD->computeJackpotAmount( $this->oC, $oPrizeHasDotation, 0 ), 1 );
    }
    
    /**
     * @covers DistributionComputer::computeJackpotTime
     */
    public function testComputeJackpotTime(){
        
        $this->init();
        
        $oJackPot = new Jackpot();
        $oJackPot->prize_id="1";
        $oJackPot->time_start="17:00:00";
        $oJackPot->time_end="17:00:00";
        
        $aResult = $this->oD->computeJackpotTime($oJackPot, 1);
        $this->oLogger->log($aResult[0]);
        
        $sWantedTime = $this->sToday." 17:00:00";
        
        $this->assertInternalType("array",$aResult);
        $this->assertTrue( count($aResult)===1 );
        $this->assertEquals( $sWantedTime, $aResult[0] );
        
        $this->assertEmpty($this->oD->computeJackpotTime($oJackPot, 0));
        
        $oJackPot = new Jackpot();
        $oJackPot->prize_id="1";
        $oJackPot->time_start="17:00:00";
        $oJackPot->time_end="17:05:00";
        
        $aResult = $this->oD->computeJackpotTime($oJackPot, 1);
        $this->oLogger->log($aResult[0]);
        
        $this->assertInternalType("array",$aResult);
        $this->assertTrue( count($aResult)===1 );
        $this->assertGreaterThanOrEqual( strtotime( $this->sToday. " " .$oJackPot->time_start), strtotime($aResult[0]) );
        $this->assertLessThanOrEqual(strtotime( $this->sToday. " " .$oJackPot->time_end), strtotime($aResult[0]) );
    }
    
    /**
     * @covers DistributionComputer::insertJackpotInstantWin
     */
    public function testInsertJackpotInstantWin(){
        
        $this->init();
        $this->assertEmpty($this->oD->insertJackpotInstantWin( $this->oC->jackpotDesc ,array(), 0));
        
        //Precondition
        $this->assertEquals(0, $this->getConnection()->getRowCount('winningtime_computed'), "winningtime_computed is not empty");
        
        $aId = $this->oD->insertJackpotInstantWin( $this->oC->jackpotDesc ,array("{$this->sToday} 17:00:00"), 1);
        
        $this->assertEquals(count($aId),1);
        
        $this->assertEquals(1, $this->getConnection()->getRowCount('winningtime_computed'), "winningtime_computed is not empty");
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @covers DistributionComputer::insertJackpotInstantWin
     */
    public function testInsertJackpotInstantWinThrowException(){
        $this->init();
        $this->oD->insertJackpotInstantWin( $this->oC->jackpotDesc ,array(), 1);
    }
    
    public function testComputeFixedPrizeReturnEmptyArrayIfFirstArgIsEmpty(){
        $this->init();
        $this->assertEmpty( $this->oD->computeFixedPrize( array(), array(), 0 ), "DistributionComputer::computeFixedPrize must return an empty array if the first argument is an empty array" );
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testComputeFixedPrizeThrowExceptionIfFirstArgChildIsNotPrizeHasDotationInstance(){
        $this->init();
        $this->oD->computeFixedPrize( array((object)array("winningtime"=>"nimp")), array(), 0 );
    }
    
    public function testComputeFixedPrize(){
        $this->init();
        
        $this->assertEquals( 0, $this->getConnection()->getRowCount('winningtime_computed'), "winningtime_computed is not empty");
        
        //On construit les fixtures
        
        $aFakePrizeHasDotation = array();
        $oFakePrizeHasDotation = new PrizeHasDotation();
        $oFakePrizeHasDotation->setFromDB((object)array(
            "id"=> "1",
            "id_dotation"=> "1",
            "id_prize" => "1331109979_cle-usb",
            "amount" => 1,
            "alreadydeal" => 0,
            "_date" => $this->sToday,
            "winningtime" => serialize(array("17:00:00"))
        ));
        $aFakePrizeHasDotation[]=$oFakePrizeHasDotation;
        
        $aExpected = array(
            "{$this->sToday} 17:00:00"
        );
        
        //On teste que computeFixedPrize renvoie un tableau avec un instant gagnant à 17:00:00 si randomRange vaut 0
        
        $aResult = $this->oD->computeFixedPrize( $aFakePrizeHasDotation, array(), 0 );
        
        $this->assertInternalType("array",$aResult, "computeFixedPrize must return an array");
        $this->assertTrue( count($aResult)===1, "computeFixedPrize must return exactly one instantwin if prizeHasDotation amount is one"  );
        $this->assertEquals( $aExpected, $aResult, "computeFixedPrize must return an instantWin at 17:00:00 if the random range is zero" );
        
        //On teste que computeFixedPrize renvoie bien un tableau vide si le prix n'est pas un prix fixe
        
        $oFakePrizeHasDotation->winningtime="";
        $oFakePrizeHasDotation->isFixed=FALSE;
        
        $aResult = $this->oD->computeFixedPrize( array($oFakePrizeHasDotation), array(), 0 );
        
        $this->assertInternalType("array",$aResult, "computeFixedPrize must return an array");
        $this->assertEmpty( $aResult, "computeFixedPrize must return an empty array if the prize is not fixed");
        
        //On remet à zero winningtime_computed
        
        $this->oPDO->start()
            ->exec("TRUNCATE `winningtime_computed`")
            ->commit();
        
        $this->assertEquals( 0, $this->getConnection()->getRowCount('winningtime_computed'), "winningtime_computed is not empty");
        
        //On teste que l'instant gagnant est ok, c'est à dire qu'avec 3 quantités, 2 déjà donnes, il ne renvoie qu'1 instant gagnant, + on teste l'horaire
        
        $aFakePrizeHasDotation = array();
        $oFakePrizeHasDotation = new PrizeHasDotation();
        $oFakePrizeHasDotation->setFromDB((object)array(
            "id"=> "1",
            "id_dotation"=> "1",
            "id_prize" => "1331109979_cle-usb",
            "amount" => 3,
            "alreadydeal" => 2,
            "_date" => $this->sToday,
            "winningtime" => serialize(array("17:00:00","17:30:00","18:00:00"))
        ));
        $aFakePrizeHasDotation[]=$oFakePrizeHasDotation;
        
        $aResult = $this->oD->computeFixedPrize( $aFakePrizeHasDotation, array(
            "1331109979_cle-usb" => 2), 10 );
        
        $this->assertInternalType("array",$aResult, "computeFixedPrize must return an array");
        $this->assertTrue( count($aResult)===1, "computeFixedPrize must return exactly one instantwin if prizeHasDotation amount is one"  );
        $this->assertEquals( 1, $this->getConnection()->getRowCount('winningtime_computed'), "winningtime_computed is not exactly one");
        $this->assertGreaterThan(strtotime( $this->sToday." "."17:50:00" ), strtotime( $aResult[0] ) );
        $this->assertLessThan(strtotime( $this->sToday." "."18:10:00" ), strtotime( $aResult[0] ) );
    }
    
    /**
     * @expectedException DomainException
     */
    public function testComputeFixedPrizeThrowExceptionIfWinningTimeMismatch(){
        $this->init();
        
        $this->assertEquals( 0, $this->getConnection()->getRowCount('winningtime_computed'), "winningtime_computed is not empty");
        
        //On construit les fixtures
        
        $aFakePrizeHasDotation = array();
        $oFakePrizeHasDotation = new PrizeHasDotation();
        $oFakePrizeHasDotation->setFromDB((object)array(
            "id"=> "1",
            "id_dotation"=> "1",
            "id_prize" => "1331109979_cle-usb",
            "amount" => 3,
            "alreadydeal" => 0,
            "_date" => $this->sToday,
            "winningtime" => serialize(array("17:00:00"))
        ));
        $aFakePrizeHasDotation[]=$oFakePrizeHasDotation;
        
        //doit lancer une exception, car amount: 3 et winningtime ne compte qu'une entrée
        
        $this->oD->computeFixedPrize( $aFakePrizeHasDotation, array(), 0 );
    }
    
    public function testGetExcessPrize() {
        
        $this->init();
        
        //2 lots non distribués
        $aFixtures = array( "1331109979_cle-usb","1331109979_cle-usb" );
        $sFixtures =serialize($aFixtures);
        
        //On construit un object Zend_Date à la veille du test
        $sYesterday = "2012-04-03";
        
        $sId = "";
        
        //On insère la fixture
        $this->oPDO->start()
            ->exec("INSERT INTO `dotation_computed` ( `id_campaign` , `day`, `serializedData` ) VALUES ( '1331109874_test', '$sYesterday', '$sFixtures' )", $sId )
            ->commit();
        
        $this->oLogger->log( "testGetExcessPrize, last id: " .dump_ex($sId));
        
        $aResult = $this->oD->getExcessPrize('1331109874_test');
        
        $this->oLogger->log( "testGetExcessPrize, returned: " .dump_ex($aResult));
        
        $this->assertInternalType("array",$aResult, "getExcessPrize must return an array");
        $this->assertEquals( $aFixtures,$aResult, "getExcessPrize must return an array with unserialised yesterday prize stack" );
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetExcessPrizeThrowExceptionIfFirstArgIsEmpty(){
        $this->init();
        $this->oD->getExcessPrize('');
    }
    
    public function testGetExcessPrizeReturnsEmptyArrayIfYesterdayPrizeStackIsEmpty(){
        $this->init();
        
        $aResult = $this->oD->getExcessPrize('1331109874_test');
        
        $this->assertInternalType( "array", $aResult, "getExcessPrize must return an array" );
        $this->assertEmpty($aResult);
    }
    
    public function testComputePrizeStackJustWorksForSimpleCase(){
        $this->init();

        $aPrize = $this->oC->prizes;
        $aPrizeHasDotation = $this->oC->curPrizeHasDotation;
        $aGavePrize = $this->oD->getGavePrizeFromLog($this->oC, $this->sTerminalNbr);

        //1 log pour ce prix, 2 quantités, on attend donc 1 entrée dans la pile de lots
        $aExpected = array(
            "1332320329_15-euros"
        );

        $this->oLogger->log("testComputePrizeStackJustWorksForSimpleCase");
        $aResult = $this->oD->computePrizeStack($aPrize, $aPrizeHasDotation, $aGavePrize, array(), FALSE );
        
        $this->assertInternalType( "array", $aResult, "computePrizeStack must return an array" );
        $this->assertEquals( $aExpected,$aResult, "computePrizeStack must return an array only normal prize id inside, with as many row that the amount field" );
    }
    
    /**
     * @expectedException DomainException
     */
    public function testComputePrizeStackThrowsExceptionIfSecondParamDoNotOnlyHoldsPrizeHasDotationInstance(){
        $this->init();
        $aPrize = $this->oC->prizes;
        $aPrizeHasDotation = array((object)array("id"=>1));
        $this->oD->computePrizeStack($aPrize, $aPrizeHasDotation, array(), array(), FALSE );
    }
    
    /**
     * @expectedException DomainException
     */
    public function testComputePrizeStackThrowsExceptionIfPrizeIdNotFound(){
        $this->init();
        $this->oD->computePrizeStack(array(),$this->oC->curPrizeHasDotation, array(), array(), FALSE );
    }
    
    /**
     * @expectedException DomainException
     */
    public function testComputePrizeStackThrowsExceptionIfAlreadydealAndLogsAreNotEquals(){
        $this->init();
        $aGavePrize = $this->oD->getGavePrizeFromLog($this->oC, $this->sTerminalNbr);
        $aPrizeHasDotation = $this->oC->curPrizeHasDotation;
        //On change la quantité pour une quantité fantaisiste
        $oPrizeHasDotation = $aPrizeHasDotation[1];
        $oPrizeHasDotation->alreadydeal=9999;
        $aPrizeHasDotation[1]=$oPrizeHasDotation;
        //Doit lancer une exception
        $this->oD->computePrizeStack($this->oC->prizes,$aPrizeHasDotation, $aGavePrize, array(), FALSE );
    }

    public function testInsertPrizeStackReallyInsertInDB(){
        $this->init();

        //precondition
        $this->assertEquals( 0, $this->getConnection()->getRowCount('dotation_computed'), "dotation_computed is not empty");
        
        $iResult = (int)$this->oD->insertPrizeStack($this->oC->id, array("1332320329_15-euros"));
        
        //L'id auto-increment doit etre > à 0 dans dotation_computed
        $this->assertGreaterThan( 0, $iResult );
        $this->assertEquals( 1, $this->getConnection()->getRowCount('dotation_computed'), "dotation_computed must have exactly one row after this test");
    }
    
    public function testShiftPrizeStack() {
        	
        $this->init();

        $iResult = (int)$this->oD->insertPrizeStack( $this->oC->id, array( "1332320329_15-euros" ));
        
        //Preconditions
        //L'id auto-increment doit etre > à 0 dans dotation_computed
        $this->assertGreaterThan( 0, $iResult );
        $this->assertEquals( 1, $this->getConnection()->getRowCount( 'dotation_computed' ), "dotation_computed must have exactly one row after this test");
        
        $iStackLength = 0;
        $sResult = $this->oD->shiftPrizeStack( $this->oC->id, $iStackLength );
        
        $this->assertEquals( "1332320329_15-euros", $sResult, "Prize id must be here 1332320329_15-euros" );
        $this->assertEquals( 0, $iStackLength, "The prize stack must be empty after this test" );
        $this->assertEquals( 1, $this->getConnection()->getRowCount( 'dotation_computed' ), "dotation_computed must have exactly one row after this test");
        
        $iStackLength = 0;
        $sResult = $this->oD->shiftPrizeStack( $this->oC->id, $iStackLength );
        
        $this->assertFalse( $sResult, "shiftPrizeStack must return FALSE if the prize stack is empty" );
    }
    
    public function testComputeNextInstantWinTimeReturnsNullIfPrizeStackIsEmpty() {
        $this->init();
        $this->assertNull( $this->oD->computeNextInstantWinTime( new Schedule(),new Zend_Date(),0, 0 ) );
    }
    
    public function testComputeNextInstantWinTimeForBreakedScheduleWorksForSimpleCase() {
    	
        $this->init();
        
        $oMorning = (object)array(
            "id" => "1",
            "id_dotation" => "1329132446_dotation_1",
            "day" => $this->sToday,
            "begin" => "10:00:00",
            "end" => "12:00:00"
        );
        
        $oAfternoon = (object)array(
            "id" => "2",
            "id_dotation" => "1329132446_dotation_1",
            "day" => $this->sToday,
            "begin" => "14:00:00",
            "end" => "16:00:00"
        );
        
        $oSchedule = new Schedule();
        $oSchedule->setFromDb( (object)array(
			"morning" => $oMorning,
			"afternoon" => $oAfternoon
		));
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday." 09:00:00" ), Zend_Date::TIMESTAMP );
        
        $oResult = $this->oD->computeNextInstantWinTime( $oSchedule, $oNow, 0, 1 );
        $this->assertTrue( get_class($oResult)=== 'Zend_Date', "computeNextInstantWinTime must return Zend_Date instance");
        $this->assertEquals( strtotime($this->sToday." 12:00:00") ,$oResult->get( Zend_Date::TIMESTAMP ), "computeNextInstantWinTime must here return an InstantWin at 12:00:00" );
    }

    public function testComputeNextInstantWinTimeForBreakedScheduleWhenNowIsBeforeSchedule() {
    	
        $this->init();
        
        $oMorning = (object)array(
            "id" => "1",
            "id_dotation" => "1329132446_dotation_1",
            "day" => $this->sToday,
            "begin" => "11:00:00",
            "end" => "12:00:00"
        );
        
        $oAfternoon = (object)array(
            "id" => "2",
            "id_dotation" => "1329132446_dotation_1",
            "day" => $this->sToday,
            "begin" => "14:00:00",
            "end" => "17:00:00"
        );
        
        $oSchedule = new Schedule();
        $oSchedule->setFromDb( (object)array(
			"morning" => $oMorning,
			"afternoon" => $oAfternoon
		));
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday." 09:00:00" ), Zend_Date::TIMESTAMP );
        
        $oResult = $this->oD->computeNextInstantWinTime( $oSchedule, $oNow, 0, 1 );
        $this->assertTrue( get_class($oResult)=== 'Zend_Date', "computeNextInstantWinTime must return Zend_Date instance");
        $this->assertEquals( strtotime($this->sToday." 15:00:00") ,$oResult->get( Zend_Date::TIMESTAMP ), "computeNextInstantWinTime must here return an InstantWin at 15:00:00" );
    }


    public function testComputeNextInstantWinTimeForBreakedScheduleWhenNowIsDuringMorningTimespan(){
        $this->init();
        
        $oMorning = (object)array(
             "id" => "1",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "10:00:00",
                "end" => "12:00:00"
        );
        
        $oAfternoon = (object)array(
             "id" => "2",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "14:00:00",
                "end" => "16:00:00"
        );
        
        $oSchedule = new Schedule();
        $oSchedule->setFromDb( (object)array(
			"morning" => $oMorning,
			"afternoon" => $oAfternoon
		));
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday." 11:00:00" ), Zend_Date::TIMESTAMP );
        
        $oResult = $this->oD->computeNextInstantWinTime( $oSchedule, $oNow, 0, 1 );
        
        $this->assertTrue( get_class($oResult)=== 'Zend_Date', "computeNextInstantWinTime must return Zend_Date instance");
        $this->assertEquals( strtotime($this->sToday." 14:30:00") ,$oResult->get( Zend_Date::TIMESTAMP ), "computeNextInstantWinTime must here return an InstantWin at 14:30:00" );
    }
    
    public function testComputeNextInstantWinTimeForBreakedScheduleWhenNowIsDuringLunch(){
        $this->init();
        
        $oMorning = (object)array(
            "id" => "1",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "10:00:00",
                "end" => "12:00:00"
        );
        
        $oAfternoon = (object)array(
            "id" => "2",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "14:00:00",
                "end" => "16:00:00"
        );
        
        $oSchedule = new Schedule();
        $oSchedule->setFromDb( (object)array(
			"morning" => $oMorning,
			"afternoon" => $oAfternoon
		));
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday . " 13:00:00" ), Zend_Date::TIMESTAMP );
        
        $oResult = $this->oD->computeNextInstantWinTime( $oSchedule, $oNow, 0, 1 );
        
        $this->assertTrue( get_class($oResult)=== 'Zend_Date', "computeNextInstantWinTime must return Zend_Date instance");
        $this->assertEquals( strtotime($this->sToday." 15:00:00") ,$oResult->get( Zend_Date::TIMESTAMP ), "computeNextInstantWinTime must here return an InstantWin at 15:00:00" );
    }
    
    public function testComputeNextInstantWinTimeForBreakedScheduleWhenNowIsDuringAfternoon(){
        $this->init();
        
        $oMorning = (object)array(
            "id" => "1",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "10:00:00",
                "end" => "12:00:00"
        );
        
        $oAfternoon = (object)array(
            "id" => "2",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "14:00:00",
                "end" => "16:00:00"
        );
        
        $oSchedule = new Schedule();
        $oSchedule->setFromDb( (object)array(
			"morning" => $oMorning,
			"afternoon" => $oAfternoon
		));
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday . " 15:00:00" ), Zend_Date::TIMESTAMP );
        
        $oResult = $this->oD->computeNextInstantWinTime( $oSchedule, $oNow, 0, 1 );
        
        $this->assertTrue( get_class($oResult)=== 'Zend_Date', "computeNextInstantWinTime must return Zend_Date instance");
        $this->assertEquals( strtotime($this->sToday." 15:30:00") ,$oResult->get( Zend_Date::TIMESTAMP ), "computeNextInstantWinTime must here return an InstantWin at 15:00:00" );
    }
    
    public function testComputeNextInstantWinForBreakedScheduleReturnsNullIfNotInSchedule(){
        $this->init();
        
        $oMorning = (object)array(
            "id" => "1",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "10:00:00",
                "end" => "12:00:00"
        );
        
        $oAfternoon = (object)array(
            "id" => "2",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "14:00:00",
                "end" => "16:00:00"
        );
        
        $oSchedule = new Schedule();
        $oSchedule->setFromDb( (object)array(
			"morning" => $oMorning,
			"afternoon" => $oAfternoon
		));
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday . " 17:00:00" ), Zend_Date::TIMESTAMP );
        
        $this->assertNull( $this->oD->computeNextInstantWinTime( $oSchedule, $oNow, 0, 1 ), "computeNextInstantWinTime must return NULL if called after day shedule" );
    }

    public function testComputeNextInstantWinTimeForContinuScheduleWhenNowIsBeforeShedule(){
        
        $this->init();
        
        $oMorning = (object)array(
            "id" => "1",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "10:00:00",
                "end" => "12:00:00"
        );
        
        $oSchedule = new Schedule();
        $oSchedule->setFromDb( (object)array("morning"=>$oMorning ));
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday . " 09:00:00" ), Zend_Date::TIMESTAMP );
        
        $oResult = $this->oD->computeNextInstantWinTime( $oSchedule, $oNow, 0, 1 );
        
        $this->assertTrue( get_class($oResult)=== 'Zend_Date', "computeNextInstantWinTime must return Zend_Date instance");
        $this->assertEquals( strtotime($this->sToday." 11:00:00") ,$oResult->get( Zend_Date::TIMESTAMP ), "computeNextInstantWinTime must here return an InstantWin at 11:00:00" );
    }
    
     public function testComputeNextInstantWinTimeForContinuScheduleWhenNowDuringShedule(){
        
        $this->init();
        
        $oMorning = (object)array(
            "id" => "1",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "10:00:00",
                "end" => "12:00:00"
        );
        
        $oSchedule = new Schedule();
        $oSchedule->setFromDb( (object)array("morning"=>$oMorning ));
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday . " 11:00:00" ), Zend_Date::TIMESTAMP );
        
        $oResult = $this->oD->computeNextInstantWinTime( $oSchedule, $oNow, 0, 1 );
        
        $this->assertTrue( get_class($oResult)=== 'Zend_Date', "computeNextInstantWinTime must return Zend_Date instance");
        $this->assertEquals( strtotime($this->sToday." 11:30:00") ,$oResult->get( Zend_Date::TIMESTAMP ), "computeNextInstantWinTime must here return an InstantWin at 11:00:00" );
    }
    
    public function testComputeNextInstantWinForContinuScheduleReturnsNullIfNotInSchedule() {
        
        $this->init();
        
        $oMorning = (object)array(
            "id" => "1",
                "id_dotation" => "1329132446_dotation_1",
                "day" => $this->sToday,
                "begin" => "10:00:00",
                "end" => "12:00:00"
        );
        
        $oSchedule = new Schedule();
        $oSchedule->setFromDb( (object)array("morning"=>$oMorning ));
        
        $oNow = new Zend_Date();
        $oNow->set( strtotime( $this->sToday . " 13:00:00" ), Zend_Date::TIMESTAMP );
        
        $this->assertNull( $this->oD->computeNextInstantWinTime( $oSchedule, $oNow, 0, 1 ), "computeNextInstantWinTime must return NULL if called after day shedule" );
    }

    public function testRegisterNextInstantWin() {
        $this->init();

        $oIWinDate = new Zend_Date();
        $oIWinDate->set( strtotime( $this->sToday . " 17:00:00" ), Zend_Date::TIMESTAMP );

        $initialRowCount = $this->getConnection()->getRowCount('winningtime_computed');
        $sId = $this->oD->registerNextInstantWin($oIWinDate, "1332320329_50-euros");
        $this->assertEquals($initialRowCount + 1,
                            $this->getConnection()->getRowCount('winningtime_computed'));

        $this->assertGreaterThan( 0, $sId );
        $row = false;
        $this->oPDO->getRow("winningtime_computed", sprintf("id = '%s'", $sId), $row);
        $this->assertEquals("1332320329_50-euros", $row->id_prize);
        $this->assertEquals("2012-04-04 17:00:00", $row->_datetime);
        $this->assertEquals("1", $row->isValid);
        $this->assertEquals("0", $row->isJackpot);
        $this->assertEquals("0", $row->isFixedPrize);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterNextInstantWinWithInvalidArgument() {
        $this->init();

        $oIWinDate = new Zend_Date();
        $oIWinDate->set( strtotime( $this->sToday . " 17:00:00" ), Zend_Date::TIMESTAMP );
        $this->oD->registerNextInstantWin($oIWinDate, null);
    }
    
    public function testGetRegisteredInstantWin(){

        $this->init();

        // On insert un instant gagnant hier
        $oIWinDate = new Zend_Date();
        $oIWinDate->set( strtotime( "2012-04-03 17:00:00" ), Zend_Date::TIMESTAMP );
        $this->oD->registerNextInstantWin($oIWinDate, "1332320329_50-euros");
        // ...puis un autre demain
        $oIWinDate->set( strtotime( "2012-04-05 00:00:00" ), Zend_Date::TIMESTAMP );
        $this->oD->registerNextInstantWin($oIWinDate, "1332320329_50-euros");

        // On teste que getRegisteredInstantWin renvoit bien un tableau vide
        //  s'il n'y a pas d'instant gagnant aujourd'hui
        $aResult = $this->oD->getRegisteredInstantWin($this->sToday, $this->oC->prizes);
        $this->assertInternalType("array", $aResult);
        $this->assertEquals(array(), $aResult);

        // On insert un instant gagnant aujourd'hui
        $oIWinDate->set( strtotime( $this->sToday . " 17:00:00" ), Zend_Date::TIMESTAMP );
        $this->oD->registerNextInstantWin($oIWinDate, "1332320329_50-euros");

        // On teste qu'il y a bien un instant gagnant aujourd'hui
        $aResult = $this->oD->getRegisteredInstantWin($this->sToday, $this->oC->prizes);
        $this->assertInternalType("array", $aResult);
        $this->assertEquals(1, count($aResult));
        $oInstantWin = $aResult[0];
        $this->assertInstanceOf("InstantWin", $oInstantWin);
        $this->assertEquals("1332320329_50-euros", $oInstantWin->id_prize);
    }

    public function testInitTodayPrizeListWithDemoAlgo() {
        $sCampaignId = "1340805973_demo";
        $sTerminalId = "1";
        $sToday = "2012-06-21";

        $this->init($sCampaignId, $sTerminalId, $sToday);

        // precondition
        $this->assertEquals(0, $this->getConnection()->getRowCount('winningtime_computed'));
        $this->assertEquals(0, $this->getConnection()->getRowCount('dotation_computed'));

        // Lors de l'appel a initTodayPrizeList avec une campagne utilisant l'algo demo,
        // la fonction doit simplement renvoyer un LotteryResult vide sans inserer d'instant
        // gagnant ni de dotation
        $res = $this->oD->initTodayPrizeList($sCampaignId, $sToday);
        $this->assertInstanceOf("LotteryResult", $res);
        $this->assertEquals(new LotteryResult(), $res);

        $this->assertEquals(0, $this->getConnection()->getRowCount('winningtime_computed'));
        $this->assertEquals(0, $this->getConnection()->getRowCount('dotation_computed'));
    }

    public function testInitTodayPrizeListWithRegularAlgoAndPrizeListReport() {
        // (Attention, on va utiliser la dotation
        // 1329132446_dotation_2 dans les fixtures)
        $sToday = "2012-04-05";
        $this->init($this->sCampaignId, "2", $sToday);

        // On insere une pile de 4 lots non distribués la veille
        $aExcessPrize = array("1332320329_15-euros", "1332320329_15-euros",
                              "1332320329_15-euros", "1332320329_15-euros");
        $sExcessPrize = serialize($aExcessPrize);
        $sYesterday = "2012-04-04";
        $this->oPDO->start()
            ->exec("INSERT INTO `dotation_computed` ( `id_campaign` , `day`, `serializedData` ) VALUES ( '1331109874_test', '$sYesterday', '$sExcessPrize' )")
            ->commit();


        $oDateTimeInjector = new DateTimeInjector("2012-04-05 15:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);

        // On insere manuellement un instant gagnant pour aujourd'hui,
        // pour s'assurer qu'il est bien supprimé par la fonction initTodayPrizeList
        $sTable = 'winningtime_computed';
        $columns = array('id_prize', '_datetime', 'isValid');
        $initialRowCount = $this->getConnection()->getRowCount($sTable);
        $this->oPDO->start()
            ->insert($sTable,
                     $columns,
                     array("'1332320329_15-euros'", "'2012-04-05 09:42:00'", 1))
            ->commit();
        $this->assertEquals($initialRowCount + 1,
                            $this->getConnection()->getRowCount($sTable));

        // On annule l'ecart variable pour les instants gagnants
        // (pour pouvoir faire des assertions precises)
        $this->oC->randomRangePercent = 0;
        $this->oC->randomRangeMin = 0;

        $res = $this->oD->initTodayPrizeList($this->sCampaignId, $this->sTerminalNbr);

        // On teste que l'on recoit bien un LotteryResult de type "init"
        $this->assertInstanceOf("LotteryResult", $res);
        $this->assertFalse(empty($res->todayPrizeHasDotation));
        $this->assertFalse(empty($res->registeredInstantWin));


        // On va ensuite verifier que la pile de lots contient 8 entrees :
        // => 5 de la dotation d'aujourdhui MOINS 1 utilisé pour insérer un
        //    instant gagnant
        // => 4 de la pile de la veille qui ont été reportés
        $sSerializedPrizeStack = "";
        $this->oPDO->start()
            ->getField( "serializedData", "dotation_computed",
                        " id_campaign='{$this->sCampaignId}' AND day='$sToday' ",
                        $sSerializedPrizeStack )
            ->commit();
        $aPrizeStack = unserialize($sSerializedPrizeStack);
        $this->assertInternalType("array", $aPrizeStack);
        $this->assertEquals(8, count($aPrizeStack));
        foreach ($aPrizeStack as $sPrizeId) {
            $this->assertEquals("1332320329_15-euros", $sPrizeId);
        }


        // Les instants gagnants ont-ils bien été insérés ?
        // (sur cette campagne on doit avoir 1 jackpot, 1 lot fixe et 1 lot normal)
        $sQuery = <<<SQL
            SELECT `id_prize`,`_datetime` FROM `winningtime_computed`
            WHERE
            DATE_FORMAT( `_datetime`,'%Y-%m-%d' )='2012-04-05'
            AND `isValid`=1 
SQL;

        $aJackpotIW = array();
        $aNormalIW = array();
        $aFixedIW = array();

        $this->oPDO->start()
            ->query($sQuery . 'AND `isJackpot`=1', $aJackpotIW )
            ->query($sQuery . 'AND `isFixedPrize`=1', $aFixedIW )
            ->query($sQuery . 'AND `isJackpot`=0 AND `isFixedPrize`=0', $aNormalIW )
            ->commit();

        // On doit avoir un seul instant gagnant de jackpot, qui tombe entre 16h et 17h
        $this->assertEquals(1, count($aJackpotIW));
        $this->assertEquals('1331109979_voyage-aux-seychelles', $aJackpotIW[0]['id_prize']);
        $iIWTime = strtotime($aJackpotIW[0]['_datetime']);
        $this->assertTrue($iIWTime >= strtotime('2012-04-05 16:00:00') &&
                          $iIWTime <= strtotime('2012-04-05 17:00:00'));

        // On doit avoir un seul instant gagnant de lot fixe, qui tombe a 15h38
        $this->assertEquals(1, count($aFixedIW));
        $this->assertEquals('1332320329_50-euros', $aFixedIW[0]['id_prize']);
        $this->assertEquals('2012-04-05 15:38:00', $aFixedIW[0]['_datetime']);

        // On doit avoir un seul instant gagnant de lot normal, qui tombe a 15h18
        // (car 9 lots normaux a gagner aujourd'hui, 3h restantes)
        $this->assertEquals(1, count($aNormalIW));
        $this->assertEquals('1332320329_15-euros', $aNormalIW[0]['id_prize']);
        $this->assertEquals('2012-04-05 15:18:00', $aNormalIW[0]['_datetime']);
    }

    public function testInitTodayPrizeListWithRegularAlgoAndWithoutPrizeListReport() {
        // (Attention, on va utiliser la dotation
        // 1329132446_dotation_2 dans les fixtures)
        $sToday = "2012-04-05";
        $this->init($this->sCampaignId, "2", $sToday);

        // On configure la campagne pour qu'elle ne reporte pas les lots de la veille
        $this->oC->allow_prizeListReport = FALSE;

        // On insere une pile de 4 lots non distribués la veille
        $aExcessPrize = array("1332320329_15-euros", "1332320329_15-euros",
                              "1332320329_15-euros", "1332320329_15-euros");
        $sExcessPrize = serialize($aExcessPrize);
        $sYesterday = "2012-04-04";
        $this->oPDO->start()
            ->exec("INSERT INTO `dotation_computed` ( `id_campaign` , `day`, `serializedData` ) VALUES ( '1331109874_test', '$sYesterday', '$sExcessPrize' )")
            ->commit();


        $oDateTimeInjector = new DateTimeInjector("2012-04-05 15:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);

        $res = $this->oD->initTodayPrizeList($this->sCampaignId, $this->sTerminalNbr);

        // On va juste verifier que la pile de lots contient bien 4 elements,
        // correspondant a la dotation d'aujourd'hui uniquement (5 - 1 qui
        // a été utilisé pour insérer un instant gagnant) pour valider que
        // les lots de la veille n'ont pas été reportés.
        $sSerializedPrizeStack = "";
        $this->oPDO->start()
            ->getField( "serializedData", "dotation_computed",
                        " id_campaign='{$this->sCampaignId}' AND day='$sToday' ",
                        $sSerializedPrizeStack )
            ->commit();
        $aPrizeStack = unserialize($sSerializedPrizeStack);
        $this->assertInternalType("array", $aPrizeStack);
        $this->assertEquals(4, count($aPrizeStack));
        foreach ($aPrizeStack as $sPrizeId) {
            $this->assertEquals("1332320329_15-euros", $sPrizeId);
        }
    }


    public function testInitTodayPrizeListWithCyclicAlgo() {
        $sCampaignId = "1341048521_cyclic";
        $sToday = "2012-07-03";
        $this->init($sCampaignId, "1", $sToday);

        // On insere une pile de 2 lots non distribués la veille
        $aExcessPrize = array("1341048521_mug", "1341048521_mug");
        $sExcessPrize = serialize($aExcessPrize);
        $sYesterday = "2012-07-02";
        $this->oPDO->start()
            ->exec("INSERT INTO `dotation_computed` ( `id_campaign` , `day`, `serializedData` ) VALUES ( '$sCampaignId', '$sYesterday', '$sExcessPrize' )")
            ->commit();

        $oDateTimeInjector = new DateTimeInjector("$sToday 11:00:00");
        $this->oD->setDateTimeInjector($oDateTimeInjector);

        // On annule l'ecart variable pour les instants gagnants
        // (pour pouvoir faire des assertions precises)
        $this->oC->randomRangeMin = 0;


        $res = $this->oD->initTodayPrizeList($this->sCampaignId, $this->sTerminalNbr);

        // On teste que l'on recoit bien un LotteryResult de type "init"
        $this->assertInstanceOf("LotteryResult", $res);

        // On verifie que la pile de lots contient 10 entrees seulement,
        // (5 perdu et 5 mug). Les lots de la veille ne doivent pas etre
        // reportés avec l'algo cyclic
        $sSerializedPrizeStack = "";
        $this->oPDO->start()
            ->getField( "serializedData", "dotation_computed",
                        " id_campaign='$sCampaignId' AND day='$sToday' ",
                        $sSerializedPrizeStack )
            ->commit();
        $aPrizeStack = unserialize($sSerializedPrizeStack);
        $this->assertInternalType("array", $aPrizeStack);
        $this->assertEquals(10, count($aPrizeStack));
        $aPrizes = array(
            "1341048521_mug" => 0,
            "1341048521_perdu" => 0
        );
        foreach ($aPrizeStack as $sPrizeId) {
            $this->assertTrue(array_key_exists($sPrizeId, $aPrizes));
            $aPrizes[$sPrizeId] += 1;
        }

        $aExpectedPrizes = array(
            "1341048521_mug" => 5,
            "1341048521_perdu" => 5
        );
        $this->assertEquals($aExpectedPrizes, $aPrizes);

        // Il ne doit pas y avoir d'instant gagnant pour lot normal
        $sQuery = <<<SQL
            SELECT `id_prize`,`_datetime` FROM `winningtime_computed`
            WHERE
            DATE_FORMAT( `_datetime`,'%Y-%m-%d' )='2012-04-05'
            AND `isValid`=1 
SQL;

        $aNormalIW = array();
        $this->oPDO->start()
            ->query($sQuery . 'AND `isJackpot`=0 AND `isFixedPrize`=0', $aNormalIW )
            ->commit();
        $this->assertEquals(0, count($aNormalIW));
    }

    public function testInitTodayPrizeListWithError() {
        $this->init();

        $oPAD = $this->oD->getPrizeHasDotationForThatPrize("1332320329_15-euros",
                                                           $this->oC->curPrizeHasDotation);

        // Precondition : on verifie qu'on a déjà distribué 1 un lot de ce type
        $this->assertEquals(1, $oPAD->alreadydeal);

        // On va modifier le nombre de lots deja distribués aujourd'hui pour que
        // cela ne corresponde plus aux logs pour generer une erreur (en augmentant
        // le nombre).
        $oPAD->alreadydeal = 36;

        $res = $this->oD->initTodayPrizeList($this->sCampaignId, $this->sTerminalNbr);
        $this->assertInstanceOf("LotteryResult", $res);
        $this->assertTrue($res->isInError);

        // On va modifier le nombre de lots deja distribués aujourd'hui pour que
        // cela ne corresponde plus aux logs pour generer une erreur (en diminuant
        // le nombre, de facon a tenter de distribuer plus de cadeaux)
        $oPAD->alreadydeal = 0;

        $res = $this->oD->initTodayPrizeList($this->sCampaignId, $this->sTerminalNbr);
        $this->assertInstanceOf("LotteryResult", $res);
        $this->assertTrue($res->isInError);


    }
}

