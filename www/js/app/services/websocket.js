angular.module('ws', []).provider('ws', wsProvider);

function wsProvider() {
    var provider = this;

    /**
     * Configuration.
     */

    this.config = {
        url: 'ws://127.0.0.1:8090'
    };

    /**
     * Expose ws service.
     */

    this.$get = ['$rootScope', '$q', wsService];

    /**
     * Create a new wsService.
     */

    function wsService($rootScope, $q) {

        var ws = {};

        var currentCallbackId = 0;

        ws.wsEvents = [];
        ws.callbacks = [];
        ws.clientId = null;

        /**
         * Connect the WebSocket.
         *
         * @param {object} config
         */

        ws.connect = function (config) {
            config = config || {};
            if (config.url) provider.config.url = config.url;
            ws.clientId = ws.getUuid();
            ws.conn = new ab.Session(
                provider.config.url,
                function() {
                    console.log('Websocket connection established.');
                    ws.conn.subscribe(ws.clientId, function(topic, data) {
                        ws.onMessage(topic, data);
                    });
                },
                function(reason) {
                    ws.onClose(reason);
                }
            );
        };

        /**
         * Callback method triggered on websocket close event.
         *
         * @param {number} reason
         */
        ws.onClose = function (reason) {
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
        };

        /**
         * Handles incoming messages from websocket server.
         *
         * @param {string} clientId
         * @param {object} data
         * @returns {boolean}
         */
        ws.onMessage = function (clientId, data) {
            /*
            if (!data.hasOwnProperty('wsEventName')) {
                console.log('Invalid onEvent request. No event name given.');
                return false;
            }
            */
            try {
                if (ws.wsEvents.hasOwnProperty(data.wsEventName)) {
                    return ws.wsEvents[data.wsEventName](data.wsEventParams);
                } else {

                    if(ws.callbacks.hasOwnProperty(data.callback_id)) {
                        console.log(ws.callbacks[data.callback_id]);
                        $rootScope.$apply(ws.callbacks[data.callback_id].cb.resolve(data.data));
                        delete ws.callbacks[data.callback_id];
                    } else {
                        console.log('Warning: Requested event is unknown. (' + wsEventName + ')');
                    }
                }
            } catch (e) {
                console.log(e);
            }
            return false;
        };





        /**
         * Requests an event on projects php backend.
         *
         * @param {string} action
         * @param {object} params
         */
        ws.sendTriggerRequest = function (action, params) {
            if (typeof params !== 'object') {
                params = {
                    clientId: this.clientId
                };
            } else {
                params.clientId = this.clientId;
            }
            ws.conn.call(action, params);
        };


        ws.sendCallbackRequest = function (action, params) {
            if (typeof params !== 'object') {
                params = {};
            }
            params.clientId = ws.clientId;
            params.callbackId = ws.getCallbackId();

            var defer = $q.defer();
            ws.callbacks[params.callbackId] = {
                time: new Date(),
                cb:defer
            };
            console.log('Sending callback request', action, params);
            ws.conn.call(action, params);
            return defer.promise;
        };


        /**
         * Registers a new websocket event.
         *
         * @param {string} wsEventName
         * @param {function} callback
         * @returns {boolean}
         */
        ws.registerWsEvent = function(wsEventName, callback) {
            ws.wsEvents[wsEventName] = callback;
            return true;
        };




        // This creates a new callback ID for a request
        ws.getCallbackId = function() {
            currentCallbackId += 1;
            if(currentCallbackId > 10000) {
                currentCallbackId = 0;
            }
            return currentCallbackId;
        };







        /**
         * Generates a unique (random) user-id.
         *
         * @returns {string}
         */
        ws.getUuid = function () {
            var uuid = sessionStorage.getItem('uuid');
            if (uuid !== null) {
                return uuid;
            }
            uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
                return v.toString(16);
            });
            sessionStorage.setItem('uuid', uuid);
            return uuid;
        };

        return ws;
    }

    /**
     * Define URL.
     *
     * @param {string} url
     * @returns {wsProvider}
     */

    this.setUrl = function setOptions(url) {
        this.config.url = url;
        return this;
    };
}