app.controller('ServersController', function ($scope, serversService) {


    $scope.getServers = function() {
        return serversService.getServers();
    };
});