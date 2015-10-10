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



(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('ServersAddController', ServersAddController);

    ServersAddController.$inject = ['$location', 'serversService', 'alertsService'];

    function ServersAddController($location, serversService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.isAdd = true;
        vm.server = {};

        vm.addServer = addServer;

        /**
         * Requests add-server action on project backend.
         */
        function addServer() {
            serversService.addServer(vm.server).then(function() {
                $location.path('/servers');
                alertsService.queueAlert('Server successfully added.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            })
        }
    }
})();



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
