<?php

require_once('simpletest_1.1.0/simpletest/autorun.php');
require_once dirname(__FILE__). "/../../Config/init.php";

class TestVarious extends UnitTestCase {

    function testCountPhoneNumberByDay(){
        
        $oPA = new PlayerApi();
        //$aResult = $oPA->countPhoneNumberByDay( "C:/wamp/www/LetHPHPServices/Quality/SimpleTest/fixtures/userdata_player_8700.csv" );
        $aResult = $oPA->countPhoneNumberByDay( "C:/Users/plh/Adobe Flash Builder 4.6/LHPlayerNewRepo/bin-debug/configs/config_1368777912_carrefour-roumanie-juin-2013/data/userdata_player_8707.csv" );
        
        var_dump($aResult);
        
        /*$aWantedResult = array(
            "2013-05-19" => 2,
            "2013-05-20" => 2,
            "2013-05-21" => 1,
            "TOTAL" => 5
        );*/
        
        $this->assertIsA( $aResult, "array");
        
        $aResult1 = $oPA->countPhoneNumberByDay( "C:/Users/plh/Adobe Flash Builder 4.6/LHPlayerNewRepo/bin-debug/configs/config_1368777912_carrefour-roumanie-juin-2013/data/userdata_player_8700.csv" );
        
        var_dump($aResult1);
        
        /*$aWantedResult = array(
            "2013-05-19" => 2,
            "2013-05-20" => 2,
            "2013-05-21" => 1,
            "TOTAL" => 5
        );*/
        
        $this->assertIsA( $aResult1, "array");
    }
    
    function testGetLogsByDay() {
        
        /*$oC = new ConfigLoader();
        $oCC = $oC->getCampaignData( "1368777912_carrefour-roumanie-juin-2013", "8707" );
        
        $oS = new Statistic();
        $aR = $oS->getLogsByDay( $oCC, 9, new Zend_Date() );
        
        var_dump($aR);
        
        $this->assertIsA( $aR, "array");*/
    }
    
    function testGetPlayerAmount() {
        
        /* $oC = new ConfigLoader();
        $oCC = $oC->getCampaignData( "1368777912_carrefour-roumanie-juin-2013", "8707" );
        
        $oS = new Statistic();
        $aR = $oS->getPlayerAmount( $oCC, $oCC->begin, $oCC->end );
        
        var_dump($aR);
        
        $this->assertIsA( $aR, "array");*/
    }
    
    function testFixedPrize(){
        
        /* $oDC = new DistributionComputer();
        $result=$oDC->initTodayPrizeList("1365074746_brico-leclerc-mai-2013", "8500");
        $this->assertIsA($result, "LotteryResult");
        
        var_dump($result);*/
    }
    
    function testGetPlayersFromLogs(){
        
        /*$sCampaignId = "1353931533_carrefour-market-12-2012";
        $sTerminalId="1000";
        
        $oPA = new PlayerApi();
        $aResult = $oPA->_getPlayersFromLogs($sCampaignId, $sTerminalId);
        
        var_dump($aResult);*/
    }
    
    function testConfigLoader() {
        
       /* $oC = new ConfigLoader();
        $oCC = $oC->getCampaignData("1361282937_cc-grand-maine", "8001");
        $oP = new PlayerApi();
        //var_dump( $oP->isBarcodValid( $oCC, "14000010" ) );
       $aS=$oP->getCampaignSeries("1361282937_cc-grand-maine");
       var_dump($aS);
        var_dump($oP->isBarcodValidInSeries($aS,"14000010",TRUE));*/
		
        
        //$array = $oC->fetchCampaignPrizeHasDotationFromId($oCC->id, $oCC->curTermHasDotation->terminal_number);
        
         //var_dump($array);
    }
    
    function testRegisteringPrizeList(){
        
        //$sFake =  'a:10:{i:0;s:18:"1358859063_lot-n-5";i:1;s:18:"1358859063_lot-n-5";i:2;s:18:"1358859063_lot-n-4";i:3;s:18:"1358859063_lot-n-1";i:4;s:18:"1358859063_lot-n-5";i:5;s:18:"1358859063_lot-n-5";i:6;s:18:"1358859063_lot-n-1";i:7;s:18:"1358859063_lot-n-3";i:8;s:18:"1358859063_lot-n-4";i:9;s:18:"1358859063_lot-n-1";}';
        
        //$oC = new ConfigLoader();
        //$oCC = $oC->getCampaignData( "1358853641_m.-bricolage-mars-2013", "1215");
        
        //$oDC = new DistributionComputer();
        // $oDC->setToday("2013-03-28");
        
        //$aResult = $oDC->getExcessPrize("1358853641_m.-bricolage-mars-2013");

        //$result = $oDC->initTodayPrizeList("1358853641_m.-bricolage-mars-2013", "8001");
        
        //var_dump($result);
        
        //echo "<br/>Hello: ".$aDump = serialize(array("1358859063_lot-n-1"));
        
/*        $oIW = new InstantWinDistribution();
        $result = $oIW->instantWin( "1358853641_m.-bricolage-mars-2013", "1215", "GOULOTTE1" );
        
        var_dump($result);*/
        
      // echo $sResult =  serialize( array( "1358859063_lot-n-2" ) );
    }
    
    function testNimp(){
        
    }
    
    
    
    function testNewIWFunction() {
        
        /* $oC = new ConfigLoader();
        $oCC = $oC->getCampaignData( "1368777912_carrefour-roumanie-juin-2013", "8708" );
        
        $whatToDo = new WhatToDo();
        $whatToDo->checkBarcodIsInSeries = true;
        $whatToDo->checkUserIsRegistered = false;
        $whatToDo->checkUserAlreadyPlay = false;
        $whatToDo->launchInstantWin = false;
        
        $whatToDo->currentCampaign = $oCC;
        $whatToDo->serialMessage = "0023594213496";
        //$whatToDo->dataFilePath = "C:/Users/plh/Adobe Flash Builder 4.6/LHPlayerNewRepo/bin-debug/configs/config_1358853641_m.-bricolage-mars-2013/data/userdata_player_1215.csv";
        
        $oP = new PlayerApi();
        $lotteryResult = $oP->afterScanServerAction($whatToDo);
        
        $this->assertIsA($lotteryResult, "LotteryResult");
        
        var_dump($lotteryResult);*/
    }
    
    function testPlayerAmount(){
        /*$oC = new ConfigLoader();
        $oCC = $oC->getCampaignData( "1358853641_m.-bricolage-mars-2013", "1215" );
        $oS=new Statistic();
        var_dump($oS->getPlayerAmount($oCC, $oCC->begin, $oCC->end));*/
    }
    
    function testDistriComputerInitTodayPrizeList() {
        
       /*$oD = new DistributionComputer();
       $result =  $oD->initTodayPrizeList("1358853641_m.-bricolage-mars-2013", "1215");
       
       var_dump($result);*/
    }
    
    function testFetchCampaignPrizeHasDotation() {
        //$oC = new ConfigLoader();
        //$oCC = $oC->getCampaignData("1349076528_leclerc-ifs-2012-11", "1000");
        
        //var_dump($oC->fetchCampaignPrizeHasDotation($oCC));
    }
    
    function testInstantWin() {
        
        /* $sCampaignId = "1344419798_leclerc-automne-2012";
        $sTerminalNbr = "1000";
        $sSerialInput = "2950000000001";
                
        $oIWD = new InstantWinDistribution();
        $result =  $oIWD->instantWin($sCampaignId, $sTerminalNbr, $sSerialInput);
        
        var_dump($result);*/
    }
    
    function testAuthenticate() {
        
        /*$oUser = new User();
        $oUser->u_password = "gallec";
        $oUser->id_campaign = "1344419798_leclerc-automne-2012";
        
        $oC = new ConfigLoader();
        $result = $oC->authenticate($oUser);
        
        d($result);*/
    }
    /**
     * Warning, take care, erase data within DB
     */
    /* function testReset() {
        $oP = new PlayerApi();
        $oP->resetCampaignData( "1344419798_leclerc-automne-2012" );
    }*/
    
    function testBarcodAllowedToPlayAndValid() {
        
        /* $oC = new ConfigLoader();
        $oCC = $oC->getCampaignData("1344419798_leclerc-automne-2012", "1215");
        
        $oPA = new PlayerApi();
        $sResult = $oPA->isBarcodAllowedToPlayAndValidAndRegistered($oCC, "295000000005", "C:\Users\plh\Adobe Flash Builder 4.6\LHPlayer\bin-debug\configs\config_1344419798_leclerc-automne-2012\data\\userdata_player_1000.csv");
        
        $this->assertEqual($sResult, PlayerApi::VALID_BARCOD); */
    }
    

    function testPlayerApi() {

   /*     $oC = new Campaign();
        $oC->id="default";
        $oC->curTermHasDotation = new TerminalHasDotation();
        $oC->curTermHasDotation->terminal_number = "1";
        $oC->fileSystemPath = "C:\\Users\\plh\\Adobe Flash Builder 4.6\\LHPlayer\\bin-debug";
        $sSerialInput = "3258170321853";

        $oPA = new PlayerApi();
        $sResult = $oPA->isBarcodAllowedToPlayAndValid($oC, $sSerialInput);

        $this->assertEqual( $sResult, PlayerApi::VALID_BARCOD );*/
    }

/*     function testGetPlayerAmount(){

        $oC = new Campaign();
        $oC->id="default";

        $oZ1 = new Zend_Date();
        $oZ1->set( strtotime( "2012-08-01" ), Zend_Date::TIMESTAMP );
        
        $oZ2 = new Zend_Date();
        $oZ2->set( strtotime( "2012-08-10" ), Zend_Date::TIMESTAMP );

        $oS = new Statistic();
        $result=  $oS->getPlayerAmount($oC, $oZ1,$oZ2);

        d($result);

        $this->assertTrue(is_array($result));
    } */
    
    function testInsertCampaign() {

        /* $oCW = new ConfigWriter();

        $name = "Leclerc Automne 2012";
        $path = "C:\\Users\\plh\\Adobe Flash Builder 4.6\\LHPlayer\\bin-debug\\configs\\config_leclerc-autumn-2012";
        $algo = 'Regular';
        $start = "2012-10-10";
        $end = "2012-11-03";

        $result = $oCW->writeEmptyCampaign($name, $path, $algo, $start, $end);
        $this->assertIsA($result, "Campaign");*/
    }

    /* function testInitTodayPrizeList(){
        $oDC = new DistributionComputer();
       $result =  $oDC->initTodayPrizeList("default", 1);
        d($result);
        $this->assertIsA($result, "LotteryResult");
    } */
}