var app = angular.module('shinyDeploy', ['ngRoute', 'ws', 'angular-jwt', 'shinyDeploy.config']);

app.config(['$routeProvider', '$locationProvider', 'wsProvider', 'shinyDeployConfig',
    function ($routeProvider, $locationProvider, wsProvider, shinyDeployConfig) {
        $locationProvider.html5Mode(true);

        wsProvider.setUrl(shinyDeployConfig.wsUrl);

        $routeProvider
            .when('/', {
                controller: 'HomeController',
                controllerAs: 'vm',
                templateUrl: '/js/app/home/home.html'
            })
            .when('/login', {
                controller: 'LoginController',
                controllerAs: 'vm',
                templateUrl: '/js/app/login/login.html'
            })
            .when('/servers', {
                controller: 'ServersListController',
                controllerAs: 'vm',
                templateUrl: '/js/app/servers/servers.html'
            })
            .when('/servers/add', {
                controller: 'ServersAddController',
                controllerAs: 'vm',
                templateUrl: '/js/app/servers/servers_form.html'
            })
            .when('/servers/edit/:serverId', {
                controller: 'ServersEditController',
                controllerAs: 'vm',
                templateUrl: '/js/app/servers/servers_form.html'
            })
            .when('/repositories', {
                controller: 'RepositoriesListController',
                controllerAs: 'vm',
                templateUrl: '/js/app/repositories/repositories.html'
            })
            .when('/repositories/add', {
                controller: 'RepositoriesAddController',
                controllerAs: 'vm',
                templateUrl: '/js/app/repositories/repositories_form.html'
            })
            .when('/repositories/edit/:repositoryId', {
                controller: 'RepositoriesEditController',
                controllerAs: 'vm',
                templateUrl: '/js/app/repositories/repositories_form.html'
            })
            .when('/deployments', {
                controller: 'DeploymentsListController',
                controllerAs: 'vm',
                templateUrl: '/js/app/deployments/deployments.html'
            })
            .when('/deployments/add', {
                controller: 'DeploymentsAddController',
                controllerAs: 'vm',
                templateUrl: '/js/app/deployments/deployments_form.html'
            })
            .when('/deployments/edit/:deploymentId', {
                controller: 'DeploymentsEditController',
                controllerAs: 'vm',
                templateUrl: '/js/app/deployments/deployments_form.html'
            })
            .when('/deployments/run/:deploymentId', {
                controller: 'DeploymentsRunController',
                controllerAs: 'vm',
                templateUrl: '/js/app/deployments/deployments_run.html'
            })
            .otherwise({ redirectTo: '/login' });
    }
]);

app.run(['ws', 'authService', function(ws, authService) {
    // connect to websocket server:
    ws.connect();

    // Init authentication service:
    authService.init();
}]);
