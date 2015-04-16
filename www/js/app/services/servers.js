app.service('serversService', function (ws) {
    /**
     * Fetches list of servers.
     *
     * @returns {a.promise|promise|d.promise|fd.g.promise}
     */
    this.getServers = function () {
        return ws.sendDataRequest('getServers');
    };

    /**
     * Adds new server.
     *
     * @param serverData
     * @returns {a.promise|promise|d.promise|fd.g.promise}
     */
    this.addServer = function (serverData) {
        var requestParams = {
            serverData: serverData
        };
        return ws.sendDataRequest('addServer', requestParams);
    }
});