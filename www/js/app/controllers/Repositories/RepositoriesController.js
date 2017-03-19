(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('RepositoriesController', RepositoriesController);

    RepositoriesController.$inject = ['repositoriesService', 'alertsService'];

    function RepositoriesController(repositoriesService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.repositories = {};

        vm.getRepositories = getRepositories;
        vm.deleteRepository = deleteRepository;

        init();

        /**
         * Loads data required for repositories index view.
         */
        function init() {
            // load repositories:
            repositoriesService.getRepositories().then(function(data) {
                vm.repositories = data;
            }, function(reason) {
                console.log('Error fetching repositories: ' + reason);
            });
        }

        /**
         * Returns list of repositories.
         *
         * @returns {Array}
         */
        function getRepositories() {
            return vm.repositories;
        }

        /**
         * Removes a repository.
         *
         * @param {number} repositoryId
         */
        function deleteRepository(repositoryId) {
            repositoriesService.deleteRepository(repositoryId).then(function() {
                for (var i = vm.repositories.length - 1; i >= 0; i--) {
                    if (vm.repositories[i].id === repositoryId) {
                        vm.repositories.splice(i, 1);
                        break;
                    }
                }
                alertsService.pushAlert('Repository successfully deleted.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }
    }
})();
