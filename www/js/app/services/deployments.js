app.service('deploymentsService', ['ws', function (ws) {

    /**
     * Fetches list of deployments.
     *
     * @returns {promise}
     */
    this.getDeployments = function () {
        return ws.sendDataRequest('getDeployments');
    };

    /**
     * Fetches list of servers.
     *
     * @returns {promise}
     */
    this.getServers = function() {
        return ws.sendDataRequest('getServers');
    };

    /**
     * Fetches list of repositories.
     *
     * @returns {promise}
     */
    this.getRepositories = function() {
        return ws.sendDataRequest('getRepositories');
    };

    /**
     * Adds new repository.
     *
     * @param {Array} deploymentData
     * @returns {promise}
     */
    this.addDeployment = function (deploymentData) {
        var requestParams = {
            deploymentData: deploymentData
        };
        return ws.sendDataRequest('addDeployment', requestParams);
    };

    /**
     * Removes a deployment from database.
     *
     * @param {number} deploymentId
     * @returns {promise}
     */
    this.deleteDeployment = function (deploymentId) {
        var requestParams = {
            deploymentId: deploymentId
        };
        return ws.sendDataRequest('deleteDeployment', requestParams);
    };

    /**
     * Updates existing deployment.
     *
     * @param {Array} deploymentData
     * @returns {promise}
     */
    this.updateDeployment = function (deploymentData) {
        var requestParams = {
            deploymentData: deploymentData
        };
        return ws.sendDataRequest('updateDeployment', requestParams);
    };

    /**
     * Fetches data for a deployment.
     *
     * @param {number} deploymentId
     * @returns {bool|promise}
     */
    this.getDeploymentData = function(deploymentId) {
        if (deploymentId === 0) {
            return false;
        }
        var requestParams = {
            deploymentId: deploymentId
        };
        return ws.sendDataRequest('getDeploymentData', requestParams);
    };
}]);