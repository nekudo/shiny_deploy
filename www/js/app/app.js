var app = angular.module('shinyDeploy', ['ngRoute', 'ws']);

app.config(['$routeProvider', '$locationProvider', 'wsProvider', function ($routeProvider, $locationProvider, wsProvider) {
    $locationProvider.html5Mode(true);

    wsProvider.setUrl('ws://127.0.0.1:8090');

    $routeProvider
        .when('/', {
            controller: 'HomeController',
            templateUrl: '/js/app/views/home.html'
        })
        .when('/servers', {
            controller: 'ServersController',
            templateUrl: '/js/app/views/servers.html'
        })
        .when('/servers/add', {
            controller: 'ServersAddController',
            templateUrl: '/js/app/views/servers_form.html'
        })
        .when('/servers/edit/:serverId', {
            controller: 'ServersEditController',
            templateUrl: '/js/app/views/servers_form.html'
        })
        .when('/repositories', {
            controller: 'RepositoriesController',
            templateUrl: '/js/app/views/repositories.html'
        })
        .when('/repositories/add', {
            controller: 'RepositoriesAddController',
            templateUrl: '/js/app/views/repositories_form.html'
        })
        .when('/repositories/edit/:repositoryId', {
            controller: 'RepositoriesEditController',
            templateUrl: '/js/app/views/repositories_form.html'
        })
        .when('/deployments', {
            controller: 'DeploymentsController',
            templateUrl: '/js/app/views/deployments.html'
        })
        .when('/deployments/add', {
            controller: 'DeploymentsAddController',
            templateUrl: '/js/app/views/deployments_form.html'
        })
        .when('/deployments/edit/:deploymentId', {
            controller: 'DeploymentsEditController',
            templateUrl: '/js/app/views/deployments_form.html'
        })
        .otherwise({ redirectTo: '/' });
}]);

app.run(['ws', function(ws) {
    // connect to websocket server:
    ws.connect();
}]);