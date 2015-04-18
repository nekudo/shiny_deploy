app.controller('RepositoriesController', function ($scope, repositoriesService, alertsService) {
    var repositories = null;

    loadRepositories();

    /**
     * Requests repositories list from project backend.
     */
    function loadRepositories() {
        var promise = repositoriesService.getRepositories();
        promise.then(function(data) {
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
        var promise = repositoriesService.deleteRepository(repositoryId);
        promise.then(function(data) {
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
});

app.controller('RepositoriesAddController', function ($scope, $location, repositoriesService, alertsService) {
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
});

app.controller('RepositoriesEditController', function ($scope, $location, $routeParams, repositoriesService, alertsService) {
    $scope.isEdit = true;

    // Fetch repository data:
    var repositoryId = ($routeParams.repositoryId) ? parseInt($routeParams.repositoryId) : 0;
    var promise = repositoriesService.getRepositoryData(repositoryId);
    promise.then(function(data) {
        $scope.repository = data;
    }, function(reason) {
        $location.path('/repositories');
    });

    $scope.updateRepository = function() {
        var promise = repositoriesService.updateRepository($scope.repository);
        promise.then(function (data) {
            alertsService.pushAlert('Repository successfully updated.', 'success');
        }, function (reason) {
            alertsService.pushAlert(reason, 'warning');
        })
    }
});