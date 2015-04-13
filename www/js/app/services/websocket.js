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
        ws.listeners = [];
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
         * @param {object} message
         * @returns {boolean}
         */
        ws.onMessage = function (clientId, message) {
            try {
                if (message.hasOwnProperty('eventName')) {
                    handleEventMessage(clientId, message);
                } else if (message.hasOwnProperty('callbackId')) {
                    handleDataMessage(clientId, message);
                }
            } catch (e) {
                console.log(e);
            }
            return false;
        };

        /**
         * Handles incoming data massages.
         *
         * @param {string} clientId
         * @param {object} message
         */
        function handleDataMessage(clientId, message) {
            if (!message.hasOwnProperty('type')) {
                console.log('Received invalid data message: Type is missing.');
            }
            var callbackId = message.callbackId;
            var payload = message.payload;
            var type = message.type;
            if(ws.callbacks.hasOwnProperty(callbackId)) {
                if (type === 'success') {
                    $rootScope.$apply(ws.callbacks[callbackId].cb.resolve(payload));
                } else if (type === 'error') {
                    $rootScope.$apply(ws.callbacks[callbackId].cb.reject(message.reason));
                }
                delete ws.callbacks[callbackId];
            } else {
                console.log('Could not resolve callback.');
            }
        }

        /**
         * Handles incoming event massages.
         *
         * @param {string} clientId
         * @param {object} message
         */
        function handleEventMessage(clientId, message) {
            var eventName = message.eventName;
            var payload = message.eventPayload;
            if (ws.listeners.hasOwnProperty(eventName)) {
                return ws.listeners[eventName](payload);
            } else {
                console.log('Could not find any listener for event: ' + eventName);
            }
        }

        /**
         * Tiggers an action on projects php backend.
         *
         * @param {string} actionName
         * @param {object} params
         */
        ws.sendTriggerRequest = function (actionName, params) {
            if (typeof params !== 'object') {
                params = {
                    clientId: this.clientId
                };
            } else {
                params.clientId = this.clientId;
            }
            ws.conn.call(actionName, params);
        };

        /**
         * Requests data from websocket server.
         *
         * @param actionName
         * @param params
         * @returns {a.promise|promise|d.promise|fd.g.promise}
         */
        ws.sendDataRequest = function (actionName, params) {
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
            ws.conn.call(actionName, params);
            return defer.promise;
        };

        /**
         * Adds callback listening for events on websocket connection.
         *
         * @param {string} eventName
         * @param {function} callback
         * @returns {boolean}
         */
        ws.addListener = function(eventName, callback) {
            ws.listeners[eventName] = callback;
            return true;
        };

        /**
         * Generates a callback id.
         *
         * @returns {number}
         */
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
     * Set url of websocket server.
     *
     * @param {string} url
     * @returns {wsProvider}
     */
    this.setUrl = function setOptions(url) {
        this.config.url = url;
        return this;
    };
}