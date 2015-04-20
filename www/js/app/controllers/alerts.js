app.controller('AlertsController', ['$scope', 'alertsService',
    function ($scope, alertsService) {

        /**
         * Alert message storage.
         *
         * @type {Array}
         */
        $scope.alerts = [];

        /**
         * Adds new alert message.
         *
         * @param message
         * @param type
         */
        $scope.addAlert = function(message, type) {
            $scope.alerts.push({
                msg: message,
                type: type
            });
        };

        /**
         * Removes alert message from storage.
         *
         * @param index
         */
        $scope.removeAlert = function(index) {
            $scope.alerts.splice(index, 1);
        };

        /**
         * Listens for alertMessages emitted by alerts service.
         */
        function addMessageListener() {
            var _unregister;
            _unregister = $scope.$on('alertMessage', function (event, message, type) {
                $scope.addAlert(message, type);
            });
            $scope.$on("$destroy", _unregister);
        }

        /**
         * Displays queued alert message.
         */
        function showQueuedAlert() {
            var queuedAlert = alertsService.getQueuedAlert();
            if (queuedAlert !== '') {
                $scope.addAlert(queuedAlert.message, queuedAlert.type);
            }
        }

        addMessageListener();
        showQueuedAlert();
    }
]);