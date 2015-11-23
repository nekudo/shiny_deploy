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
        vm.password_verify = '';
        vm.masterHashSet = false;

        // Methods
        vm.login = login;
        vm.setMasterPasswordHash = setMasterPasswordHash;

        init();


        /**
         * Checks if master-password hash is already set.
         *
         */
        function init() {
            auth.masterHashExists().then(function(response) {
                if (response.hasOwnProperty('hashExists') && response.hashExists === true) {
                    vm.masterHashSet = true;
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
         * Sets new master password.
         *
         * @returns {undefined}
         */
        function setMasterPasswordHash() {
           auth.setMasterPasswordHash(vm.password, vm.password_verify).then(function(response) {
               if (response.hasOwnProperty('success') && response.success === true) {
                   vm.masterHashSet = true;
                   alertsService.pushAlert('Password successfully saved.', 'success');
                   return true;
               }
               alertsService.pushAlert('Error while setting password. Check logs.', 'error');
               return false;
           }, function(error) {
               alertsService.pushAlert(error, 'error');
           });
        }
    }
})();
