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
