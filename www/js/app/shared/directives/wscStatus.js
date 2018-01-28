(function () {
    "use strict";
    angular
        .module('shinyDeploy')
        .directive('wscStatus', wscStatus);

    function wscStatus() {

        var directive = {
            restrict: 'A',
            template: '<span class="fa fa-plug {{vm.statusClass}}"></span>',
            scope: {},
            replace: true,
            link: link,
            controller: WscStatusController,
            controllerAs: 'vm',
            bindToController: true
        };

        return directive;

        function link(scope, element, attrs) {

        }
    }

    WscStatusController.$inject = ['$scope', 'ws'];

    function WscStatusController($scope, ws) {
        // Injecting $scope just for comparison
        var vm = this;

        // Properties
        vm.statusClass = 'text-red';

        // Methods
        vm.updateStatusClass = updateStatusClass;

        // Listen for websocket events:
        var _unregister;
        _unregister = $scope.$on('wsStatusChange', function (event, statusNew) {
            $scope.$apply(function() {
                vm.updateStatusClass(statusNew);
            });
        });
        $scope.$on("$destroy", _unregister);


        function updateStatusClass(connStatus) {
            switch (connStatus) {
                case 'connected':
                    vm.statusClass = 'text-green';
                    break;
                case 'disconnected':
                    vm.statusClass = 'text-red';
                    break;
            }
        }
    }
})();