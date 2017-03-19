(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('ServersController', ServersController);

    ServersController.$inject = ['serversService', 'alertsService'];

    function ServersController(serversService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.servers = {};

        vm.getServers = getServers;
        vm.deleteServer = deleteServer;

        init();

        /**
         * Load data required for servers index view.
         */
        function init() {
            serversService.getServers().then(function(data) {
                vm.servers = data;
            }, function(reason) {
                console.log('Error fetching servers: ' + reason);
            });
        }

        /**
         * Returns list of servers.
         *
         * @returns {Array}
         */
        function getServers() {
            return vm.servers;
        }

        /**
         * Removes a server.
         *
         * @param {number} serverId
         */
        function deleteServer(serverId) {
            serversService.deleteServer(serverId).then(function() {
                for (var i = vm.servers.length - 1; i >= 0; i--) {
                    if (vm.servers[i].id === serverId) {
                        vm.servers.splice(i, 1);
                        break;
                    }
                }
                alertsService.pushAlert('Server successfully deleted.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }
    }
})();
