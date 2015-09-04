(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('NotificationsController', NotificationsController);

    NotificationsController.$inject = ['$scope', '$timeout', 'ws'];

    function NotificationsController($scope, $timeout, ws) {
        /*jshint validthis: true */
        var vm = this;

        vm.notifications = [];
        vm.notificationsCount = 0;

        addNotificationListener();

        /**
         * Listen for notification events on websocket connection:
         */
        function addNotificationListener() {
            ws.addListener('notification', function(eventData) {
                var message = eventData.text;
                var type = (typeof eventData.type !== 'undefined') ? eventData.type : 'default';
                pushNotification(message, type);
            });
        }

        /**
         * Adds new alert message.
         *
         * @param {string} message
         * @param {string} type
         */
        function pushNotification(message, type) {
            $scope.$apply(function() {
                vm.notifications.push({
                    msg: message,
                    type: type,
                    id: 'notification-' + notificationsCount
                });
                $timeout(removeNotification, 5000);
                vm.notificationsCount++;
            });
        }

        /**
         * Removes notification from stack.
         */
        function removeNotification() {
            vm.notifications.splice(0, 1);
        }
    }
}());