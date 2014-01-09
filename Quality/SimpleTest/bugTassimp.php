<?php
require_once('simpletest_1.1.0/simpletest/autorun.php');
require_once dirname(__FILE__). "/../../Config/init.php";

$sCid="1349076528_leclerc-ifs-2012-11";
$sPid=1000;
$dataFile4Fixture = dirname(__FILE__). "/fixtures/bug_tas.csv";
$gameLog = <<<SQL
INSERT INTO `logs` (`id_campaign`, `id_terminal`, `logtype`, `_datetime`, `tickets`, `data`, `barcod`, `prizeid`) VALUES
('1349076528_leclerc-ifs-2012-11', 1000, 9, '2012-11-08 10:33:44', NULL, 'NOT_INSTANT_WIN', '2999999993289', '0'),
('1349076528_leclerc-ifs-2012-11', 1000, 9, '2012-11-08 10:34:40', NULL, 'NOT_INSTANT_WIN', '2999999993319', '0'),
('1349076528_leclerc-ifs-2012-11', 1000, 9, '2012-11-22 10:37:20', NULL, 'NOT_INSTANT_WIN', '2999999993319', '0');
SQL;

$oPDOu = $GLOBALS["pdo"];
$oPDOu->start()
    ->exec( "TRUNCATE `logs`" )
    ->commit();
        
$oConfigLoader = new ConfigLoader();
$oCampaign = $oConfigLoader->getCampaignData( $sCid, $sPid );
        
$oPlayerApi = new PlayerApi();


 //precondition, no logs in db
$aLogs = getAllLogs();
assertEqual( count($aLogs), 0 );
        
$oPDOu->start()
    ->exec( $gameLog )
    ->commit();
        
//precondition, 3 logs in db
$aLogs = getAllLogs();
assertEqual( count($aLogs), 3 );
        
//On tire une première fois
$result = $oPlayerApi->launchFinalDraw( $oCampaign, $dataFile4Fixture, 2 );
        
//launchFinalDraw doit renvoyer un objet user
assertTrue( get_class($result)==="User" );
        
//4 logs attendus : le log enregistré par insertPreviousWinner et celui par $gameLog (fixture)
$aLogs = getAllLogs();
assertEqual(count($aLogs), 4);
        
//le log enregistré dans launchFinalDraw doit porté le log type 21
assertEqual( $aLogs[3]["logtype"], Vlog::LOG_TYPE_FINAL_DRAW );
        
//seul le 2e joueur a badgé en P2
assertEqual( $result->u_email, "p1badgep2@yy.fr" );


function getAllLogs() {
    
    global $oPDOu;
    
    $aLogs = array();
    $oPDOu->start()
        ->query( "SELECT * FROM `logs`", $aLogs )
        ->commit();
    
    return $aLogs;
}

function assertEqual( $a, $b ) {
    if( $a != $b ){
        trigger_error("Value are not equal",E_USER_ERROR);
    }
}

function assertTrue( $a ) {
    if( !$a ){
        trigger_error("Value is not true",E_USER_ERROR);
    }
}