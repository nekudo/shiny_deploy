function WsEventHandler() {
    // clients uuid
    this.clientId = null;

    // autobahn session
    this.conn = null;

    // registered websocket events
    this.wsEvents = [];

    /**
     * Sets clients uuid.
     *
     * @param {number} clientId
     */
    this.setClientId = function(clientId) {
        this.clientId = clientId;
    };

    /**
     * Sets websocket connection.
     *
     * @param {ab.Session} conn
     */
    this.setConnection = function(conn) {
        this.conn = conn;
    };

    /**
     * Triggers a websocket event if registered.
     *
     * @param {string} clientId
     * @param {object} data
     * @returns {boolean}
     */
    this.onEvent = function (clientId, data) {
        if (!data.hasOwnProperty('wsEventName')) {
            console.log('Invalid onEvent request. No event name given.');
            return false;
        }
        try {
            if (this.wsEvents.hasOwnProperty(data.wsEventName)) {
                return this.wsEvents[data.wsEventName](data.wsEventParams);
            } else {
                console.log('Warning: Requested event is unknown. (' + wsEventName + ')');
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
    this.callEvent = function (action, params) {
        if (typeof params !== 'object') {
            params = {
                clientId: this.clientId
            };
        } else {
            params.clientId = this.clientId;
        }
        this.conn.call(action, params);
    };

    /**
     * Callback method triggered on websocket close event.
     *
     * @param {number} reason
     */
    this.onClose = function (reason) {
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
     * Registers a new websocket event.
     *
     * @param {string} wsEventName
     * @param {function} callback
     * @returns {boolean}
     */
    this.registerWsEvent = function(wsEventName, callback) {
        this.wsEvents[wsEventName] = callback;
        return true;
    }
}
