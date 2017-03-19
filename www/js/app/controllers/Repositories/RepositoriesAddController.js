(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('RepositoriesAddController', RepositoriesAddController);

    RepositoriesAddController.$inject = ['$location', 'repositoriesService', 'alertsService'];

    function RepositoriesAddController($location, repositoriesService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.isAdd = true;
        vm.repository = {};
        vm.addRepository = addRepository;

        /**
         * Requests add-repository action on project backend.
         */
        function addRepository() {
            repositoriesService.addRepository(vm.repository).then(function() {
                $location.path('/repositories');
                alertsService.queueAlert('Repository successfully added.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            })
        }
    }
})();
