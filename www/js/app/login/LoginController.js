(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('LoginController', LoginController);

    LoginController.$inject = ['$location', 'authService', 'alertsService'];

    function LoginController($location, authService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        // Properties
        vm.username = '';
        vm.password = '';
        vm.password_verify = '';

        // Methods
        vm.login = login;

        /**
         * Check login credentials.
         */
        function login() {
            authService.login(vm.username, vm.password).then(function(response) {
                if (response.hasOwnProperty('success') && response.success === true) {
                    authService.setToken(response.token);
                    $location.path('/');
                } else {
                    alertsService.pushAlert('Login failed.', 'error');
                }
            }, function(reason) {
                alertsService.pushAlert(reason, 'error');
            });
        }
    }
})();
