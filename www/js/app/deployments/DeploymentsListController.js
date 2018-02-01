(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('DeploymentsListController', DeploymentsListController);

    DeploymentsListController.$inject = ['deploymentsService', 'alertsService'];

    function DeploymentsListController(deploymentsService, alertsService) {
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
                        vm.deployments.splice(i, 1);
                        break;
                    }
                }
                alertsService.pushAlert('Deployment successfully deleted.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }
    }
})();
