app.service('alertsService', function ($rootScope) {

    /**
     * Alert message queue.
     *
     * @type {Array}
     */
    this.queue = [];

    /**
     * Broadcasts new alert message.
     *
     * @param message
     * @param type
     */
    this.pushAlert = function (message, type) {
        type = typeof type !== 'undefined' ? type : 'info';
        $rootScope.$broadcast('alertMessage', message, type);
    };

    /**
     * Queues alert message.
     *
     * @param message
     * @param type
     */
    this.queueAlert = function(message, type) {
        type = typeof type !== 'undefined' ? type : 'info';
        var msgObject = {
            type: type,
            message: message
        };
        this.queue.push(msgObject);
    };

    /**
     * Fetches latest message from queue.
     *
     * @returns {string}
     */
    this.getQueuedAlert = function() {
        return this.queue.shift() || "";
    };
});