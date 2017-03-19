(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('LogController', LogController);

    LogController.$inject = ['$scope', 'ws'];

    function LogController($scope, ws) {
        /*jshint validthis: true */
        var vm = this;

        vm.logs = [];

        // listen to "log" events on websocket stream
        ws.addListener('log', function(eventData) {
            addLogMessage(eventData);
        });

        /**
         * Logs message to console.
         *
         * Possible log-types are: default,info,success,danger,error
         *
         * @param {Array} data
         */
        function addLogMessage(data) {
            var msgLines = data.text.split("\n");
            for (var i = 0; i < msgLines.length; i++) {
                pushMessageToQueue({
                    message: msgLines[i],
                    styleclass: (data.hasOwnProperty('type')) ? 'log-' + data.type : 'log-default',
                    time: (data.hasOwnProperty('time')) ? data.time : getTimeString(),
                    source: (data.hasOwnProperty('source')) ? data.source : ''
                });
            }
            $(".nano").nanoScroller().nanoScroller({ scroll: 'bottom' });
        }

        /**
         * Pushes a new log-message to queue and relaods scope.
         *
         * @param {Object} logMessage
         */
        function pushMessageToQueue(logMessage) {
            $scope.$apply(function() {
                vm.logs.push(logMessage);
            });
        }

        /**
         * Returns current time
         *
         * @returns {string}
         */
        function getTimeString()  {
            var currentdate = new Date();
            return ((currentdate.getHours() < 10)?"0":"") + currentdate.getHours() +":"
                + ((currentdate.getMinutes() < 10)?"0":"") + currentdate.getMinutes() +":"
                + ((currentdate.getSeconds() < 10)?"0":"") + currentdate.getSeconds();
        }
    }
})();
