app.service('serversService', function (ws) {
    this.getServers = function () {
        return ws.sendDataRequest('getServers');
    };

    this.addServer = function (serverData) {
        var requestParams = {
            serverData: serverData
        };
        return ws.sendDataRequest('addServer', requestParams);
    }
});