<?php

/**
 * PlayerApi test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class PlayerApiFunctionalTest extends PHPUnit_Extensions_Database_TestCase {

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


    public function testMergeDataFileForFinalDrawCalled3Times() {
        $this->init("1331109874_test", "1", "2012-04-09");

        $sSrcFile1 = realpath(dirname(__FILE__).'/../Fixtures/records2.csv');
        $sSrcFile2 = realpath(dirname(__FILE__).'/../Fixtures/records4.csv');
        $sSrcFile3 = realpath(dirname(__FILE__).'/../Fixtures/records5.csv');

        $sFile1 = $this->sTempDir . "/file1.csv";
        $sFile2 = $this->sTempDir . "/file2.csv";
        $sFile3 = $this->sTempDir . "/file3.csv";
        $this->assertTrue(copy($sSrcFile1, $sFile1));
        $this->assertTrue(copy($sSrcFile2, $sFile2));
        $this->assertTrue(copy($sSrcFile3, $sFile3));

        $sMergedFile = $this->oA->mergeDataFileForFinalDraw($this->oC, $sFile1, $sFile2);
        $sMergedFile = $this->oA->mergeDataFileForFinalDraw($this->oC, $sFile1, $sFile3);

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
                                 "test43@gmail.com", "test44@gmail.com",
                                 "test44@gmail.com");

        $aExpectedBarcodes = array("355568", "355569", "355570", "355571",
                                   "355572", "355573", "355574", "355575",
                                   "355576");

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

