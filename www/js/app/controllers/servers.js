app.controller('ServersController', function ($scope, serversService) {
    var servers = null;

    loadServers();

    /**
     * Requests server list from project backend.
     */
    function loadServers() {
        $scope.isLoading = true;
        var promise = serversService.getServers();
        promise.then(function(data) {
            servers = data;
            $scope.isLoading = false;
        }, function(reason) {
            console.log('Error fetching servers: ' + reason);
            $scope.isLoading = false;
        });
    }

    /**
     * Returns list of servers.
     *
     * @returns {null|Array}
     */
    $scope.getServers = function() {
        return servers;
    };
});

app.controller('ServersAddController', function ($scope, $location, serversService, alertsService) {

    /**
     * Requests add-server action on project backend.
     */
    $scope.addServer = function() {
        var promise = serversService.addServer($scope.server);
        promise.then(function(data) {
            $location.path('/servers');
            alertsService.queueAlert('Server successfully added.', 'success');
        }, function(reason) {
            alertsService.pushAlert(reason, 'warning');
        })
    }
});