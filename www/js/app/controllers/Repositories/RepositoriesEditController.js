(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('RepositoriesEditController', RepositoriesEditController);

    RepositoriesEditController.$inject = ['$location', '$routeParams', 'repositoriesService', 'alertsService'];

    function RepositoriesEditController($location, $routeParams, repositoriesService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.isEdit = true;
        vm.repository = {};

        vm.updateRepository = updateRepository;

        init();

        /**
         * Loads data required for edit repository view.
         */
        function init() {
            // load repository data:
            var repositoryId = ($routeParams.repositoryId) ? parseInt($routeParams.repositoryId) : 0;
            repositoriesService.getRepositoryData(repositoryId).then(function(data) {
                vm.repository = data;
            }, function() {
                $location.path('/repositories');
            });
        }

        /**
         * Updates a repository.
         */
        function updateRepository() {
            repositoriesService.updateRepository(vm.repository).then(function() {
                alertsService.pushAlert('Repository successfully updated.', 'success');
            }, function (reason) {
                alertsService.pushAlert(reason, 'warning');
            })
        }
    }
})();
