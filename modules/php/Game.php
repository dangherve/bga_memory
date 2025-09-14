<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * memory implementation : © <Herve Dang> <dang.herve@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */
declare(strict_types=1);

namespace Bga\Games\memory;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

class Game extends \Table
{
    private static array $CARD_TYPES;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        require "material.inc.php";

        $this->cheat=1;

        parent::__construct();

        $this->initGameStateLabels([
            "numberCards" => 10,
            "pairsFound" => 11,
            "firstCard" => 12,
            "secondCard" => 13,
            "lastPlayer" => 14,

            //gameoption
            "gameCard" => 100,
            "gameType" => 101,

            "numberCardsTarot" => 110,
            "numberCardsTarotAssociate" => 111,

        ]);


        $this->cards = $this->getNew("module.common.deck");
        $this->cards->init("card");

    }



    public function defaultColor() : string{

        switch ($this->getGameStateValue("gameCard")){
            //tarot game
            case 1:
                $defaultColor="5";
                break;
            //Canada flag
            case 2:
                $defaultColor="3";
                break;
            //Europe Flag
            case 3:
                $defaultColor="3";
                break;
            //shokoba
            case 4:
                $defaultColor="2";
                break;
        }

        return $defaultColor;
    }
    public function defaultValue() : string{
        switch ($this->getGameStateValue("gameCard")){
            //tarot game
            case 1:
                $defaultValue="8";
                break;
            //Canada flag
            case 2:
                $defaultValue="0";
                break;
            //Europe Flag
            case 3:
                $defaultValue="0";
                break;
            //shokoba
            case 4:
                $defaultValue="0";
                break;
        }

        return $defaultValue;
    }

    function _checkActivePlayer()
    {
        if ($this->getActivePlayerId() !== $this->getCurrentPlayerId()) {
            throw new \BgaUserException(self::_("Unexpected Error: you are not the active player"), true);
        }
    }


    /**
     * Player choose a card
     *
     */
    public function actPlayCard(int $card_id): void
    {
        $this->checkAction('actPlayCard');
        $player_id = $this->getActivePlayerId();
        $this->_checkActivePlayer();

        $card = $this->getCollectionFromDb('
            SELECT card_id as id, card_type as type, card_type_arg as type_arg,
            card_visible as visible,
            card_location as location, card_location_arg as location_arg
            FROM card WHERE card_id = "'.$card_id.'"');


        if($card[key($card)]['visible']){
            self::notifyPlayer($player_id, 'visible card', '', array());
        }else{

            static::DbQuery("UPDATE card set card_visible = TRUE WHERE card_id = ".$card_id);
            $card[key($card)]['visible']=TRUE;

            if($this->getGameStateValue("firstCard") == -1 ){
                $this->setGameStateInitialValue("firstCard", $card_id);

                $this->notifyAllPlayers(
                'revealCard',clienttranslate('${player_name} reveal the first card '),
                [
                    'player_name' => $this->getActivePlayerName(),
                    'card' => $card,
                ]
                );

            }else{
                $this->setGameStateInitialValue("secondCard", $card_id);

                $this->notifyAllPlayers(
                'revealCard',clienttranslate('${player_name} reveal the second card '),
                [
                    'player_name' => $this->getActivePlayerName(),
                    'card' => $card,
                ]
                );

                $this->gamestate->nextState("checkCard");
            }
        }


    }

    /**
     * Players need to confirm that they saw the wrongfull paired card
     *
     */

    function actConfirm()
    {

        $player_id = $this->getCurrentPlayerId(); // Multiple active state => we must check the player who launched the request
        $this->gamestate->setPlayerNonMultiactive($player_id, '');

    }

    /**
     * Process Card:
     *  - reset uncorrect paired move
     *  - eventually move found card
     */

    function stProcessCard()
    {
        //data keep for debug
        $cards = $this->getCollectionFromDb('
            SELECT card_id as id,
            card_type as type, card_type_arg as type_arg,
            card_visible as visible,
            card_location as location, card_location_arg as location_arg, card_color as color
            FROM card WHERE card_id = "'.$this->getGameStateValue("firstCard").'" OR
            card_id = "'.$this->getGameStateValue("secondCard").'"');

        foreach ($cards as $i => $value) {
            static::DbQuery("UPDATE card set card_visible = FALSE WHERE card_id = ".$cards[$i]['id']);
            $cards[$i]['visible'] = FALSE;


            //hide data when card not visible
            if (!$this->cheat && !$cards[$i]['visible']){
                $cards[$i]['type']=$this->defaultColor();
                $cards[$i]['type_arg']=$this->defaultValue();
            }

        }

        $cardsRemove = $this->getCollectionFromDb('
            SELECT card_id as id, card_location_arg as location_arg
            FROM card WHERE card_found = TRUE and  card_location = "table"');

        static::DbQuery("UPDATE card set card_location = 'Player', card_location_arg = ". $this->getActivePlayerId()." WHERE card_found = TRUE");

        $this->notifyAllPlayers(
        'hideCards',clienttranslate("Hiding card and remove found card"),
        [
            'cards' => $cards,
            'cardsRemove' => $cardsRemove,
        ]
        );

        $this->setGameStateInitialValue("firstCard", -1);
        $this->setGameStateInitialValue("secondCard", -1);
        $this->gamestate->nextState("nextPlayer");
    }


    /**
     * Check Card: test card if they match
     */


    public function checkCardType(int $type1, int $type2)
    {


        $result=false;
        switch ($this->getGameStateValue("gameCard")){
            //tarot game
            case 1:
                $result=
                    (($type1 == 0) && ($type2 == 1)) ||
                    (($type1 == 1) && ($type2 == 0)) ||
                    (($type1 == 2) && ($type2 == 3)) ||
                    (($type1 == 3) && ($type2 == 2));

                break;
            case 2:
            case 3:
                $result=
                    (($type1 == 0) && ($type2 == -1)) ||
                    (($type1 == -1) && ($type2 == 0));

                break;
        }

        return $result;
    }

    public function stCheckCard(): void
    {
        $player_id = $this->getActivePlayerId();

        $card1 = $this->getObjectFromDB('
            SELECT card_type as type, card_type_arg as type_arg
            FROM card WHERE card_id = "'.$this->getGameStateValue("firstCard").'"');

        $card2 = $this->getObjectFromDB('
            SELECT card_type as type, card_type_arg as type_arg
            FROM card WHERE card_id = "'.$this->getGameStateValue("secondCard").'"');

        //basic mode
        if ((($card1["type"] == $card2["type"]) &&
            ($card1["type_arg"] == $card2["type_arg"])) ||
            ($this->getGameStateValue("gameType") ==1 &&
            $this->checkCardType((int)$card1["type"], (int)$card2["type"]) &&
            ($card1["type_arg"] == $card2["type_arg"]))
            ){
            $color=sprintf('#%06X', mt_rand(0, 0xFFFFFF));
            static::DbQuery("UPDATE card set card_found = TRUE, card_color='".$color."'
                WHERE card_id = ".$this->getGameStateValue("firstCard"). " OR
                card_id =".$this->getGameStateValue("secondCard"));

            $pairsFound=$this->getGameStateValue("pairsFound")+1;

            $card = $this->getCollectionFromDb('
                SELECT card_id as id, card_type as type, card_type_arg as type_arg,
                card_location as location, card_location_arg as location_arg, card_color as color
                FROM card WHERE card_found = TRUE and card_location = "table"');

            $this->notifyAllPlayers('pairsFound',clienttranslate(""),array(
                'card' => $card,
                'color' => $color,
            ));

            $this->setGameStateInitialValue("firstCard", -1);
            $this->setGameStateInitialValue("secondCard", -1);

            $this->setGameStateValue("pairsFound",$pairsFound);

            $sql = "UPDATE player
                    SET player_score = player_score +1 WHERE player_id=".$player_id;
            $this->DbQuery( $sql );

            //update score
            $newScores = $this->getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
            $this->notifyAllPlayers( "newScores", clienttranslate('${player_name} found a pair'), array(
                'player_name' => $this->getActivePlayerName(),
                "scores" => $newScores
            ));

            if( $pairsFound == $this->getGameStateValue("numberCards")){
                $this->notifyAllPlayers('end',clienttranslate("all pair found"),[]);
                $this->gamestate->nextState("endGame");
            }else{
                $this->notifyAllPlayers('replay',clienttranslate("all pair card found replay"),[]);
                $this->gamestate->nextState("playerTurn");
            }

        }else{
            $this->gamestate->nextState("cardReveal");
        }

    }

    /**
     * Ask all player to confirm that see the card
     */

    public function stCardReveal(): void
    {

        $this->notifyAllPlayers('MULTI',clienttranslate(""),[]);
        // All players must now confirm they had the time to see the Kitty
        $this->gamestate->setAllPlayersMultiactive();
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return intpa
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {

        $cardFound=$this->getUniqueValueFromDB("SELECT COUNT(*) FROM card WHERE card_found = TRUE");

        $progression = ($cardFound/2)/$this->getGameStateValue("numberCards")*100;

        return $progression;
    }

    /**
     * Game state action, example content.
     *
     * The action method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
     */

    public function stNextPlayer(): void {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Give some extra time to the active player when he completed an action
        $this->giveExtraTime($player_id);

        $this->activeNextPlayer();


        $player_data = self::loadPlayersBasicInfos();

        if ($player_data[$player_id]['player_no'] == self::getGameStateValue('lastPlayer')){
            $this->incStat(1,"turns_number");
        }

        // Go to another gamestate
        // Here, we would detect if the game is over, and in this case use "endGame" transition instead
        $this->gamestate->nextState("playerTurn");
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
       if ($from_version <= 2508141622)
       {
            // ! important ! Use DBPREFIX_<table_name> for all tables
          $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM card LIKE 'card_found'");
          if (empty($result))
            $this->applyDbUpgradeToAllDB( "ALTER TABLE DBPREFIX_card  ADD `card_found` tinyint(1)  unsigned NOT NULL DEFAULT '0'" );

          $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM card LIKE 'card_color'");
          if (empty($result))
            $this->applyDbUpgradeToAllDB( "ALTER TABLE DBPREFIX_card ADD `card_color` varchar(10) NOT NULL DEFAULT \"none\" ");

          $result = self::getUniqueValueFromDB("SELECT * FROM global WHERE global_id=100");
          if (empty($result))
            $this->applyDbUpgradeToAllDB( "insert into DBPREFIX_global value (100,6)" );
        }

       if ($from_version <= 2508171946)
       {


          $result = self::getUniqueValueFromDB("SELECT * FROM global WHERE global_id=10");
          if (empty($result))
            $result = self::getUniqueValueFromDB("SELECT * FROM global WHERE global_id=100");
            $this->applyDbUpgradeToAllDB( "insert into DBPREFIX_global value (10,".$result.")" );


       }

//       if ($from_version <= 1405061421)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas()
    {

        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
        );


        //getCardsInLocation seem not take added element
        $cards = $this->getCollectionFromDb('
            SELECT card_id as id,
            card_type as type, card_type_arg as type_arg,
            card_location as location, card_location_arg as location_arg,
            card_visible as visible, card_color as color
            FROM card WHERE card_location = "table"
            ORDER BY card_location_arg ASC');

        $result['gameCard'] = $this->getGameStateValue("gameCard");
        $result['gameType'] = $this->getGameStateValue("gameType");

        $result["canada_Flag"] = $this->canada_Flag;
        $result["europe_Flag"] = $this->europe_Flag;

        if(!$this->cheat){

            //hide data when card not visib
            foreach ($cards as $i => $value) {
                if (!$cards[$i]['visible']){
                    $cards[$i]['type']=$this->defaultColor();
                    $cards[$i]['type_arg']=$this->defaultValue();
                }

            }

        }
        $result['table'] = $cards;

        return $result;
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.

        $this->setGameStateInitialValue("pairsFound", 0);

        $this->setGameStateInitialValue("firstCard", -1);
        $this->setGameStateInitialValue("secondCard", -1);

        $this->setGameStateInitialValue( 'lastPlayer', (int)$this->getUniqueValueFromDB("SELECT MAX(player_no) FROM player") );

        switch ($this->getGameStateValue("gameCard")){
            //tarot game
            case 1:
                if($this->getGameStateValue("gameType") == 2){
                    $this->setGameStateInitialValue("numberCards", $this->getGameStateValue("numberCardsTarot"));
                }else{
                    $this->setGameStateInitialValue("numberCards", $this->getGameStateValue("numberCardsTarotAssociate"));
                }
                break;
            //Canada flag
            case 2:
                $this->setGameStateInitialValue("numberCards", 13);
                break;
            //Europe Flag
            case 3:
                $this->setGameStateInitialValue("numberCards", 27);
                break;
            //shokoba
            case 4:
                $this->setGameStateInitialValue("numberCards", 20);
                break;
        }



        $this->initializeDeck();
        $this->initializeGameTable();


        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
    }

    // Create and shuffle deck
    protected function initializeDeck()
    {

        $cards = [];
        if($this->getGameStateValue("gameType") == 2){
            $nbCards=$this->getGameStateValue("numberCards");
        }else{
            $nbCards=$this->getGameStateValue("numberCards")*2;

        }


        for ($value = 0; $value < $nbCards; $value++) {

        switch ($this->getGameStateValue("gameCard")){
            //tarot game
            case 1:
                if($value<14){
                    $type=0;
                    $type_arg=$value;
                }elseif($value<28){
                    $type=1;
                    $type_arg=$value-14;
                }elseif($value<42){
                    $type=2;
                    $type_arg=$value-28;
                }elseif($value<56){
                    $type=3;
                    $type_arg=$value-42;
                }elseif($value<70){
                    $type=4;
                    $type_arg=$value-56;
                }else{
                    $type=5;
                    $type_arg=$value-70;
                }
                break;
            case 2:
                if($value<13){
                    $type=0;
                    $type_arg=$value;
                }else{
                    $type=-1;
                    $type_arg=$value-13;
                }
                break;
            case 3:
                if($value<27){
                    $type=0;
                    $type_arg=$value;
                }else{
                    $type=-1;
                    $type_arg=$value-27;
                }
                break;
            case 4:
                if($value<10){
                    $type=0;
                    $type_arg=$value;
                }else{
                    $type=1;
                    $type_arg=$value-10;
                }
                break;
        }

            $cards[] = ['type' => $type, 'type_arg' => $type_arg, 'nbr' => $this->getGameStateValue("gameType")];
        }

        $this->cards->createCards($cards, 'deck');
        $this->cards->shuffle('deck');
    }

    /**
     *
     * Debug function
     *
     * @throws BgaUserException
     */
    public function debug_CARDS(): void
    {


        switch ($this->getGameStateValue("gameCard")){
            //tarot game
            case 1:
                if($this->getGameStateValue("gameType") == 2){
                    $this->setGameStateInitialValue("numberCards", $this->getGameStateValue("numberCardsTarot"));
                }else{
                    $this->setGameStateInitialValue("numberCards", $this->getGameStateValue("numberCardsTarotAssociate"));
                }
                break;
            case 2:
                if($this->getGameStateValue("gameType") == 2){
                    $this->setGameStateInitialValue("numberCards", 13);
                }else{
                    $this->setGameStateInitialValue("numberCards", 26);
                }
                break;
            case 2:
                if($this->getGameStateValue("gameType") == 23){
                    $this->setGameStateInitialValue("numberCards", 27);
                }else{
                    $this->setGameStateInitialValue("numberCards", 54);
                }
                break;
            default:
                $this->setGameStateInitialValue("numberCards", 4);
                break;
        }

        $sql = "TRUNCATE card ";
            $this->DbQuery( $sql );

        $this->initializeDeck();

        $this->initializeGameTable();
    }


    public function debug_initializeGameTable(): void
    {
        $this->initializeGameTable();
    }

    public function debug_initializeDeck(): void
    {
        $sql = "TRUNCATE card ";
            $this->DbQuery( $sql );

        $this->initializeDeck();
    }




    protected function initializeGameTable(): void
    {

        $nbCards=$this->getGameStateValue("numberCards")*2;


$this->dump( "var nbCards ",$nbCards );
        for ($i=0;$i<$nbCards;$i++){

            $this->cards->pickCardForLocation('deck', 'table',$i);
            $this->dump( "var i ",$i );
        }
    }



    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default:
                {
                    $this->gamestate->nextState("processCard");
                    break;
                }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }
}
