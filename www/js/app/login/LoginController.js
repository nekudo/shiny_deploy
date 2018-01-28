(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('LoginController', LoginController);

    LoginController.$inject = ['$timeout', '$location', 'authService', 'alertsService'];

    function LoginController($timeout, $location, authService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        // Properties
        vm.password = '';
        vm.password_verify = '';
        vm.systemUserExists = true;

        // Methods
        vm.login = login;
        vm.createSystemUser = createSystemUser;

        init();


        /**
         * Checks if master-password hash is already set.
         *
         */
        function init() {
            $timeout(function() {
                authService.systemUserExists().then(function(response) {
                    if (response.hasOwnProperty('userExists') && response.userExists !== true) {
                        vm.systemUserExists = false;
                    }
                });
            }, 100);
        };

        /**
         * Check login credentials.
         */
        function login() {
            authService.login(vm.password).then(function(response) {
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

        /**
         * Triggers creation of system user.
         *
         * @returns {booloan}
         */
        function createSystemUser() {
            authService.createSystemUser(vm.password, vm.password_verify).then(function(response) {
               if (response.hasOwnProperty('success') && response.success === true) {
                   vm.systemUserExists = true;
                   alertsService.pushAlert('System user successfully created.', 'success');
                   return true;
               }
               alertsService.pushAlert('Error while creating system user. Check logfile for details', 'error');
               return false;
           }, function(error) {
               alertsService.pushAlert(error, 'error');
           });
        }
    }
})();
