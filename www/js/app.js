var app = angular.module('shinyDeploy', ['ngRoute']);

app.config(function ($routeProvider) {
    $routeProvider
        .when('/servers', {
            controller: 'ServersController',
            templateUrl: '/js/app/views/servers.html'
        })
        .when('/repositories', {
            controller: 'RepositoriesController',
            templateUrl: '/js/app/views/repositories.html'
        })
        .otherwise({ redirectTo: '/' });
});