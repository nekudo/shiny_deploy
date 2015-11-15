(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('LoginController', LoginController);

    LoginController.$inject = ['$scope', '$location', 'auth', 'alertsService'];

    function LoginController($scope, $location, auth, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        // Properties
        vm.password = '';

        // Methods
        vm.login = login;

        /**
         * Check login credentials.
         */
        function login() {
            auth.login(vm.password).then(function(response) {
                if (response.hasOwnProperty('success') && response.success === true) {
                    auth.setToken(response.token);
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
