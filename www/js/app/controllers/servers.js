app.controller('ServersController', ['$scope', 'serversService', 'alertsService',
    function ($scope, serversService, alertsService) {
        var servers = null;

        init();

        /**
         * Load data required for servers index view.
         */
        function init() {
            var getServersPromise = serversService.getServers();
            getServersPromise.then(function(data) {
                servers = data;
            }, function(reason) {
                console.log('Error fetching servers: ' + reason);
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
            var deleteServerPromise = serversService.deleteServer(serverId);
            deleteServerPromise.then(function(data) {
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
    }
]);

app.controller('ServersAddController', ['$scope', '$location', 'serversService', 'alertsService',
    function ($scope, $location, serversService, alertsService) {
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
    }
]);

app.controller('ServersEditController', ['$scope', '$location', '$routeParams', 'serversService', 'alertsService',
    function ($scope, $location, $routeParams, serversService, alertsService) {
        $scope.isEdit = true;

        init();

        /**
         * Loads data required for edit server view.
         */
        function init() {
            // load server data:
            var serverId = ($routeParams.serverId) ? parseInt($routeParams.serverId) : 0;
            var getServerDataPromise = serversService.getServerData(serverId);
            getServerDataPromise.then(function(data) {
                if (data.hasOwnProperty('port')) {
                    data.port = parseInt(data.port);
                }
                $scope.server = data;
            }, function(reason) {
                $location.path('/servers');
            });
        }

        /**
         * Updates a server.
         */
        $scope.updateServer = function() {
            var promise = serversService.updateServer($scope.server);
            promise.then(function (data) {
                alertsService.pushAlert('Server successfully updated.', 'success');
            }, function (reason) {
                alertsService.pushAlert(reason, 'warning');
            })
        }
    }
]);