(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('ServersEditController', ServersEditController);

    ServersEditController.$inject = ['$location', '$routeParams', 'serversService', 'alertsService'];

    function ServersEditController($location, $routeParams, serversService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.isEdit = true;
        vm.server = {};

        vm.updateServer = updateServer;

        init();

        /**
         * Loads data required for edit server view.
         */
        function init() {
            // load server data:
            var serverId = ($routeParams.serverId) ? parseInt($routeParams.serverId) : 0;
            serversService.getServerData(serverId).then(function(data) {
                if (data.hasOwnProperty('port')) {
                    data.port = parseInt(data.port);
                }
                vm.server = data;
            }, function() {
                $location.path('/servers');
            });
        }

        /**
         * Updates a server.
         */
        function updateServer() {
            serversService.updateServer(vm.server).then(function () {
                alertsService.pushAlert('Server successfully updated.', 'success');
            }, function (reason) {
                alertsService.pushAlert(reason, 'warning');
            })
        }
    }
})();
