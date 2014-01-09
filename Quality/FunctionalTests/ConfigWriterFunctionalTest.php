<?php

/**
 * ConfigWriter test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class ConfigWriterFunctionalTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * @var PDOUtils
     */
    private $oPDO;

    /**
     * @var ConfigWriter
     */
    private $oW;

    /**
     * @var ConfigLoader
     */
    private $oL;

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
     * Prepares the environment before running a test.
     */
    public function init($sToday) {

      $this->oPDO = new PDOUtils(DB_DBNAME, DB_HOST, DB_USER, DB_PASSWD );
      $GLOBALS['pdo'] = $this->oPDO;
    }

    public function setUp() {
        parent::setUp();
        // Creation d'un repertoire temporaire dans lequel mysql aura le droit d'ecrire
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

    public function testConfigWriterJustWork() {
        $this->init("2012-04-04");

        $oLocale = new Zend_Locale('fr_FR');
        $GLOBALS["zend_locale"]=$oLocale;

        $oFakeCampaign = new Campaign();
        $oFakeCampaign->id="1331109874_test";
        $oFakeCampaign->name="Test-1,-12:21";
        $oFakeCampaign->distribution="Regular";
        $oFakeCampaign->fileSystemPath="C:\\Documents and Settings\\administrateur.LESTANUKIS\\Bureau\\Campagnes\\test1";

        $oFakeCampaign->begin= new Zend_Date();
        $oFakeCampaign->beginTest= new Zend_Date();
        $oFakeCampaign->end=new Zend_Date();

        $oFakeCampaign->randomRangeMin=40;
        $oFakeCampaign->randomRangePercent=10;

        $oFakeCampaign->allow_scheduleChange=false;
        $oFakeCampaign->allow_jackpotChange=false;
        $oFakeCampaign->allow_prizeListReport=false;

        $oFakeCampaign->mess_bad_barcode="Booo";
        $oFakeCampaign->mess_alreadyPlayed="Booo";
        $oFakeCampaign->errorMessageBkgColor="0xff6600";
        $oFakeCampaign->numOccurBarCode=4;
        $oFakeCampaign->campaignOrDay="D";

        $oFakeCampaign->jackpotType="None";
        $oFakeCampaign->jackpotDesc=unserialize('O:7:"Jackpot":7:{s:10:"time_start";N;s:8:"time_end";N;s:7:"message";N;s:4:"port";N;s:5:"begin";N;s:3:"end";N;s:13:"_explicitType";s:7:"Jackpot";}');

        $oP1 = new Prize();
        $oP1->setFromDB((object)array(
                            "id"=>"1329132415_Cle-USB",
                            "id_campaign"=>"1331109874_test",
                            "name"=> utf8_encode("Clé USB") ,
                            "is_jackpot"=>FALSE,
                            "is_booby"=>TRUE,
                            "is_lost"=>FALSE,
                            "prize_nbr"=>3
                        ));

        $oFakeCampaign->prizes[]=$oP1;

        $oP2 = new Prize();
        $oP2->setFromDB((object)array(
                            "id"=>"1329132415_Galaxy-Note",
                            "id_campaign"=>"1331109874_test",
                            "name"=>"Galaxy Note",
                            "is_jackpot"=>FALSE,
                            "is_booby"=>FALSE,
                            "is_lost"=>FALSE,
                            "prize_nbr"=>2
                        ));

        $oFakeCampaign->prizes[]=$oP2;

        $oP3 = new Prize();
        $oP3->setFromDB((object)array(
                            "id"=>"1329132415_Voyage-aux-Seychelles",
                            "id_campaign"=>"1331109874_test",
                            "name"=>"Voyage aux Seychelles",
                            "is_jackpot"=>TRUE,
                            "is_booby"=>FALSE,
                            "is_lost"=>FALSE,
                            "prize_nbr"=>1
                        ));

        $oFakeCampaign->prizes[]=$oP3;

        $oW = new ConfigWriter();
        $oR = $oW->save( $oFakeCampaign );

        $this->assertInstanceOf('Campaign', $oR);
    }

    public function testExportJustWork() {
        $this->init("2012-04-04");

        $oFakeCampaign = new Campaign();
        $oFakeCampaign->id="1328202255_test18:02";
        $oFakeCampaign->id="1331109874_test";

        $oW = new ConfigWriter();
        $bRes = $oW->export($oFakeCampaign, $this->sTempDir);
        $this->assertTrue( $bRes );

        $this->assertTrue(file_exists($this->sTempDir . "/campaign.csv"));
        $this->assertTrue(file_exists($this->sTempDir . "/dotation.csv"));
        $this->assertTrue(file_exists($this->sTempDir . "/prize.csv"));
        $this->assertTrue(file_exists($this->sTempDir . "/prizehasdotation.csv"));
        $this->assertTrue(file_exists($this->sTempDir . "/series.csv"));
        $this->assertTrue(file_exists($this->sTempDir . "/terminalhasdotation.csv"));
        $this->assertTrue(file_exists($this->sTempDir . "/timetable.csv"));
        $this->assertTrue(file_exists($this->sTempDir . "/users.csv"));
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


