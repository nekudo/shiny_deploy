app.service('deploymentsService', ['ws', '$q', function (ws, $q) {

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
     * Fetches list of repository branches.
     *
     * @param {Number} repositoryId
     * @returns {promise}
     */
    this.getRepositoryBranches = function(repositoryId) {
        var requestParams = {
            repositoryId: repositoryId
        };
        return ws.sendDataRequest('getRepositoryBranches', requestParams);
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

        var deferred = $q.defer();
        var requestParams = {
            deploymentId: deploymentId
        };

        ws.sendDataRequest('getDeploymentData', requestParams).then(function(data) {
            if (data.hasOwnProperty('tasks') && data.tasks === '') {
                data.tasks = [];
            }
            data.server = {};
            if (data.hasOwnProperty('server_id')) {
                data.server = { id: data.server_id };
            }

            data.repository = {};
            if (data.hasOwnProperty('repository_id')) {
                data.repository = { id: data.repository_id };
            }

            data.branchObj = {};
            if (data.hasOwnProperty('branch')) {
                data.branchObj = { id: data.branch };
            }

            deferred.resolve(data);
        });

        return deferred.promise;
    };

    /**
     * Trigger API key generation.
     *
     * @param {Array} deploymentData
     * @returns {promise}
     */
    this.generateApiKey = function (deploymentData) {
        var requestParams = {
            deploymentData: deploymentData
        };
        return ws.sendDataRequest('generateApiKey', requestParams);
    };

    /**
     * Registers an event listener at websocket module.
     *
     * @param {string} eventName
     * @param {callback} callback
     */
    this.addEventListener = function(eventName, callback) {
        ws.addListener(eventName, callback);
    };

    /**
     * Triggers a gearman job through websocket server.
     *
     * @param {string} jobName
     * @param {object} jobPayload
     */
    this.triggerJob = function(jobName, jobPayload) {
        ws.sendTriggerRequest(jobName, jobPayload);
    };

    /**
     * Fetches list of deployment logs.
     *
     * @param {Number} deploymentId
     * @returns {Promise}
     */
    this.getDeploymentLogs = function(deploymentId) {
        var requestParams = {
            deploymentId: deploymentId
        };
        return ws.sendDataRequest('getDeploymentLogs', requestParams);
    };
}]);
