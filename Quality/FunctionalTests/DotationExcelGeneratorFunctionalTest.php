<?php

/**
 * DotationExcelGenerator test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class DotationExcelGeneratorFunctionalTest extends PHPUnit_Extensions_Database_TestCase {

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


    public function testExcelGeneratorJustWork() {
        $nBSheet=10;
        $nameOperation="test11:18";
        $nbLots=6;
        $dateStart="02-06-2012";
        $dateEnd="26-02-2012";
        $coupure="1";
        $hours=array(
            (object)array("begin"=> "09:00", "end"=> "12:00", "begin1"=>"13:00", "end1"=>"21:00","day"=>"lun" ),
            (object)array("begin"=> "09:00", "end"=> "12:00", "begin1"=>"13:00", "end1"=>"21:00","day"=>"mar" ),
            (object)array("begin"=> "09:00", "end"=> "12:00", "begin1"=>"13:00", "end1"=>"21:00","day"=>"mer" ),
            (object)array("begin"=> "09:00", "end"=> "12:00", "begin1"=>"13:00", "end1"=>"21:00","day"=>"jeu" ),
            (object)array("begin"=> "09:00", "end"=> "12:00", "begin1"=>"13:00", "end1"=>"21:00","day"=>"ven" ),
            (object)array("begin"=> "09:00", "end"=> "12:00", "begin1"=>"13:00", "end1"=>"21:00","day"=>"sam" ),
            (object)array("begin"=> "09:00", "end"=> "12:00", "begin1"=>"13:00", "end1"=>"21:00","day"=>"dim" )
        );
        $prizes=array("Playstation","Peigne","Clé USB","Voyage à LasVegas","Serviette de toilette","Une glace");

        $oDEG=new DotationExcelGenerator();
        $sTest =$oDEG->generate($this->sTempDir, $nBSheet, $nameOperation, $nbLots, $dateStart,$dateEnd, $coupure, $hours, $prizes);

        $this->assertInternalType('string', $sTest);
    }

}