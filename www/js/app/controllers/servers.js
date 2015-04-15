app.controller('ServersController', function ($scope, serversService) {
    var servers = null;

    loadServers();

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

    $scope.getServers = function() {
        return servers;
    };
});

app.controller('ServersAddController', function ($scope, $location, serversService, alertsService) {
    $scope.addCustomer = function() {
        var promise = serversService.addServer($scope.server);
        promise.then(function(data) {
            $location.path('/servers');
            alertsService.pushAlert('Server successfully added.', 'success');
        }, function(reason) {
            alertsService.pushAlert(reason, 'warning');
        })
    }
});