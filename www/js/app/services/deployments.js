app.service('deploymentsService', ['ws', function (ws) {
    this.getServers = function() {
        return ws.sendDataRequest('getServers');
    };

    this.getRepositories = function() {
        return ws.sendDataRequest('getRepositories');
    };
}]);