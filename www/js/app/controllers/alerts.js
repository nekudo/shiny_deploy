(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('AlertsController', AlertsController);

    AlertsController.$inject = ['$scope', 'alertsService'];

    function AlertsController($scope, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        /** @type {Array} Alert message storage. */
        vm.alerts = [];

        vm.addAlert = addAlert;
        vm.removeAlert = removeAlert;

        init();

        /**
         * Display queued alerts and listen for new alerts.
         */
        function init() {
            // listen for new alert messages:
            var _unregister;
            _unregister = $scope.$on('alertMessage', function (event, message, type) {
                vm.addAlert(message, type);
            });
            $scope.$on("$destroy", _unregister);

            // display alert messages still in queue:
            var queuedAlert = alertsService.getQueuedAlert();
            if (queuedAlert !== '') {
                vm.addAlert(queuedAlert.message, queuedAlert.type);
            }
        }

        /**
         * Adds new alert message.
         *
         * @param message
         * @param type
         */
        function addAlert(message, type) {
            vm.alerts.push({
                msg: message,
                type: type
            });
        }

        /**
         * Removes alert message from storage.
         *
         * @param index
         */
        function removeAlert(index) {
            vm.alerts.splice(index, 1);
        }
    }

})();
