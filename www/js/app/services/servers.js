app.service('serversService', function (ws) {
    this.getServers = function () {
        return ws.sendCallbackRequest('getServers');
    };
});