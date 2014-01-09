<?php

/**
 * Statistic test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class StatisticTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * @var Statistic
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

        $this->oS = new Statistic();
        $this->oS->setPDO($this->oPDO);
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
        $class = new ReflectionClass('Statistic');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testCreateDateRangeArray() {

        $this->init("1331109874_test", "1", "2012-04-04");

        $oDateStart = new Zend_Date();
        $oDateStart->set( strtotime( "2012-04-28" ), Zend_Date::TIMESTAMP );

        $oDateEnd = new Zend_Date();
        $oDateEnd->set( strtotime( "2012-05-04" ), Zend_Date::TIMESTAMP );

        $aExpectedResult = array(
            "2012-04-28" => 0,
            "2012-04-29" => 0,
            "2012-04-30" => 0,
            "2012-05-01" => 0,
            "2012-05-02" => 0,
            "2012-05-03" => 0,
            "2012-05-04" => 0,
        );

        $fCreateDateRangeArray = self::getMethod('createDateRangeArray');
        $aRes = $fCreateDateRangeArray->invokeArgs($this->oS,
                                                   array($oDateStart, $oDateEnd));
        $this->assertEquals($aExpectedResult, $aRes);
    }


    public function testGetPlayerAmount() {

        $this->init("1331109874_test", "1", "2012-04-04");

        $oDateStart = new Zend_Date();
        $oDateStart->set( strtotime( "2012-04-04" ), Zend_Date::TIMESTAMP );

        $oDateEnd = new Zend_Date();
        $oDateEnd->set( strtotime( "2012-04-08" ), Zend_Date::TIMESTAMP );

        $aExpectedResult = array(
            '2012-04-04' => 4,
            '2012-04-05' => 0,
            '2012-04-06' => 3,
            '2012-04-07' => 0,
            '2012-04-08' => 0,
        );
        $this->assertEquals($aExpectedResult,
                            $this->oS->getPlayerAmount($this->oC, $oDateStart,
                                                       $oDateEnd));

        $aExpectedResult = array(
            '2012-04-04' => 4
        );
        $this->assertEquals($aExpectedResult,
                            $this->oS->getPlayerAmount($this->oC, $oDateStart));


    }

    public function testGetLogsByDayWithNoFilter() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $aLogs = $this->oS->getLogsByDay($this->oC);
        $this->assertInternalType("array", $aLogs);
        $this->assertEquals(9, count($aLogs));
        foreach ($aLogs as $oLog) {
            $this->assertInstanceOf("Vlog", $oLog);
            $this->assertEquals("1331109874_test", $oLog->id_campaign);
        }
    }

    public function testGetLogsByDayWithLogtypeFilter() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $aLogs = $this->oS->getLogsByDay($this->oC, 9);
        $this->assertInternalType("array", $aLogs);
        $this->assertEquals(7, count($aLogs));
        foreach ($aLogs as $oLog) {
            $this->assertInstanceOf("Vlog", $oLog);
            $this->assertEquals("1331109874_test", $oLog->id_campaign);
            $this->assertEquals(9, $oLog->logtype);
        }
    }

    public function testGetLogsByDayWithStartDateFilter() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $oDateStart = new Zend_Date();
        $oDateStart->set( strtotime( "2012-04-05" ), Zend_Date::TIMESTAMP );

        $aLogs = $this->oS->getLogsByDay($this->oC, 0, $oDateStart);
        $this->assertInternalType("array", $aLogs);
        $this->assertEquals(5, count($aLogs));
        foreach ($aLogs as $oLog) {
            $this->assertInstanceOf("Vlog", $oLog);
            $this->assertEquals("1331109874_test", $oLog->id_campaign);
        }

        $aLogs = $this->oS->getLogsByDay($this->oC, 9, $oDateStart);
        $this->assertInternalType("array", $aLogs);
        $this->assertEquals(3, count($aLogs));
        foreach ($aLogs as $oLog) {
            $this->assertInstanceOf("Vlog", $oLog);
            $this->assertEquals("1331109874_test", $oLog->id_campaign);
            $this->assertEquals(9, $oLog->logtype);
        }
    }


    public function testGetLogsByDayWithStartDateAndEndDateFilter() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $oDateStart = new Zend_Date();
        $oDateStart->set( strtotime( "2012-04-04" ), Zend_Date::TIMESTAMP );
        $oDateEnd = new Zend_Date();
        $oDateEnd->set( strtotime( "2012-04-05" ), Zend_Date::TIMESTAMP );

        $aLogs = $this->oS->getLogsByDay($this->oC, 0, $oDateStart, $oDateEnd);
        $this->assertInternalType("array", $aLogs);
        $this->assertEquals(5, count($aLogs));
        foreach ($aLogs as $oLog) {
            $this->assertInstanceOf("Vlog", $oLog);
            $this->assertEquals("1331109874_test", $oLog->id_campaign);
        }

        $aLogs = $this->oS->getLogsByDay($this->oC, 1, $oDateStart, $oDateEnd);
        $this->assertInternalType("array", $aLogs);
        $this->assertEquals(1, count($aLogs));
        foreach ($aLogs as $oLog) {
            $this->assertInstanceOf("Vlog", $oLog);
            $this->assertEquals("1331109874_test", $oLog->id_campaign);
            $this->assertEquals(1, $oLog->logtype);
        }
    }

    /**
     * @expectedException RuntimeException
     */
    public function testExportStatThrowExceptionIfCannotCreateFile() {
        $this->init("1331109874_test", "1", "2012-04-04");
        $sFile = "/unknown/directory/test.csv";
        $this->oS->exportStat($this->oC, $sFile, false, 0);
    }


    /**
     * @expectedException RuntimeException
     */
    public function testExportStatThrowExceptionIfCannotCreateCompressedFile() {
        $this->init("1331109874_test", "1", "2012-04-04");
        $sFile = "/unknown/directory/test.zip";
        $this->oS->exportStat($this->oC, $sFile, true, 0);
    }


    public function testExportStatWithNoFilter() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $sFile = $this->sTempDir . '/test-export.csv';

        $this->assertTrue($this->oS->exportStat($this->oC, $sFile, false, 0));
        $fCSV = fopen($sFile, "r");
        $this->assertNotEquals(FALSE, $fCSV);
        $iCount = 0;
        while (($aRow = fgetcsv($fCSV)) !== FALSE) {
            $iCount++;
        }
        $this->assertEquals(9, $iCount);
        fclose($fCSV);
    }

    public function testExportStatWithLogTypeFilter() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $sFile = $this->sTempDir . '/test-export.csv';

        $this->assertTrue($this->oS->exportStat($this->oC, $sFile, false, 9));
        $fCSV = fopen($sFile, "r");
        $this->assertNotEquals(FALSE, $fCSV);
        $iCount = 0;
        while (($aRow = fgetcsv($fCSV)) !== FALSE) {
            $iCount++;
        }
        $this->assertEquals(7, $iCount);
        fclose($fCSV);
    }

    public function testExportStatWithStartDateFilter() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $oDateStart = new Zend_Date();
        $oDateStart->set( strtotime( "2012-04-05" ), Zend_Date::TIMESTAMP );

        $sFile = $this->sTempDir . '/test-export.csv';

        $this->assertTrue($this->oS->exportStat($this->oC, $sFile, false, 0, $oDateStart));
        $fCSV = fopen($sFile, "r");
        $this->assertNotEquals(FALSE, $fCSV);
        $iCount = 0;
        while (($aRow = fgetcsv($fCSV)) !== FALSE) {
            $iCount++;
        }
        $this->assertEquals(5, $iCount);
        fclose($fCSV);
    }

    public function testExportStatWithStartDateAndEndDateFilter() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $oDateStart = new Zend_Date();
        $oDateStart->set( strtotime( "2012-04-05" ), Zend_Date::TIMESTAMP );

        $oDateEnd = new Zend_Date();
        $oDateEnd->set( strtotime( "2012-04-06" ), Zend_Date::TIMESTAMP );

        $sFile = $this->sTempDir . '/test-export.csv';

        $this->assertTrue($this->oS->exportStat($this->oC, $sFile, false,
                                                0, $oDateStart, $oDateEnd));
        $fCSV = fopen($sFile, "r");
        $this->assertNotEquals(FALSE, $fCSV);
        $iCount = 0;
        while (($aRow = fgetcsv($fCSV)) !== FALSE) {
            $iCount++;
        }
        $this->assertEquals(4, $iCount);
        fclose($fCSV);
    }

    public function testExportStatWithCompression() {
        $this->init("1331109874_test", "1", "2012-04-04");

        $oDateStart = new Zend_Date();
        $oDateStart->set( strtotime( "2012-04-05" ), Zend_Date::TIMESTAMP );

        $oDateEnd = new Zend_Date();
        $oDateEnd->set( strtotime( "2012-04-06" ), Zend_Date::TIMESTAMP );

        $sFile = $this->sTempDir . '/test-export.zip';

        $this->assertTrue($this->oS->exportStat($this->oC, $sFile, true,
                                                0, $oDateStart, $oDateEnd));


        $oZip = new ZipArchive;
        if ($oZip->open($sFile) === TRUE) {
            $oZip->extractTo($this->sTempDir);
            $oZip->close();
        }

        $fCSV = fopen($this->sTempDir . "/logs.csv", "r");
        $this->assertNotEquals(FALSE, $fCSV);
        $iCount = 0;
        while (($aRow = fgetcsv($fCSV)) !== FALSE) {
            $iCount++;
        }
        $this->assertEquals(4, $iCount);
        fclose($fCSV);
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

