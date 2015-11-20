var app = angular.module('shinyDeploy', ['ngRoute', 'ws', 'angular-jwt', 'shinyDeploy.config']);

app.config(['$routeProvider', '$locationProvider', 'wsProvider', 'shinyDeployConfig',
    function ($routeProvider, $locationProvider, wsProvider, shinyDeployConfig) {
        $locationProvider.html5Mode(true);

        wsProvider.setUrl(shinyDeployConfig.wsUrl);

        $routeProvider
            .when('/', {
                controller: 'HomeController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/home.html'
            })
            .when('/login', {
                controller: 'LoginController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/login.html'
            })
            .when('/servers', {
                controller: 'ServersController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/servers.html'
            })
            .when('/servers/add', {
                controller: 'ServersAddController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/servers_form.html'
            })
            .when('/servers/edit/:serverId', {
                controller: 'ServersEditController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/servers_form.html'
            })
            .when('/repositories', {
                controller: 'RepositoriesController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/repositories.html'
            })
            .when('/repositories/add', {
                controller: 'RepositoriesAddController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/repositories_form.html'
            })
            .when('/repositories/edit/:repositoryId', {
                controller: 'RepositoriesEditController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/repositories_form.html'
            })
            .when('/deployments', {
                controller: 'DeploymentsController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/deployments.html'
            })
            .when('/deployments/add', {
                controller: 'DeploymentsAddController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/deployments_form.html'
            })
            .when('/deployments/edit/:deploymentId', {
                controller: 'DeploymentsEditController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/deployments_form.html'
            })
            .when('/deployments/run/:deploymentId', {
                controller: 'DeploymentsRunController',
                controllerAs: 'vm',
                templateUrl: '/js/app/views/deployments_run.html'
            })
            .otherwise({ redirectTo: '/login' });
    }
]);

app.run(['ws', 'auth', function(ws, auth) {
    // Init authentication service:
    auth.init();

    // connect to websocket server:
    ws.connect();
}]);
