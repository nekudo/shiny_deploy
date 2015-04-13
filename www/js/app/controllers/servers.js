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

app.controller('ServersAddController', function ($scope, $rootScope, $location, serversService) {
    $scope.addCustomer = function() {
        var promise = serversService.addServer($scope.server);
        promise.then(function(data) {
            $location.path('/servers');
        }, function(reason) {
            $rootScope.$emit('message-event', reason);
        })
    }
});


// @todo Move to directives js file.
app.directive("alertMsg", ['$rootScope', function ($rootScope) {
    return {
        restrict: "E",
        scope: true,
        template: '{{msg}}', // this string is the html that will be placed inside the <alert-msg></alert-msg> tags.
        link: function ($scope, $element, attrs) {
            var _unregister;
            _unregister = $rootScope.$on('message-event', function (event, message) {
                $scope.msg = message;
            });
            $scope.$on("$destroy", _unregister);
        }
    };
}]);