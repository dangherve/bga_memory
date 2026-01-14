/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * memory implementation : © <Herve Dang> <dang.herve@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * memory.js
 *
 * memory user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

let index =0
let maxGroup=0
function showSlides (n) {
    elements=document.getElementsByClassName('groupe'+index)
    for(i=0;i<elements.length;i++){
        elements[i].style.display='none'
    }
    index=((index+n)%(maxGroup)+(maxGroup))%(maxGroup)
    elements=document.getElementsByClassName('groupe'+index)
    for(i=0;i<elements.length;i++){
        elements[i].style.display='flex'
    }

}

window.autoGrowText = function (el, minSize = 10, maxSize = 30) {
    let size = minSize;
    el.style.fontSize = size + 'px';

    while ( el.scrollHeight <= el.clientHeight &&
            el.scrollWidth <= el.clientWidth &&
            size < maxSize) {
        size++;
        el.style.fontSize = size + 'px';
    }

    // Step back once (last size caused overflow)
    el.style.fontSize = (size - 1) + 'px';
}


window.showSlides = showSlides; // Terser dead code

const jstpl_card = (tpl) => `
<div id="card_${tpl.card_id}" class="card ${tpl.class} ${tpl.cardGame}" style="top:${tpl.top}px;left:${tpl.left}px;border: ${tpl.borderSize}px solid ${tpl.color};border-radius: 5px; background-position:-${tpl.backx}px -${tpl.backy}px;${tpl.displayOpt}"></div>`

const jstpl_text = (tpl) => `
<div id="card_${tpl.card_id}" class="card ${tpl.class} ${tpl.textClass}" style="top:${tpl.top}px;left:${tpl.left}px;border: ${tpl.borderSize}px solid ${tpl.color};border-radius: 5px;${tpl.displayOpt}">${tpl.text}</div>`

var LOCAL_STORAGE_ZOOM_KEY = 'Memory-zoom';

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "dojo/debounce",
    "ebg/counter",
    "./modules/scrollmapWithZoom"
],
function (dojo, declare) {
    return declare("bgagame.memory", ebg.core.gamegui, {
        constructor: function(){
            console.log('memory constructor');
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            this.tableCard = null;

            this.cheat=0;

            //default value for tarot
            this.cards_per_row = 14;
            this.defautColor = 5;
            this.defautValue = 8;
            this.cardwidth = 70;
            this.cardheight = 129;
            this.cardGame= "tarot"
            this.borderSize= 2
            this.spacing=10
            this.flag=null

            this.stateName = 'playerTurn';
        },

        /*
            setup:

            This method must set up the game user interface according to current game situation specified
            in parameters.

            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)

            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */


        createHelp: function (nbCard,className,maxLine,maxSize){

            size=document.getElementById('maintitlebar_content').scrollWidth-25*2
            linePosition=0
            line=0
            flag=''
            flagCount=1
            first=true
            slideshow=false
            for (i =0;i<nbCard;i++){
                for (j =0;j<2 ;j++){
                    tpl = {};
                    tpl.card_id="flag_help"+(2*i+j);
                    if((maxSize*(flagCount))>size){

                        linePosition=0;
                        line++;
                        if (line >= maxLine){
                            maxGroup++
                            line=0
                            slideshow=true
                        }
                        flagCount=1
                    }

                   if( maxGroup!=0)
                        first=false

                    if (first || !slideshow){
                        tpl.displayOpt=""
                    }else
                        tpl.displayOpt="display:none"

                    tpl.cardGame=className;
                    tpl.color='none'
                    tpl.borderSize=0
                    tpl.backx=100*i;

                    tpl.left=(110*(linePosition))+35;

                    tpl.top=60*(line)+10;
                    linePosition++;
                    tpl.class="groupe"+maxGroup //slideshow

                    if(j == 1){
                        tpl.text=this.flag[i].tr_name
                        tpl.textClass="textCardHelp";
                        flag+=jstpl_text(tpl)
                    }else{
                        tpl.backy=50*j
                        flag+=jstpl_card(tpl)
                    }
                }
                flagCount++
            }

            if (slideshow){
                elements=document.getElementsByClassName('groupe0')
                for(i=0;i<elements.length;i++){
                    elements[i].style.display='flex'
                }
                flagHeight=60*(maxLine)
                console.log("flagHeight:"+flagHeight)
                addOn+='<div id="flag" style="height:'+flagHeight+'px" class="whiteblock">'
                addOn+='<a class="prev" onclick="showSlides(-1)">&#10094;</a>'
                addOn+=flag
                addOn+='<a class="next" onclick="showSlides(1)">&#10095;</a>'
                addOn+='</div>'
            }else{
                console.log("line:"+line)

                maxGroup=line+1
                console.log("maxGroup:"+maxGroup)

                flagHeight=60*(maxGroup)
                console.log("flagHeight:"+flagHeight)

                addOn='<div id="flag" style="height:'+flagHeight+'px" class="whiteblock">'+flag+'</div>'
            }
            maxGroup++
            return addOn;
        },

        initiateTemplate: function(templateType){
            var maxSize;
            var maxLine=3

            maxSize=220;

            //usefull only with 102 ==1
            switch(this.prefs[103].value){
                case "1":
                    maxLine=1
                    break;
                case "2":
                    maxLine=99;
                    break;
                default:
                    break;

            }
            template="map_container "
            addOn=""

            if(this.prefs[102].value == "1") {

                switch (templateType){
                    case "1":
                        //canada
                        addon=this.createHelp(13,"canadaHelp",maxLine,maxSize)
                        break;

                    case "2":
                        //europe
                        addon=this.createHelp(27,"europeHelp",maxLine,maxSize)
                        break;
                }
            }

            document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                ${addOn}
                <div id="map_container" class="${template}">
                    <div id="map_scrollable">
                    </div>
                    <div id="map_surface" >
                    </div>
                    <div id="map_scrollable_oversurface">
                    </div>
                    <a id="movetop" href="#"></a>
                    <a id="moveleft" href="#"></a>
                    <a id="moveright" href="#"></a>
                    <a id="movedown" href="#"></a>
                </div>
            `);
//                        <div id="places_container"></div>

            this.scrollmap = new ebg.scrollmapWithZoom();
//            this.scrollmap = new ebg.scrollmap(); // declare an object (this can also go in constructor)

            this.scrollmap.zoom = 1;
            this.scrollmap.btnsDivOnMap = false;
            this.scrollmap.btnsDivPositionOutsideMap = ebg.scrollmapWithZoom.btnsDivPositionE.Left
            this.scrollmap.scrollmapWithZoom =true;

            // Make map scrollable
            this.scrollmap.create( $('map_container'),$('map_scrollable'),$('map_surface'),$('map_scrollable_oversurface') ); // use ids from template
            this.scrollmap.setupOnScreenArrows( 250 ); // this will hook buttons to onclick functions with 150px scroll step

        },

//        $cards = $this->getCollectionFromDb('
//            SELECT card_id as id, card_type as type, card_type_arg as type_arg,
//            card_location as location, card_location_arg as location_arg
//            FROM card WHERE card_location = "table"');

        addCard: function (elements){
            playable=false;
            for( i in elements ){
                var element = elements[i];
                tpl = {};
                var maxCard=this.prefs[100].value;

                tpl.card_id=element.id;
                tpl.left=(element.location_arg%maxCard)*(this.cardwidth+this.spacing);
                tpl.top=Math.floor(element.location_arg/maxCard)*(this.cardheight+this.spacing);


                tpl.cardGame=this.cardGame;
                tpl.borderSize=this.borderSize;

                tpl.x=(element.location_arg%maxCard);
                tpl.y=Math.floor(element.location_arg/maxCard);

                tpl.color= element.color



                tpl.displayOpt="";

                if(element.visible == 0){
                    tpl.class="playableCard";
                    if (this.cheat ==1){
                        tpl.backx=(element.type_arg*this.cardwidth);
                        tpl.backy=this.cardheight*element.type
                    }else{
                        tpl.backx=(this.defautValue*this.cardwidth);
                        tpl.backy=(this.defautColor*this.cardheight);
                    }
                }else{
                    tpl.class="visibleCard";
                    tpl.backx=(element.type_arg*this.cardwidth);
                    tpl.backy=this.cardheight*element.type

                }



                //get current element
//                element="place_"+tpl.x+"x"+tpl.y
                 elementID="card_"+tpl.card_id
                htmlelement=document.getElementById( elementID)


                //if not exist just add it
                if (htmlelement == null){


                    if((( this.cheat ==1) || (element.visible != 0)) && (element.type == -1)){
                        tpl.textClass="textCard";
                        tpl.text=this.flag[element.type_arg].tr_name
                        elementToAdd = jstpl_text( tpl )

                    }else{
                        elementToAdd = jstpl_card( tpl )
                    }
                    dojo.place(elementToAdd, 'map_scrollable_oversurface' );
                }else{

                    try{
                        htmlelement.parentNode.removeChild(htmlelement)

                        if((element.visible != 0) && (element.type == -1)){
                            tpl.textClass="textCard";
                            tpl.text=this.flag[element.type_arg].tr_name
                            elementToAdd = jstpl_text( tpl )
                        }else{
                            elementToAdd = jstpl_card( tpl )
                        }

                        dojo.place(elementToAdd, 'map_scrollable_oversurface' );
                    }catch(err){
                        if(isDebug)
                            alert("*** check dom ****")
                    }

                }
                if(element.type == -1){
                    autoGrowText(  document.getElementById(`card_${tpl.card_id}`))
                }

                this.disconnectAll();

                this.connectClass('playableCard', 'onclick', 'onAction');
                this.connectClass('visibleCard', 'onclick', 'onConfirm');


            }

        },


        removeCard: function (elements){
            for( i in elements ){
                var element = elements[i];
                var maxCard=this.prefs[100].value;
/*
                tpl = {};
                tpl.x=(element.location_arg%maxCard);
                tpl.y=Math.floor(element.location_arg/maxCard);
                elementToRemove="place_"+tpl.x+"x"+tpl.y
*/
                elementToRemove="card_"+element.id


                //get current element
                htmlelement=document.getElementById(elementToRemove)


                try{
                    htmlelement.parentNode.removeChild(htmlelement)
                }catch(err){
                    if(isDebug)
                        alert("*** check dom ****")
                }

            }

        },

        actConfirm: function (){

            this.bgaPerformAction('actConfirm', {});

    },

        onConfirm: function (evt){
            actionItem = evt.currentTarget//.parentNode.parentNode
            if(this.stateName != 'playerTurn')
                this.bgaPerformAction('actConfirm', {});
    },

        onAction: function (evt){
            actionItem = evt.currentTarget//.parentNode.parentNode


            if(this.stateName == 'playerTurn')
                this.bgaPerformAction('actPlayCard', {
                    card_id: actionItem.id.split("_")[1]});
            else
                this.bgaPerformAction('actConfirm', {});
    },

        setup: function( gamedatas )
        {
            console.log( "Starting game setup 2" );

            var template
            switch(gamedatas['gameCard']){
                //tarot(1) and default value nothing to do as we did not change value
                //canada
                case '2':
                    this.cardGame="canada";
                    this.cards_per_row = 13;
                    this.cardwidth = 200;
                    this.cardheight = 100;
                    this.defautColor = 3;
                    this.defautValue = 0;
                    if(gamedatas['gameType'] == "2"){
                        template="0"
                    }else{
                        template="1"
                    }
                    this.flag=gamedatas['canada_Flag'];


                    break;


                case '3':
                    this.cardGame="europe";
                    this.cards_per_row = 27;
                    this.cardwidth = 200;
                    this.cardheight = 100;
                    this.defautColor = 3;
                    this.defautValue = 0;
                    if(gamedatas['gameType'] == "2"){
                        template="0"
                    }else{
                        template="2"
                    }
                    this.flag=gamedatas['europe_Flag'];
                    break;

                case '4':
                    this.cardGame="shokoba";
                    this.cards_per_row = 10;
                    this.cardwidth = 250;
                    this.cardheight = 250;
                    this.defautColor = 2;
                    this.defautValue = 0;
                    this.borderSize = 10;
                    this.spacing = 30
                    template="0"
                    break;

                default:
                    template="0"
                    break;

            }



            this.initiateTemplate(template);

            this.addCard(gamedatas['table']);


            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },


        // Get card unique identifier based on its color and value
        getCardUniqueId : function(color, value) {
            var id = color * 14 + value

            return id;
        },
        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName, args );

            switch( stateName )
            {

            /* Example:

            case 'myGameState':

                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );

                break;
           */


            case 'dummy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );

            switch( stateName )
            {


            /* Example:

            case 'myGameState':

                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );

                break;
           */


            case 'dummy':
                break;
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName, args );
            this.stateName=stateName



            if( this.isCurrentPlayerActive() )
            {
                switch( stateName )
                {
                 case 'cardReveal':
                    this.statusBar.addActionButton(
                        _('confirm'),
                        () => this.actConfirm(),
                        {
                            color: 'primary',
                            autoclick: this.getGameUserPreference(101) == 1
                        });

                 /*
                    const playableCardsIds = args.playableCardsIds; // returned by the argPlayerTurn

                    // Add test action buttons in the action status bar, simulating a card click:
                    playableCardsIds.forEach(
                        cardId => this.addActionButton(`actPlayCard${cardId}-btn`, _('Play card with id ${card_id}').replace('${card_id}', cardId), () => this.onCardClick(cardId))
                    );

                    this.addActionButton('actPass-btn', _('Pass'), () => this.bgaPerformAction("actPass"), null, null, 'gray');
                    */
                    break;
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*

            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.

        */


        ///////////////////////////////////////////////////
        //// Player's action

        /*

            Here, you are defining methods to handle player's action (ex: results of mouse click on
            game objects).

            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server

        */

        // Example:



        onValidate: function () {
            var tableCard_ids = [];

            if(this.tableCard.getSelectedItems() !=2){
                alert('error')
            }else{
                tableCard_ids.push ( this.tableCard.getSelectedItems()[0].id);
                tableCard_ids.push ( this.tableCard.getSelectedItems()[1].id);
            }

            this.bgaPerformAction('actValidate', {
                tableCard_ids: tableCard_ids.join(',')
            });
        },

        setPlayCardState: function () {
            this.changeMainBar('');
            this.addActionButton('validate_button', _('Validate'), 'onValidate');
            this.addActionButton('cancel_button', _('Cancel'), 'setChooseActionState');
        },


        onTableSelectionChanged: function () {

            var tableCard = this.tableCard;

            if (tableCard.getSelectedItems().length == 0) {
                return;
            }

            if (this.checkAction('actTakeCard')) {

                var items = tableCard.getSelectedItems();
                if (items.length > 0) {
                    this.SelectionType = 'table';
                    this.setPlayCardState();
                    this.tableCard.selectedItemId = items[0].id;
                } else if (this.SelectionType === 'table') {
                    this.setChooseActionState();a
                }
            } else {
                tableCard.unselectAll();
            }
        },
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your memory.game.php file.

        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            // TODO: here, associate your game notifications with local methods

            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            //
            //
            this.bgaSetupPromiseNotifications();
        },


        notif_newScores: async function( args ){
            for( var player_id in args.scores ){
                var newScore = args.scores[ player_id ];
                this.scoreCtrl[ player_id ].toValue( newScore );
            }
        },

        notif_revealCard: function(args) {
            this.addCard(args.card);
        },

        notif_pairsFound: function(args) {
            this.addCard(args.card);
        },

        notif_hideCards: function(args) {
            this.addCard(args.cards);
            this.removeCard(args.cardsRemove);

        },

        // TODO: from this point and below, you can write your game notifications handling methods

        /*
        Example:

        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );

            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call

            // TODO: play the card in the user interface.
        },

        */
   });
});
