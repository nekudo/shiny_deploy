jQuery(document).ready(function($) {
    'use strict';

    var helperMisc = new HelperMisc();
    var wsEventHandler = new WsEventHandler();
    var wsEvents = new WsEvents();

    // connect to webserver:
    var clientId = helperMisc.getUuid();
    var conn = new ab.Session(
        'ws://127.0.0.1:8090',
        function() {
            console.log('Websocket connection established.');
            conn.subscribe(clientId, function(topic, data) {
                wsEventHandler.onEvent(topic, data);
            });
        },
        function(reason) {
            wsEventHandler.onClose(reason);
        }
    );
    wsEventHandler.setClientId(clientId);
    wsEventHandler.setConnection(conn);

    // register websocket "log" event:
    wsEventHandler.registerWsEvent('log', function(eventData) {
        var message = eventData.text;
        var type = (typeof eventData.type !== 'undefined') ? eventData.type : 'default';
        var source = (typeof eventData.source !== 'undefined') ? eventData.source : '';
        var time = (typeof eventData.time !== 'undefined') ? eventData.time : helperMisc.getTimeString();
        wsEvents.log(message, type, source, time);
    });

    // Init nanoScroller:
    $('.nano').nanoScroller();

    // Handle deploy clicks:
    $('#deploy-button').click(function(event) {
        event.preventDefault();
        var wsEventData = {
            idSource: $('#deploy-source').val(),
            idTarget: $('#deploy-target').val()
        };
        wsEventHandler.callEvent('startDeploy', wsEventData);
    });
});
