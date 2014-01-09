<?php

/**
 * ScheduleEditor test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class ScheduleEditorFunctionalTest extends PHPUnit_Extensions_Database_TestCase {
    
    /**
     * @var ScheduleEditor
     */
    private $oS;
    
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


        $this->oS = new ScheduleEditor();
        $this->oS->setPDO($this->oPDO);
        
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
    public function testScheduleEditorThrowsExceptionIfScheduleMorningIsIncomplete()
    {
        $this->init();
        $oSchedule = $this->oC->curShedule;
        $oSchedule->morning_dbtupple = null;
        $this->oS->updateSchedule($oSchedule);
    }

    /**
     * @expectedException DomainException
     */
    public function testScheduleEditorThrowsExceptionIfScheduleAfternoonIsIncomplete()
    {
        $this->init();
        $oSchedule = $this->oC->curShedule;
        $this->assertTrue($oSchedule->isBreaked);
        $oSchedule->afternoon_dbtupple = null;
        $this->oS->updateSchedule($oSchedule);
    }

    public function testScheduleEditor()
    {
        $this->init();

        $oMorningBegin = new Zend_Date();
        $oMorningBegin->set( strtotime( "2012-04-04 08:15:40" ), Zend_Date::TIMESTAMP );
        $oMorningEnd = new Zend_Date();
        $oMorningEnd->set( strtotime( "2012-04-04 11:02:51" ), Zend_Date::TIMESTAMP );
        $oAfternoonBegin = new Zend_Date();
        $oAfternoonBegin->set( strtotime( "2012-04-04 13:02:00" ), Zend_Date::TIMESTAMP );
        $oAfternoonEnd = new Zend_Date();
        $oAfternoonEnd->set( strtotime( "2012-04-04 17:05:00" ), Zend_Date::TIMESTAMP );

        $oSchedule = $this->oC->curShedule;
        $oSchedule->morning_begin = $oMorningBegin;
        $oSchedule->morning_end = $oMorningEnd;
        $this->assertTrue($oSchedule->isBreaked);
        $oSchedule->afternoon_begin = $oAfternoonBegin;
        $oSchedule->afternoon_end = $oAfternoonEnd;

        $sMorningId = $oSchedule->morning_dbtupple->id;
        $sAfternoonId = $oSchedule->afternoon_dbtupple->id;

        $oRes = $this->oS->updateSchedule($oSchedule);

        // Check that db tupples stored in object are up to date
        $this->assertInstanceOf('Schedule', $oRes);

        $oExpectedMorning = (object) array(
            "id" => $sMorningId,
            "id_dotation" => "1329132446_dotation_1",
            "day" => "2012-04-04",
            "begin" => "08:15:40",
            "end" => "11:02:51"
        );
        $oExpectedAfternoon = (object) array(
            "id" => $sAfternoonId,
            "id_dotation" => "1329132446_dotation_1",
            "day" => "2012-04-04",
            "begin" => "13:02:00",
            "end" => "17:05:00"
        );
        $this->assertEquals($oExpectedMorning, $oRes->morning_dbtupple);
        $this->assertEquals($oExpectedAfternoon, $oRes->afternoon_dbtupple);

        // Check that database is up to date
        $this->oPDO->getRow("timetable", sprintf("id=%d", (int) $sMorningId), $aMRow);
        $this->oPDO->getRow("timetable", sprintf("id=%d", (int) $sAfternoonId), $aARow);
        $this->assertEquals($oExpectedMorning, $aMRow);
        $this->assertEquals($oExpectedAfternoon, $aARow);
    }



}

