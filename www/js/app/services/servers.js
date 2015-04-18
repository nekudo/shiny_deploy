app.service('serversService', function (ws) {
    /**
     * Fetches list of servers.
     *
     * @returns {promise}
     */
    this.getServers = function () {
        return ws.sendDataRequest('getServers');
    };

    /**
     * Adds new server.
     *
     * @param {Array} serverData
     * @returns {promise}
     */
    this.addServer = function (serverData) {
        var requestParams = {
            serverData: serverData
        };
        return ws.sendDataRequest('addServer', requestParams);
    };

    /**
     * Removes a server from database.
     *
     * @param {number} serverId
     * @returns {promise}
     */
    this.deleteServer = function (serverId) {
        var requestParams = {
            serverId: serverId
        };
        return ws.sendDataRequest('deleteServer', requestParams);
    };

    /**
     * Updates existing server.
     *
     * @param {Array} serverData
     * @returns {promise}
     */
    this.updateServer = function (serverData) {
        var requestParams = {
            serverData: serverData
        };
        return ws.sendDataRequest('updateServer', requestParams);
    };

    /**
     * Fetches data for a server.
     *
     * @param {number} serverId
     * @returns {bool|promise}
     */
    this.getServerData = function(serverId) {
        if (serverId === 0) {
            return false;
        }
        var requestParams = {
            serverId: serverId
        };
        return ws.sendDataRequest('getServerData', requestParams);
    };
});