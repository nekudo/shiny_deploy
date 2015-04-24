app.controller('RepositoriesController', ['$scope', 'repositoriesService', 'alertsService',
    function ($scope, repositoriesService, alertsService) {
        var repositories = null;

        init();

        /**
         * Loads data required for repositories index view.
         */
        function init() {
            // load repositories:
            var getRepositoriesPromise = repositoriesService.getRepositories();
            getRepositoriesPromise.then(function(data) {
                repositories = data;
            }, function(reason) {
                console.log('Error fetching repositories: ' + reason);
            });
        }

        /**
         * Returns list of repositories.
         *
         * @returns {null|Array}
         */
        $scope.getRepositories = function() {
            return repositories;
        };

        /**
         * Removes a repository.
         *
         * @param {number} repositoryId
         */
        $scope.deleteRepository = function(repositoryId) {
            var deleteRepositoryPromise = repositoriesService.deleteRepository(repositoryId);
            deleteRepositoryPromise.then(function(data) {
                for (var i = repositories.length - 1; i >= 0; i--) {
                    if (repositories[i].id === repositoryId) {
                        repositories.splice(i, 1);
                        break;
                    }
                }
                alertsService.pushAlert('Repository successfully deleted.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }
    }
]);

app.controller('RepositoriesAddController', ['$scope', '$location', 'repositoriesService', 'alertsService',
    function ($scope, $location, repositoriesService, alertsService) {
        $scope.isAdd = true;

        /**
         * Requests add-repository action on project backend.
         */
        $scope.addRepository = function() {
            var promise = repositoriesService.addRepository($scope.repository);
            promise.then(function(data) {
                $location.path('/repositories');
                alertsService.queueAlert('Repository successfully added.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            })
        }
    }
]);

app.controller('RepositoriesEditController', ['$scope', '$location', '$routeParams', 'repositoriesService', 'alertsService',
    function ($scope, $location, $routeParams, repositoriesService, alertsService) {
        $scope.isEdit = true;

        init();

        /**
         * Loads data required for edit repository view.
         */
        function init() {
            // load repository data:
            var repositoryId = ($routeParams.repositoryId) ? parseInt($routeParams.repositoryId) : 0;
            var getRepositoryDataPromise = repositoriesService.getRepositoryData(repositoryId);
            getRepositoryDataPromise.then(function(data) {
                $scope.repository = data;
            }, function(reason) {
                $location.path('/repositories');
            });
        }

        /**
         * Updates a repository.
         */
        $scope.updateRepository = function() {
            var updateRepositoryPromise = repositoriesService.updateRepository($scope.repository);
            updateRepositoryPromise.then(function (data) {
                alertsService.pushAlert('Repository successfully updated.', 'success');
            }, function (reason) {
                alertsService.pushAlert(reason, 'warning');
            })
        }
    }
]);