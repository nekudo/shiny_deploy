app.controller('MenuController', function ($scope, $location) {
    /**
     * Checks weather menu-item is active.
     *
     * @param path
     * @returns {boolean}
     */
    $scope.getClass = function (path) {
        return ($location.path().substr(0, path.length) == path);
    }
});