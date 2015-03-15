jQuery(document).ready(function($) {
    // handles websocket events:
    function onWsEvent(topic, data) {
        console.log(topic, data);
        if (!data.hasOwnProperty('action')) {
            console.log('Invalid WS event');
        }
        var wsActionName = data.action;
        var wsActionData = data.actionData;
        try {
            wsActions[wsActionName](wsActionData);
        } catch (e) {
            console.log(e);
        }
    }

    // Websocket close callback method
    function onWsClose(reason) {
        switch (reason) {
            case ab.CONNECTION_CLOSED:
                console.log("Connection was closed properly.");
                break;
            case ab.CONNECTION_UNREACHABLE:
                console.log("Websocket connection could not be established.");
                break;
            case ab.CONNECTION_UNSUPPORTED:
                console.log("Browser does not support WebSocket.");
                break;
            case ab.CONNECTION_LOST:
            default:
                console.log("Websocket connection lost.");
                break;
        }
    }

    function callWsEvent(action, params) {
        if (typeof params !== 'object') {
            params = {
                clientId: clientId
            };
        } else {
            params.clientId = clientId;
        }
        conn.call(action, params);
    }

    function getRandomString(length)
    {
        var randomString = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        for (var i = 0; i < length; i++ ) {
            randomString += possible.charAt(Math.floor(Math.random() * possible.length));
        }
        return randomString;
    }

    var wsActions = {
        log: function(data) {
            alert(data);
        }
    };

    // connect to websocket server:
    var clientId = getRandomString(14);
    console.log('client id: '+ clientId);
    var conn = new ab.Session(
        'ws://127.0.0.1:8090',
        function() {
            console.log('Websocket connection established.');
            conn.subscribe(
                clientId,
                function(topic, data) {
                    onWsEvent(topic, data);
                }
            );
        },
        function(reason) {
            onWsClose(reason);
        },
        {
            'skipSubprotocolCheck': true
        }
    );

    $('#test-event').click(function(event) {
        event.preventDefault();
        var actionData = {
            param1: 42
        };
        callWsEvent('dummy', actionData);
    })
});