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
