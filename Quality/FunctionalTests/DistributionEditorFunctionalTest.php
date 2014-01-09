<?php

/**
 * DistributionEditor test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class DistributionEditorFunctionalTest extends PHPUnit_Extensions_Database_TestCase {
    
    /**
     * @var DistributionEditor
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
        

        $this->oL = new ConfigLoader();
        $this->oL->setToday($this->sToday);
        $this->oL->setPDO($this->oPDO);
        $this->oC = $this->oL->getCampaignData( $this->sCampaignId, $this->sTerminalNbr);


        $this->oD = new DistributionEditor();
        $this->oD->setPDO($this->oPDO);
        
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
    public function testUpdatePrizeHasDotationThrowsExceptionIfNoId()
    {
        $this->init();

        $oPAD = $this->oC->curPrizeHasDotation[0];
        $this->assertInstanceOf("PrizeHasDotation", $oPAD);

        $oPAD->id = null;
        $this->oD->updatePrizeHasDotation($oPAD);
    }


    /**
     * @expectedException DomainException
     */
    public function testUpdatePrizeHasDotationThrowsExceptionIfInconsistentDates()
    {
        $this->init();

        $oPAD = $this->oC->curPrizeHasDotation[0];

        // precondition
        $this->assertInstanceOf("PrizeHasDotation", $oPAD);
        $this->assertEquals("2012-04-04", $oPAD->raw_date);

        // We change the raw_date but not the _date
        $oPAD->raw_date = "2012-04-05";

        $this->oD->updatePrizeHasDotation($oPAD);
    }


    public function testUpdatePrizeHasDotation()
    {
        $this->init();

        $oPAD = $this->oC->curPrizeHasDotation[0];
        $sId = $oPAD->id;
        $sIdDotation = $oPAD->id_dotation;
        $sIdPrize = $oPAD->id_prize;

        $oDate = new Zend_Date();
        $oDate->set( strtotime( "2012-04-05" ), Zend_Date::TIMESTAMP );
        $aWinningTime = array("13:05:00", "14:20:00", "15:29:00");

        $oPAD->amount = 42;
        $oPAD->raw_date = "2012-04-05";
        $oPAD->_date = $oDate;
        $oPAD->alreadydeal = 20;
        $oPAD->winningtime = $aWinningTime;

        $oRes = $this->oD->updatePrizeHasDotation($oPAD);
        $this->assertInstanceOf("PrizeHasDotation", $oRes);

        // On verifie que l'objet renvoye contient les bonnes valeurs
        $this->assertEquals($sId, $oRes->id);
        $this->assertEquals($sIdDotation, $oRes->id_dotation);
        $this->assertEquals($sIdPrize, $oRes->id_prize);
        $this->assertEquals($oDate, $oRes->_date);
        $this->assertEquals("2012-04-05", $oRes->raw_date);
        $this->assertEquals(42, $oRes->amount);
        $this->assertEquals(20, $oRes->alreadydeal);
        $this->assertEquals($aWinningTime, $oRes->winningtime);

        // On verifie qu'il y a egalement les bonnes valeurs en BDD
        $oRow = array();
        $this->oPDO->getRow("prizehasdotation",
                            sprintf("id=%d", (int) $sId),
                            $oRow);

        $this->assertEquals($sIdDotation, $oRow->id_dotation);
        $this->assertEquals($sIdPrize, $oRow->id_prize);
        $this->assertEquals("2012-04-05", $oRow->_date);
        $this->assertEquals(42, $oRow->amount);
        $this->assertEquals(20, $oRow->alreadydeal);
        $this->assertEquals(serialize($aWinningTime), $oRow->winningtime);


        // On va a nouveau enregistrer une modification, en mettant winningTime a null
        $oPAD->winningtime = null;
        $oRes = $this->oD->updatePrizeHasDotation($oPAD);

        $oRow = array();
        $this->oPDO->getRow("prizehasdotation",
                            sprintf("id=%d", (int) $sId),
                            $oRow);
        $this->assertTrue(empty($oRes->winningtime));
        $this->assertNull($oRow->winningtime);


    }



}

