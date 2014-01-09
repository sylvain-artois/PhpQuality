<?php

/**
 * DotationExcelReader test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class DotationExcelReaderFunctionalTest extends PHPUnit_Extensions_Database_TestCase {

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

    public function setUp() {
        parent::setUp();
        // Creation d'un repertoire temporaire
        $this->sTempDir = tempnam(sys_get_temp_dir(), "test-");
        @unlink($this->sTempDir);
        mkdir($this->sTempDir);
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


    /**
     * Prepares the environment before running a test.
     */
    public function init() {

      $this->oPDO = new PDOUtils(DB_DBNAME, DB_HOST, DB_USER, DB_PASSWD );
      $GLOBALS['pdo'] = $this->oPDO;
    }


    public function testExcelReaderJustWork() {

        $this->init();

        $sDirectory = realpath(dirname(__FILE__).'/../Fixtures/export_c1/');

        // Avant de tester DotationExcelReader, on va importer une campagne depuis un csv
        $oW = new ConfigWriter();
        $oW->import($sDirectory);


        $oDER = new DotationExcelReader();

        $sCampaignId="4";
        $iNbDotation=1;
        $iNbPrize=15;
        $bLaunchBreak=true;
        $sRealDateStart="2011-11-05";
        $aPrize = array();

        $oP1 = new Prize();
        $oP1->setFromDB((object)array(
                            "id"=>"1329132415_Cle-USB",
                            "id_campaign"=>"1329132128_Test-1,-12-21",
                            "name"=> utf8_encode("Clé USB") ,
                            "is_jackpot"=>FALSE,
                            "is_booby"=>TRUE,
                            "is_lost"=>FALSE,
                            "prize_nbr"=>3
                        ));
        //$aPrize[]=$oP1;

        $oP2 = new Prize();
        $oP2->setFromDB((object)array(
                            "id"=>"1329132415_Galaxy-Note",
                            "id_campaign"=>"1329132128_Test-1,-12-21",
                            "name"=>"Galaxy Note",
                            "is_jackpot"=>FALSE,
                            "is_booby"=>FALSE,
                            "is_lost"=>FALSE,
                            "prize_nbr"=>2
                        ));
        //$aPrize[]=$oP2;

        $oP3 = new Prize();
        $oP3->setFromDB((object)array(
                            "id"=>"1329132415_Voyage-aux-Seychelles",
                            "id_campaign"=>"1329132128_Test-1,-12-21",
                            "name"=>"Voyage aux Seychelles",
                            "is_jackpot"=>TRUE,
                            "is_booby"=>FALSE,
                            "is_lost"=>FALSE,
                            "prize_nbr"=>1
                        ));
        //$aPrize[]=$oP3;

        $aData = $oDER->readAndInsert( $sDirectory, $sCampaignId, $iNbDotation, $iNbPrize, $bLaunchBreak, $sRealDateStart,$aPrize, "1" );

        $this->assertInstanceOf("Campaign", $aData);

    }

}