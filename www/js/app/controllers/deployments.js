app.controller('DeploymentsController', ['$scope', 'deploymentsService', 'alertsService',
    function ($scope, deploymentsService, alertsService) {
        var deployments = null;

        loadDeployments();

        /**
         * Requests server list from project backend.
         */
        function loadDeployments() {
            var promise = deploymentsService.getDeployments();
            promise.then(function(data) {
                deployments = data;
            }, function(reason) {
                console.log('Error fetching deployments: ' + reason);
            });
        }

        /**
         * Returns list of deployments.
         *
         * @returns {null|Array}
         */
        $scope.getDeployments = function() {
            return deployments;
        };

        /**
         * Removes a deployment.
         *
         * @param {number} deploymentId
         */
        $scope.deleteDeployment = function(deploymentId) {
            var promise = deploymentsService.deleteDeployment(deploymentId);
            promise.then(function(data) {
                for (var i = deployments.length - 1; i >= 0; i--) {
                    if (deployments[i].id === deploymentId) {
                        deployments.splice(i, 1);
                        break;
                    }
                }
                alertsService.pushAlert('Deployment successfully deleted.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }
    }
]);

app.controller('DeploymentsAddController', ['$scope', '$location', 'deploymentsService', 'alertsService',
    function ($scope, $location, deploymentsService, alertsService) {
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
         * Requests add-deployment action on project backend.
         */
        $scope.addDeployment = function() {
            var deploymentData = $scope.deployment;
            if ($scope.deployment.hasOwnProperty('repository_id')) {
                deploymentData.repository_id = deploymentData.repository_id.id;
                deploymentData.server_id = deploymentData.server_id.id;
            }
            var promise = deploymentsService.addDeployment($scope.deployment);
            promise.then(function(data) {
                $location.path('/deployments');
                alertsService.queueAlert('Deployment successfully added.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            })
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

app.controller('DeploymentsEditController', ['$scope', '$location', '$routeParams', 'deploymentsService', 'alertsService',
    function ($scope, $location, $routeParams, deploymentsService, alertsService) {
        $scope.isEdit = true;

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

        // Fetch server data:
        var deploymentId = ($routeParams.deploymentId) ? parseInt($routeParams.deploymentId) : 0;
        var promise = deploymentsService.getDeploymentData(deploymentId);
        promise.then(function(data) {
            if (data.hasOwnProperty('repository_id')) {
                for (var i = repositories.length - 1; i >= 0; i--) {
                    if (repositories[i].id === data.repository_id) {
                        data.repository_id = repositories[i];
                        break;
                    }
                }
                for (var i = servers.length - 1; i >= 0; i--) {
                    if (servers[i].id === data.server_id) {
                        data.server_id = servers[i];
                        break;
                    }
                }
            }
            $scope.deployment = data;
        }, function(reason) {
            $location.path('/deployments');
        });

        $scope.updateDeployment = function() {
            var deploymentData = $scope.deployment;
            if ($scope.deployment.hasOwnProperty('repository_id')) {
                deploymentData.repository_id = deploymentData.repository_id.id;
                deploymentData.server_id = deploymentData.server_id.id;
            }
            var promise = deploymentsService.updateDeployment($scope.deployment);
            promise.then(function (data) {
                alertsService.pushAlert('Deployment successfully updated.', 'success');
            }, function (reason) {
                alertsService.pushAlert(reason, 'warning');
            })
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