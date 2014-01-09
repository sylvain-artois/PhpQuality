<?php
require_once('simpletest_1.1.0/simpletest/autorun.php');
require_once dirname(__FILE__). "/../../Config/init.php";

/**
 * SimpleTest unit-test class
 * Valid final draw algo
 *
 * @author S. Artois
 */
class TestFinalDraw extends UnitTestCase {

    /**
     * Chemin vers le fichier de fixture pour la période 1
     * 
     * @var string 
     */
    protected $dataFile4Fixture1;
    
    /**
     * Chemin vers le fichier de fixture pour la période 2
     * 
     * @var string 
     */
    protected $dataFile4Fixture2;
    
    /**
     * Chemin vers le fichier de fixture pour la période 3
     * 
     * @var string 
     */
    protected $dataFile4Fixture3;
    
    /**
     * Instance de la classe testée
     * 
     * @var PlayerApi 
     */
    protected $oPlayerApi;
    
    /**
     * @var ConfigLoader
     */
    protected $oConfigLoader;
    
    /**
     * @var PDOUtils
     */
    protected $oPDOu;
    
    /**
     * @var Campaign Instance de la classe campagne, la campagne testée
     */
    protected $oCampaign;
    
    /**
     * @var string L'id de la campagne testée
     */
    protected $sCid = "1349076528_leclerc-ifs-2012-11";
    
    /**
     * @var string Terminal nbr 
     */
    protected $sPid = "1000";
    
    function setUp() {
        
        $this->oPDOu = $GLOBALS["pdo"];
        $this->oPDOu->start()
                ->exec( "TRUNCATE `logs`" )
                ->commit();
        
        
        $this->dataFile4Fixture1 = dirname(__FILE__). "/fixtures/final_draw_test_period_1.csv";
        $this->dataFile4Fixture2 = dirname(__FILE__). "/fixtures/final_draw_test_period_2.csv";
        $this->dataFile4Fixture3 = dirname(__FILE__). "/fixtures/final_draw_test_period_3.csv";
        
        $this->oConfigLoader = new ConfigLoader();
        $this->oCampaign = $this->oConfigLoader->getCampaignData( $this->sCid, $this->sPid );
        
        $this->oPlayerApi = new PlayerApi();
    }

    function tearDown() {
        
    }
    
    /**
     * Créé un précédent gagnant
     * 
     * @param string $sDate
     * @param int $iPeriod 1|2|3, la période du TAS
     */
    protected function insertPreviousWinner( $sDate, $iPeriod ) {
        
        $oUser = new User();
        $oUser->u_password = 2959999999032;
        $oUser->u_type = 1;
        $oUser->u_email = "gg@gmail.com";
        
        $date = new Zend_Date();
        $date->set( strtotime( $sDate ), Zend_Date::TIMESTAMP );
        
        $this->oPlayerApi->logFinalDraw( $this->oCampaign, $oUser, $date, $iPeriod );
    }
    
    protected function getAllLogs() {
        
        $aLogs = array();
        $this->oPDOu->start()
                ->query( "SELECT * FROM `logs`", $aLogs )
                ->commit();
        return $aLogs;
    }
    
    function testFinalDrawOutputForPeriod1IsOk() {
        
        $aLogs = $this->getAllLogs();
        
        //precondition, no logs in db
        $this->assertEqual( count($aLogs),0 );
        
        //On créé un précédent gagnant, pour n'avoir que 2 gagnants potentiels
        $this->insertPreviousWinner("2012-11-18 15:00:00", 1);
        
        //On tire une première fois
        $result = $this->oPlayerApi->launchFinalDraw($this->oCampaign, $this->dataFile4Fixture1 );
        
        //launchFinalDraw doit renvoyer un objet user
        $this->assertIsA( $result, "User" );
        
        //2 logs attendus : le log enregistré par insertPreviousWinner et celui par launchFinalDraw
        $aLogs = $this->getAllLogs();
        $this->assertEqual(count($aLogs), 2);
        
        //le log enregistré dans launchFinalDraw doit porté le log type 21
        $this->assertEqual($aLogs[1]["logtype"], Vlog::LOG_TYPE_FINAL_DRAW);
        
        //seuls les lignes 2 et 3 sont valides dans le csv ( le 1er étant tiré au sort par insertPreviousWinner )
        $this->assertTrue( in_array( $result->u_email, array( "gg@live.com", "gg@yahoo.fr" ) ) );
        
        //On tire une 2e fois
        $result = $this->oPlayerApi->launchFinalDraw( $this->oCampaign, $this->dataFile4Fixture1 );
        
        //launchFinalDraw doit renvoyer un objet user
        $this->assertIsA( $result, "User" );
        
        //2 logs attendus : le log enregistré par insertPreviousWinner et celui par launchFinalDraw
        $aLogs = $this->getAllLogs();
        $this->assertEqual(count($aLogs), 3);
        
        //le log enregistré dans launchFinalDraw doit porté le log type 21
        $this->assertEqual($aLogs[2]["logtype"], Vlog::LOG_TYPE_FINAL_DRAW);
        
        //seuls les lignes 2 et 3 sont valides dans le csv ( le 1er étant tiré au sort par insertPreviousWinner )
        $this->assertTrue( in_array( $result->u_email, array( "gg@live.com", "gg@yahoo.fr" ) ) );
        
        //On tire une 3e fois
        $result = $this->oPlayerApi->launchFinalDraw( $this->oCampaign, $this->dataFile4Fixture1 );
        
        //launchFinalDraw doit renvoyer un objet user
        $this->assertIsA( $result, "User" );
        
        //On a lancé le TAS 3x, donc plus de joueurs potentiels, donc le système doit renvoyer une instance de user vide
        $this->assertFalse($result->u_email);
        $this->assertFalse($result->u_id);
        $this->assertFalse($result->u_password);
        $this->assertFalse($result->u_type);
    }
    
    /**
     * Vérifie que si le fichier de données est manquant, le système lance bien une exception
     */
    function testFinalDrawThrowExceptionIfDataFileNotFound() {
        
        $this->expectException();
        $this->oPlayerApi->launchFinalDraw($this->oCampaign, "C:\nimportequoi.csv" );
    }
    
    /**
     * Participent au TAS :
     * - les joueurs inscrits en période 1 ayant badgé en période 2
     * - les joueurs inscrits en période 2
     * 
     * Le fichier CSV contient 3 lignes
     * ligne 1, joueur inscrit en P1, sans log en P2
     * ligne 2, joueur inscrit en P1, avec log en P2
     * ligne 2, joueur inscrit en P2
     */
    function testFinalDrawOutputForPeriod2IsOk() {
        
        $aLogs = $this->getAllLogs();
        
        //precondition, no logs in db
        $this->assertEqual( count($aLogs),0 );
        
        //On créé 1 joueur qui a badgé en période 2, 21/11 9:00 au 1/12 20:00, mais inscript en période 1 (voir csv)
        $logs4period2 = <<<SQL
INSERT INTO  `leth_player_bdd`.`logs` (
`id` ,
`id_campaign` ,
`id_terminal` ,
`logtype` ,
`_datetime` ,
`tickets` ,
`data` ,
`barcod` ,
`prizeid`
)
VALUES (
NULL ,  '{$this->sCid}',  '{$this->sPid}',  '9',  '2012-11-25 12:00:00', NULL , NULL ,  '2959999999032',  '0'
);
SQL;

        $this->oPDOu->start()
                ->exec($logs4period2)
                ->commit();
        
        $aLogs = $this->getAllLogs();
        
        //precondition, 1 log play en DB (voir ci dessus)
        $this->assertEqual( count($aLogs), 1 );
        
        //On tire une première fois
        $result = $this->oPlayerApi->launchFinalDraw( $this->oCampaign, $this->dataFile4Fixture2, 2 );
        
        //launchFinalDraw doit renvoyer un objet user
        $this->assertIsA( $result, "User" );
        
        //2 logs attendus
        $aLogs = $this->getAllLogs();
        $this->assertEqual(count($aLogs), 2);
        
        //le log enregistré dans launchFinalDraw doit porté le log type 21
        $this->assertEqual($aLogs[1]["logtype"], Vlog::LOG_TYPE_FINAL_DRAW);
        
        //seuls les lignes 2 et 3 sont valides dans le csv ( le 1er n'ayant pas joué en P2 et inscrit en P1 )
        $this->assertTrue( in_array( $result->u_email, array( "gg@gmail.com", "gg@live.com" ) ) );
        
        //On tire une 2e fois
        $result = $this->oPlayerApi->launchFinalDraw( $this->oCampaign, $this->dataFile4Fixture2, 2 );
        
        //launchFinalDraw doit renvoyer un objet user
        $this->assertIsA( $result, "User" );
        
        //2 logs attendus
        $aLogs = $this->getAllLogs();
        $this->assertEqual(count($aLogs), 3);
        
        //le log enregistré dans launchFinalDraw doit porté le log type 21
        $this->assertEqual($aLogs[2]["logtype"], Vlog::LOG_TYPE_FINAL_DRAW);
        
        //seuls les lignes 2 et 3 sont valides dans le csv ( le 1er n'ayant pas joué en P2 et inscrit en P1 )
        $this->assertTrue( in_array( $result->u_email, array( "gg@gmail.com", "gg@live.com" ) ) );
        
        //On tire une 3e fois
        $result = $this->oPlayerApi->launchFinalDraw( $this->oCampaign, $this->dataFile4Fixture2, 2 );
        
        //launchFinalDraw doit renvoyer un objet user
        $this->assertIsA( $result, "User" );
        
        //On a lancé le TAS 3x, donc plus de joueurs potentiels, donc le système doit renvoyer une instance de user vide
        $this->assertFalse($result->u_email);
        $this->assertFalse($result->u_id);
        $this->assertFalse($result->u_password);
        $this->assertFalse($result->u_type);
    }
    
    /**
     * Participent au TAS :
     * - les joueurs inscrits en période 1 ou 2 ayant badgé en période 3
     * - les joueurs inscrits en période 3
     * 
     * Le fichier CSV contient 3 lignes
     * ligne 1, joueur inscrit en P1, sans log en P3
     * ligne 2, joueur inscrit en P1, avec log en P3
     * ligne 2, joueur inscrit en P3
     * 
     * Période 3 : 5/12 9:00 au 15/12 20:00
     * 
     */
    function testFinalDrawOutputForPeriod3IsOk() {
        
        $aLogs = $this->getAllLogs();
        
        //precondition, no logs in db
        $this->assertEqual( count( $aLogs ),0 );
        
        //On créé 1 joueur qui a badgé en période 3, 5/12 9:00 au 15/12 20:00, mais inscript en période 1 (voir csv)
        $logs4period2 = <<<SQL
INSERT INTO  `leth_player_bdd`.`logs` (
`id` ,
`id_campaign` ,
`id_terminal` ,
`logtype` ,
`_datetime` ,
`tickets` ,
`data` ,
`barcod` ,
`prizeid`
)
VALUES (
NULL ,  '{$this->sCid}',  '{$this->sPid}',  '9',  '2012-12-10 12:00:00', NULL , NULL ,  '2959999999032',  '0'
);
SQL;

        $this->oPDOu->start()
                ->exec($logs4period2)
                ->commit();
        
        $aLogs = $this->getAllLogs();
        
        //precondition, 1 log play en DB (voir ci dessus)
        $this->assertEqual( count($aLogs), 1 );
        
        //On tire une première fois
        $result = $this->oPlayerApi->launchFinalDraw( $this->oCampaign, $this->dataFile4Fixture3, 3 );
        
        //launchFinalDraw doit renvoyer un objet user
        $this->assertIsA( $result, "User" );
        
        //2 logs attendus
        $aLogs = $this->getAllLogs();
        $this->assertEqual(count($aLogs), 2);
        
        //le log enregistré dans launchFinalDraw doit porté le log type 21
        $this->assertEqual($aLogs[1]["logtype"], Vlog::LOG_TYPE_FINAL_DRAW);
        
        //seuls les lignes 2 et 3 sont valides dans le csv ( le 1er n'ayant pas joué en P2 et inscrit en P1 )
        $this->assertTrue( in_array( $result->u_email, array( "gg@gmail.com", "gg@live.com" ) ) );
        
        //On tire une 2e fois
        $result = $this->oPlayerApi->launchFinalDraw( $this->oCampaign, $this->dataFile4Fixture3, 3 );
        
        //launchFinalDraw doit renvoyer un objet user
        $this->assertIsA( $result, "User" );
        
        //2 logs attendus
        $aLogs = $this->getAllLogs();
        $this->assertEqual(count($aLogs), 3);
        
        //le log enregistré dans launchFinalDraw doit porté le log type 21
        $this->assertEqual($aLogs[2]["logtype"], Vlog::LOG_TYPE_FINAL_DRAW);
        
        //seuls les lignes 2 et 3 sont valides dans le csv ( le 1er n'ayant pas joué en P2 et inscrit en P1 )
        $this->assertTrue( in_array( $result->u_email, array( "gg@gmail.com", "gg@live.com" ) ) );
        
        //On tire une 3e fois
        $result = $this->oPlayerApi->launchFinalDraw( $this->oCampaign, $this->dataFile4Fixture3, 3 );
        
        //launchFinalDraw doit renvoyer un objet user
        $this->assertIsA( $result, "User" );
        
        //On a lancé le TAS 3x, donc plus de joueurs potentiels, donc le système doit renvoyer une instance de user vide
        $this->assertFalse($result->u_email);
        $this->assertFalse($result->u_id);
        $this->assertFalse($result->u_password);
        $this->assertFalse($result->u_type);
    }
}

