app.controller('DeploymentsController', ['$scope', 'deploymentsService', 'alertsService',
    function ($scope, deploymentsService, alertsService) {
        var deployments = null;

        init();

        /**
         * Loads data required for deployments list view.
         */
        function init() {
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
        };
    }
]);

app.controller('DeploymentsAddController', ['$scope', '$location', 'deploymentsService', 'alertsService',
    function ($scope, $location, deploymentsService, alertsService) {
        $scope.isAdd = true;
        var servers = null;
        var repositories = null;

        init();

        /**
         * Loads data required for add deployment form.
         */
        function init() {
            // load servers:
            var getServersPromise = deploymentsService.getServers();
            getServersPromise.then(function(data) {
                servers = data;
            }, function(reason) {
                console.log('Error fetching servers: ' + reason);
            });

            // load repositories:
            var getRepositoriesPromise = deploymentsService.getRepositories();
            getRepositoriesPromise.then(function(data) {
                repositories = data;
            }, function(reason) {
                console.log('Error fetching repositories: ' + reason);
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
            var addDeploymentPromise = deploymentsService.addDeployment($scope.deployment);
            addDeploymentPromise.then(function(data) {
                $location.path('/deployments');
                alertsService.queueAlert('Deployment successfully added.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            })
        };
    }
]);

app.controller('DeploymentsEditController', ['$scope', '$location', '$routeParams', 'deploymentsService', 'alertsService',
    function ($scope, $location, $routeParams, deploymentsService, alertsService) {
        $scope.isEdit = true;
        var servers = null;
        var repositories = null;

        init();

        /**
         * Loads data required for edit deployment form.
         */
        function init() {
            // load servers:
            var getServersPromise = deploymentsService.getServers();
            getServersPromise.then(function(data) {
                servers = data;
            }, function(reason) {
                console.log('Error fetching servers: ' + reason);
            });

            // load repositories:
            var getRepositoriesPromise = deploymentsService.getRepositories();
            getRepositoriesPromise.then(function(data) {
                repositories = data;
            }, function(reason) {
                console.log('Error fetching repositories: ' + reason);
            });

            // load deployment:
            var deploymentId = ($routeParams.deploymentId) ? parseInt($routeParams.deploymentId) : 0;
            var getDeploymentDataPromise = deploymentsService.getDeploymentData(deploymentId);
            getDeploymentDataPromise.then(function(data) {
                if (data.hasOwnProperty('repository_id')) {
                    for (var i = repositories.length - 1; i >= 0; i--) {
                        if (repositories[i].id === data.repository_id) {
                            data.repository_id = repositories[i];
                            break;
                        }
                    }
                    for (var j = servers.length - 1; j >= 0; j--) {
                        if (servers[j].id === data.server_id) {
                            data.server_id = servers[j];
                            break;
                        }
                    }
                }
                $scope.deployment = data;
            }, function(reason) {
                $location.path('/deployments');
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
         * Returns list of repositories.
         *
         * @returns {null|Array}
         */
        $scope.getRepositories = function() {
            return repositories;
        };

        /**
         * Updates deployment data.
         */
        $scope.updateDeployment = function() {
            var deploymentData = angular.copy($scope.deployment);
            if ($scope.deployment.hasOwnProperty('repository_id')) {
                deploymentData.repository_id = deploymentData.repository_id.id;
                deploymentData.server_id = deploymentData.server_id.id;
            }
            var updateDeploymentPromise = deploymentsService.updateDeployment(deploymentData);
            updateDeploymentPromise.then(function (data) {
                alertsService.pushAlert('Deployment successfully updated.', 'success');
            }, function (reason) {
                alertsService.pushAlert(reason, 'warning');
            })
        };
    }
]);