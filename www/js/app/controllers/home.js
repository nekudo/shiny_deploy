(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('HomeController', HomeController);

    HomeController.$inject = ['$scope'];

    function HomeController($scope) {
        /*jshint validthis: true */
        var vm = this;
    }
}());
