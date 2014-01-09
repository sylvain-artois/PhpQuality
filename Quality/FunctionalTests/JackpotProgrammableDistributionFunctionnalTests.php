<?php

/**
 * JackpotProgrammableDistribution test case.
 * @link http://www.phpunit.de/manual/current/en/database.html
 */
class JackpotProgrammableDistributionFunctionalTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * @var JackpotProgrammableDistribution
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
    private $sCampaignId = "1340805973_demo";

    /**
     * @var string
     */
    private $sToday = "2012-06-21";

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


        $this->oD = new JackpotProgrammableDistribution();
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
    public function testChooseProgJackpotThrowsExceptionIfNoCampaignId()
    {
        $this->init();

        $oPrize = new Prize();
        $oPrize->setFromDB((object) array(
                               "id" => "1340805980_voyage-berlin",
                               "id_campaign" => "1340805973_demo",
                               "name" => "Voyage a Berlin",
                               "is_jackpot" => 0,
                               "is_booby" => 0,
                               "is_lost" => 0,
                               "prize_nbr" => 2,
                               "alreadyDeal" => 0));

        $this->oD->chooseProgJackpot("", $oPrize);
    }

    /**
     * @expectedException DomainException
     */
    public function testChooseProgJackpotThrowsExceptionIfNoPrizeId()
    {
        $this->init();

        $oPrize = new Prize();
        $oPrize->setFromDB((object) array(
                               "id" => null,
                               "id_campaign" => "1340805973_demo",
                               "name" => "Voyage a Berlin",
                               "is_jackpot" => 0,
                               "is_booby" => 0,
                               "is_lost" => 0,
                               "prize_nbr" => 2,
                               "alreadyDeal" => 0));

        $this->oD->chooseProgJackpot("", $oPrize);
    }

    public function testChooseProgJackpot()
    {
        $this->init();

        $oPrize = new Prize();
        $oPrize->setFromDB((object) array(
                               "id" => "1340805980_voyage-berlin",
                               "id_campaign" => "1340805973_demo",
                               "name" => "Voyage a Berlin",
                               "is_jackpot" => 0,
                               "is_booby" => 0,
                               "is_lost" => 0,
                               "prize_nbr" => 2,
                               "alreadyDeal" => 0));

        $this->assertTrue($this->oD->chooseProgJackpot("1340805973_demo", $oPrize));

        // On verifie en base que le prix a ete mis a jour
        $oRow = new StdClass();
        $this->oPDO->getRow("prize",
                            sprintf("id='%s'", "1340805980_voyage-berlin"),
                            $oRow);
        $aExpectedRow = array(
                               "id" => "1340805980_voyage-berlin",
                               "id_campaign" => "1340805973_demo",
                               "name" => "Voyage a Berlin",
                               "is_jackpot" => '1',
                               "is_booby" => '0',
                               "is_lost" => '0',
                               "prize_nbr" => '2',
                               "allreadyDeal" => '0');
        $this->assertEquals($aExpectedRow, (array) $oRow);

        // On verifie egalement que le jackpot a ete mis a jour
        $oExpectedJackpot = $this->oC->jackpotDesc;
        $oExpectedJackpot->prize_id = "1340805980_voyage-berlin";

        $this->oPDO->getRow("campaign",
                            sprintf("id='%s'", "1340805973_demo"),
                            $oRow);
        $this->assertEquals(serialize($oExpectedJackpot), $oRow->jackpotDesc);
    }

    public function testGetJackpot() {
        $this->init();

        // On va tout d'abord definir le jackpot pour notre campagne
        $oPrize = new Prize();
        $oPrize->setFromDB((object) array(
                               "id" => "1340805980_voyage-berlin",
                               "id_campaign" => "1340805973_demo",
                               "name" => "Voyage a Berlin",
                               "is_jackpot" => 0,
                               "is_booby" => 0,
                               "is_lost" => 0,
                               "prize_nbr" => 2,
                               "alreadyDeal" => 0));
        $this->assertTrue($this->oD->chooseProgJackpot("1340805973_demo", $oPrize));

        // Maintenant, on va declencher le jackpot pour dans 30 minutes
        $oDateTimeInjector = new DateTimeInjector("2012-06-21 11:02:42");
        $this->oD->setDateTimeInjector($oDateTimeInjector);
        $this->oD->setNow("2012-06-21 11:02:42");
        $initialRowcount = $this->getConnection()->getRowCount( "winningtime_computed" );
        $this->assertTrue($this->oD->addInstantWinForProgJackpot("1340805973_demo"));

        // On verifie en base de donnees qu'un instant gagnant a bien été inséré
        $this->assertEquals($initialRowcount + 1,
                            $this->getConnection()->getRowCount( "winningtime_computed" ));

        // On teste que le dernier enregistrement contient bien les bonnes infos
        $this->oPDO->start()
            ->query("SELECT * FROM  `winningtime_computed` ORDER BY id DESC LIMIT 1",
                    $aWinningTimes)
            ->commit();
        $this->assertEquals("1340805980_voyage-berlin",
                            $aWinningTimes[0]['id_prize']);
        $this->assertEquals("2012-06-21 11:32:42",
                            $aWinningTimes[0]['_datetime']);
        $this->assertEquals("1",$aWinningTimes[0]['isValid']);
        $this->assertEquals("1",$aWinningTimes[0]['isJackpot']);
        $this->assertEquals("0",$aWinningTimes[0]['isFixedPrize']);
    }

}

