<?php
require_once('simpletest_1.1.0/simpletest/autorun.php');
require_once dirname(__FILE__). "/../../Config/init.php";

/**
 * A testcase only for working on M. Bricolage prize report bug
 */
class TestPrizeReport extends UnitTestCase {
    
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
    private $sTerminalNbr = "8001";
    
    /**
     * @var string
     */
    private $sCampaignId = "1358853641_m.-bricolage-mars-2013";
    
    /**
     * @var string
     */
    private $sToday = "2013-03-25";
    
    /**
     * @var Log
     */
    private $oLogger;
    
    /**
     * @var PDOUtils
     */
    private $oPDO;
    
    /**
     *
     * @var InstantWinDistribution 
     */
    private $oIW;
    
    /**
     * 
     */
    private function _init() {
        
        $this->oLogger = Log::factory( 'file', 'prize_report.log', 'REPORT' );
        $this->oPDO = new PDOUtils( "leth_player_bdd", "localhost", "root", "" );
        
        $this->oL = new ConfigLoader();
        $this->oL->setToday( $this->sToday );
        $this->oL->setPDO( $this->oPDO );
        $this->oC = $this->oL->getCampaignData( $this->sCampaignId, $this->sTerminalNbr );
        
        $this->oD = new DistributionComputer();
        $this->oD->setToday( $this->sToday );
        $this->oD->setLoader( $this->oL );
        $this->oD->setCampaign( $this->oC );
        $this->oD->setPDO( $this->oPDO );
        $this->oD->setLogger( $this->oLogger );
        
        $this->oIW = new InstantWinDistribution();
        $this->oIW->setToday( $this->sToday );
        $this->oIW->setLoader( $this->oL );
        $this->oIW->setCampaign( $this->oC );
        $this->oIW->setPDO( $this->oPDO );
        $this->oIW->setLogger( $this->oLogger );
    }
    
    /*function testCreatePrizeToReportAndSimulatePlayAction() {
        
        $this->_init();
        
        $iHowManyTimeToPlay = 3;
        $aBarcod = array(
            "2010000000001",
            "2010000000002",
            "2010000000003",
            "2010000000004"
        );
        
        
        $oToday = new Zend_Date();
        $oToday->set( strtotime( $this->sToday . " 09:00:00" ), Zend_Date::TIMESTAMP );
        $oDTI = new DateTimeInjector( $oToday->toString( "YYYY-MM-dd HH:mm:ss" ) );
        $this->oD->setDateTimeInjector( $oDTI );
            
        //Add an entry in dotation_computed, so prize_report will be bootstrap
        $this->oD->initTodayPrizeList( $this->sCampaignId, $this->sTerminalNbr );
        
        for( $i = 0 ; $i < $iHowManyTimeToPlay ; $i++ ) {
            
            $oToday->add(1, Zend_Date::HOUR);
            
            //Create fake time
            $oDTI = new DateTimeInjector( $oToday->toString( "YYYY-MM-dd HH:mm:ss" ) );
            $this->oIW->setDateTimeInjector($oDTI);
            
            //Call instantWin
            $this->oIW->instantWin( $this->sCampaignId, $this->sTerminalNbr, $aBarcod[$i] );
        }
        
        
        $oToday2 = new Zend_Date();
        $oToday2->set( strtotime( $this->sToday . " 09:00:00" ), Zend_Date::TIMESTAMP );
        //Le 26 à 9h
        $oToday2->add(1, Zend_Date::DAY);
        $oDTI2 = new DateTimeInjector( $oToday2->toString( "YYYY-MM-dd HH:mm:ss" ) );
        $this->oD->setDateTimeInjector( $oDTI2 );
        
        //Add an entry in dotation_computed, so prize_report will be bootstrap
        $this->oD->initTodayPrizeList($this->sCampaignId, $this->sTerminalNbr);
        
        for( $i = 0 ; $i < $iHowManyTimeToPlay ; $i++ ) {
            
            $oToday2->add(1, Zend_Date::HOUR);
            
            //Create fake time
            $oDTI = new DateTimeInjector( $oToday2->toString( "YYYY-MM-dd HH:mm:ss" ) );
            $this->oIW->setDateTimeInjector($oDTI);
            
            //Call instantWin
            $this->oIW->instantWin( $this->sCampaignId, $this->sTerminalNbr, $aBarcod[$i] );
        }
        
        
        $oToday3 = new Zend_Date();
        $oToday3->set( strtotime( $this->sToday . " 09:00:00" ), Zend_Date::TIMESTAMP );
        //Le 27 à 9h
        $oToday3->add(2, Zend_Date::DAY);
        $oDTI3 = new DateTimeInjector( $oToday3->toString( "YYYY-MM-dd HH:mm:ss" ) );
        $this->oD->setDateTimeInjector( $oDTI3 );
        
        //Add an entry in dotation_computed, so prize_report will be bootstrap
        $this->oD->initTodayPrizeList( $this->sCampaignId, $this->sTerminalNbr );
        
        for( $i = 0 ; $i < $iHowManyTimeToPlay ; $i++ ) {
            
            $oToday3->add(1, Zend_Date::HOUR);
            
            //Create fake time
            $oDTI = new DateTimeInjector( $oToday3->toString( "YYYY-MM-dd HH:mm:ss" ) );
            $this->oIW->setDateTimeInjector($oDTI);
            
            //Call instantWin
            $this->oIW->instantWin( $this->sCampaignId, $this->sTerminalNbr, $aBarcod[$i] );
        }
    }*/
    
    function testSimplyLaunchInstantWin() {
        
        $sBarcodBase = "2010000000";
        $aBarcod = array();
        $sTempbarcod = "";
        
        for( $i = 0 ; $i < 300  ; $i++ ) {
            if( $i < 10 ) {
                $sTempbarcod = $sBarcodBase . "00" . $i;
                $aBarcod[] = $sTempbarcod;
            }
            else if( $i >= 10 && $i < 100 ) {
                $sTempbarcod = $sBarcodBase . "0" . $i;
                $aBarcod[] = $sTempbarcod;
            }
            else if(  $i >= 100 ) {
                $sTempbarcod = $sBarcodBase . $i;
                $aBarcod[] = $sTempbarcod;
            }
        }
        
        shuffle($aBarcod);
        $sBarcodKey = array_rand($aBarcod);
        
        $this->oIW = new InstantWinDistribution();
        $oLR = $this->oIW->instantWin( $this->sCampaignId, $this->sTerminalNbr, $aBarcod[$sBarcodKey] );
        
        $this->assertIsA($oLR, "LotteryResult");
        
        var_dump($oLR);
    }
}