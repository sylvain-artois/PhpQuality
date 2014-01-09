<?php
require_once('simpletest_1.1.0/simpletest/autorun.php');
require_once dirname(__FILE__). "/../../Config/init.php";
require_once "TestFinalDraw.php";

/**
 * Simple test to isolate the TAS bug discovered by F.M. before ifs
 *
 * @author S. Artois
 */
class BugTas extends TestFinalDraw {
    
    /**
     * Chemin vers le fichier de fixture pour la période 1
     * 
     * @var string 
     */
    private $dataFile4Fixture;
    
    private $gameLog = <<<SQL
INSERT INTO `logs` (`id_campaign`, `id_terminal`, `logtype`, `_datetime`, `tickets`, `data`, `barcod`, `prizeid`) VALUES
('1349076528_leclerc-ifs-2012-11', 1000, 9, '2012-11-08 10:33:44', NULL, 'NOT_INSTANT_WIN', '2999999993289', '0'),
('1349076528_leclerc-ifs-2012-11', 1000, 9, '2012-11-08 10:34:40', NULL, 'NOT_INSTANT_WIN', '2999999993319', '0'),
('1349076528_leclerc-ifs-2012-11', 1000, 9, '2012-11-22 10:37:20', NULL, 'NOT_INSTANT_WIN', '2999999993319', '0');
SQL;
    
    /**
     * Called before each test
     */
    function setUp() {
        
        parent::setUp();
        
        $this->dataFile4Fixture = dirname(__FILE__). "/fixtures/bug_tas.csv";
    }
    
    public function testP2() {
        
         //precondition, no logs in db
        $aLogs = $this->getAllLogs();
        $this->assertEqual( count($aLogs),0 );
        
        $this->oPDOu->start()
                ->exec($this->gameLog)
                ->commit();
        
        //precondition, 3 logs in db
        $aLogs = $this->getAllLogs();
        $this->assertEqual( count($aLogs), 3 );
        
        //On tire une première fois
        $result = $this->oPlayerApi->launchFinalDraw( $this->oCampaign, $this->dataFile4Fixture );
        
        //launchFinalDraw doit renvoyer un objet user
        $this->assertIsA( $result, "User" );
        
        //4 logs attendus : le log enregistré par insertPreviousWinner et celui par $this->gameLog (fixture)
        $aLogs = $this->getAllLogs();
        $this->assertEqual(count($aLogs), 4);
        
        //le log enregistré dans launchFinalDraw doit porté le log type 21
        $this->assertEqual( $aLogs[1]["logtype"], Vlog::LOG_TYPE_FINAL_DRAW );
        
        //seul le 2e joueur a badgé en P2
        $this->assertEqual( $result->u_email, "p1badgep2@yy.fr" );
    }
}
