(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('MenuController', MenuController);

    MenuController.$inject = ['$location'];

    function MenuController($location) {
        /*jshint validthis: true */
        var vm = this;

        vm.getClass = getClass;

        function getClass(path) {
            return ($location.path().substr(0, path.length) == path);
        }
    }
})();