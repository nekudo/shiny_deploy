app.service('alertsService', function ($rootScope) {

    this.pushAlert = function (message, type) {
        type = typeof type !== 'undefined' ? type : 'info';
        $rootScope.$broadcast('alertMessage', message, type);
    };

});