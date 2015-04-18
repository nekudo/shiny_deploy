app.controller('ServersController', function ($scope, serversService, alertsService) {
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

    /**
     * Removes a server.
     *
     * @param {number} serverId
     */
    $scope.deleteServer = function(serverId) {
        var promise = serversService.deleteServer(serverId);
        promise.then(function(data) {
            for (var i = servers.length - 1; i >= 0; i--) {
                if (servers[i].id === serverId) {
                    servers.splice(i, 1);
                    break;
                }
            }
            alertsService.pushAlert('Server successfully deleted.', 'success');
        }, function(reason) {
            alertsService.pushAlert(reason, 'warning');
        });
    }
});

app.controller('ServersAddController', function ($scope, $location, serversService, alertsService) {
    $scope.isAdd = true;

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

app.controller('ServersEditController', function ($scope, $location, $routeParams, serversService, alertsService) {
    $scope.isEdit = true;

    // Fetch server data:
    var serverId = ($routeParams.serverId) ? parseInt($routeParams.serverId) : 0;
    var promise = serversService.getServerData(serverId);
    promise.then(function(data) {
        if (data.hasOwnProperty('port')) {
            data.port = parseInt(data.port);
        }
        $scope.server = data;
    }, function(reason) {
        $location.path('/servers');
    });

    $scope.updateServer = function() {
        var promise = serversService.updateServer($scope.server);
        promise.then(function(data) {
            alertsService.pushAlert('Server successfully updated.', 'success');
        }, function(reason) {
            alertsService.pushAlert(reason, 'warning');
        })
    }
});