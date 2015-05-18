app.controller('NotificationsController', ['$scope', '$timeout', 'ws',
    function ($scope, $timeout, ws) {

        $scope.notifications = [];
        var notificationsCount = 0;

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
                $scope.notifications.push({
                    msg: message,
                    type: type,
                    id: 'notification-' + notificationsCount
                });
                $timeout(removeNotification, 5000);
                notificationsCount++;
            });
        }

        /**
         * Removes notification from stack.
         */
        function removeNotification() {
            $scope.notifications.splice(0, 1);
        }

        addNotificationListener();
    }
]);