(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('LoginController', LoginController);

    LoginController.$inject = ['$location', 'auth', 'alertsService'];

    function LoginController($location, auth, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        // Properties
        vm.password = '';
        vm.password_verify = '';
        vm.systemUserExists = false;

        // Methods
        vm.login = login;
        vm.createSystemUser = createSystemUser;

        init();


        /**
         * Checks if master-password hash is already set.
         *
         */
        function init() {
            auth.systemUserExists().then(function(response) {
                if (response.hasOwnProperty('userExists') && response.userExists === true) {
                    vm.systemUserExists = true;
                }
            });
        };

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

        /**
         * Triggers creation of system user.
         *
         * @returns {booloan}
         */
        function createSystemUser() {
           auth.createSystemUser(vm.password, vm.password_verify).then(function(response) {
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
