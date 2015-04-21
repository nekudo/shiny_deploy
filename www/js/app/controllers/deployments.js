app.controller('DeploymentsController', ['$scope', 'deploymentsService',
    function ($scope, deploymentsService) {

    }
]);

app.controller('DeploymentsAddController', ['$scope', 'deploymentsService',
    function ($scope, deploymentsService) {
        $scope.isAdd = true;
        var servers = null;
        var repositories = null;

        loadServers();
        loadRepositories();

        /**
         * Returns list of servers.
         *
         * @returns {null|Array}
         */
        $scope.getServers = function() {
            return servers;
        };

        /**
         * Returns list of repositories.
         *
         * @returns {null|Array}
         */
        $scope.getRepositories = function() {
            return repositories;
        };

        /**
         * Requests server list from project backend.
         */
        function loadServers() {
            var promise = deploymentsService.getServers();
            promise.then(function(data) {
                servers = data;
            }, function(reason) {
                console.log('Error fetching servers: ' + reason);
            });
        }

        /**
         * Requests repositories list from project backend.
         */
        function loadRepositories() {
            var promise = deploymentsService.getRepositories();
            promise.then(function(data) {
                repositories = data;
            }, function(reason) {
                console.log('Error fetching repositories: ' + reason);
            });
        }
    }
]);