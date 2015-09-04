(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('DeploymentsController', DeploymentsController);

    DeploymentsController.$inject = ['deploymentsService', 'alertsService'];

    function DeploymentsController(deploymentsService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.deployments = null;

        vm.getDeployments = getDeployments;
        vm.deleteDeployment = deleteDeployment;

        init();

        /**
         * Loads data required for deployments list view.
         */
        function init() {
            var promise = deploymentsService.getDeployments();
            promise.then(function(data) {
                vm.deployments = data;
            }, function(reason) {
                console.log('Error fetching deployments: ' + reason);
            });
        }

        /**
         * Returns list of deployments.
         *
         * @returns {null|Array}
         */
        function getDeployments() {
            return vm.deployments;
        }

        /**
         * Removes a deployment.
         *
         * @param {number} deploymentId
         */
        function deleteDeployment(deploymentId) {
            deploymentsService.deleteDeployment(deploymentId).then(function (data) {
                for (var i = vm.deployments.length - 1; i >= 0; i--) {
                    if (vm.deployments[i].id === deploymentId) {
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
}());



(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('DeploymentsAddController', DeploymentsAddController);

    DeploymentsAddController.$inject = ['$location', 'deploymentsService', 'alertsService'];

    function DeploymentsAddController($location, deploymentsService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.isAdd = true;
        vm.deployment = [];
        vm.servers = [];
        vm.repositories = [];
        vm.branches = [];

        vm.addDeployment = addDeployment;
        vm.refreshBranches = refreshBranches;

        init();

        /**
         * Loads data required for add deployment form.
         */
        function init() {
            // load servers:
            deploymentsService.getServers().then(function (data) {
                vm.servers = data;
            }, function(reason) {
                console.log('Error fetching servers: ' + reason);
            });

            // load repositories:
            deploymentsService.getRepositories().then(function (data) {
                vm.repositories = data;
            }, function(reason) {
                console.log('Error fetching repositories: ' + reason);
            });
        }

        /**
         * Requests add-deployment action on project backend.
         */
        function addDeployment() {

            if (vm.deployment.hasOwnProperty('repository_id')) {
                vm.deployment.repository_id = vm.deployment.repository_id.id;
                vm.deployment.server_id = vm.deployment.server_id.id;
            }
            deploymentsService.addDeployment(vm.deployment).then(function(data) {
                $location.path('/deployments');
                alertsService.queueAlert('Deployment successfully added.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }

        /**
         * Refresh branches list if repository is changed.
         */
        function refreshBranches() {
            deploymentsService.getRepositoryBranches(vm.deployment.repository_id.id).then(function (data) {
                vm.branches = data;
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }
    }
}());



(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('DeploymentsEditController', DeploymentsEditController);

    DeploymentsEditController.$inject = ['$location', '$routeParams', 'deploymentsService', 'alertsService'];

    function DeploymentsEditController($location, $routeParams, deploymentsService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.isEdit = true;
        vm.servers = [];
        vm.repositories = [];
        vm.deployment = [];

        vm.updateDeployment = updateDeployment;

        init();

        /**
         * Loads data required for edit deployment form.
         */
        function init() {
            // load servers:
            deploymentsService.getServers().then(function (data) {
                vm.servers = data;
            }, function(reason) {
                console.log('Error fetching servers: ' + reason);
            });

            // load repositories:
            deploymentsService.getRepositories().then(function (data) {
                vm.repositories = data;
            }, function(reason) {
                console.log('Error fetching repositories: ' + reason);
            });

            // load deployment:
            var deploymentId = ($routeParams.deploymentId) ? parseInt($routeParams.deploymentId) : 0;
            var getDeploymentDataPromise = deploymentsService.getDeploymentData(deploymentId);
            getDeploymentDataPromise.then(function(data) {
                vm.deployment = data;
            }, function(reason) {
                $location.path('/deployments');
            });
        }

        /**
         * Updates deployment data.
         */
        function updateDeployment() {
            var deploymentData = angular.copy(vm.deployment);
            if (vm.deployment.hasOwnProperty('repository_id')) {
                deploymentData.repository_id = deploymentData.repository_id.id;
                deploymentData.server_id = deploymentData.server_id.id;
            }
            deploymentsService.updateDeployment(deploymentData).then(function (data) {
                alertsService.pushAlert('Deployment successfully updated.', 'success');
            }, function (reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }
    }
}());



(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('DeploymentsRunController', DeploymentsRunController);

    DeploymentsRunController.$inject = ['$location', '$routeParams', 'deploymentsService'];

    function DeploymentsRunController($location, $routeParams, deploymentsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.deployment = [];

        vm.triggerDeploy = triggerDeploy;

        init();

        /**
         * Loads data required for run deployment view.
         */
        function init() {
            // load deployment:
            var deploymentId = ($routeParams.deploymentId) ? parseInt($routeParams.deploymentId) : 0;
            deploymentsService.getDeploymentData(deploymentId).then(function (data) {
                vm.deployment = data;
            }, function(reason) {
                $location.path('/deployments');
            });
        }

        function triggerDeploy(deploymentId) {
            deploymentsService.triggerDeployAction(deploymentId);
        }
    }
}());