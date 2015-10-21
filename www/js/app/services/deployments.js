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
     * Triggers the deployment action in backend.
     *
     * @param deploymentId
     * @returns {boolean}
     */
    this.triggerDeployAction = function(deploymentId) {
        if (deploymentId === 0) {
            return false;
        }
        ws.sendTriggerRequest('startDeploy', {
            deploymentId: deploymentId
        });
    };

    /**
     * Fetches a list of changed files between local and remote revision.
     *
     * @param {number} deploymentId
     * @returns {promise}
     */
    this.triggerGetChangedFiles = function(deploymentId) {
        ws.sendTriggerRequest('startGetChangedFiles', {
            deploymentId: deploymentId
        });
    };

    /**
     * Trigger request for a file-diff.
     *
     * @param {Array} params
     * @returns {promise}
     */
    this.triggerGetFileDiff = function(params) {
        ws.sendTriggerRequest('startGetDiff', params);
    };

    /**
     * Triggers event to set remote revision value.
     *
     * @param {Number} deploymentId
     * @returns {promise}
     */
    this.triggerSetRemoteRevision = function(deploymentId) {
        var params = {
            deploymentId: deploymentId
        };
         ws.sendTriggerRequest('setRemoteRevision', params);
    };

    /**
     * Triggers event to set local revision value.
     *
     * @param {Number} deploymentId
     * @returns {promise}
     */
    this.triggerSetLocalRevision = function(deploymentId) {
        var params = {
            deploymentId: deploymentId
        };
        ws.sendTriggerRequest('setLocalRevision', params);
    };
}]);
