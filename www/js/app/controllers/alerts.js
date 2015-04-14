app.controller('AlertsController', function ($scope) {

    $scope.alerts = [];

    addMessageListener();

    $scope.addAlert = function(message, type) {
        $scope.alerts.push({
            msg: message,
            type: type
        });
    };

    $scope.removeAlert = function(index) {
        $scope.alerts.splice(index, 1);
    };

    function addMessageListener() {
        var _unregister;
        _unregister = $scope.$on('alertMessage', function (event, message, type) {
            $scope.addAlert(message, type);
        });
        $scope.$on("$destroy", _unregister);
    }
});