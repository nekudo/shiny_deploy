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
        var servers = null;
        var repositories = null;

        // load servers:
        var getServersPromise = ws.sendDataRequest('getServers');
        getServersPromise.then(function(data) {
            servers = data;
        }, function(reason) {
            deferred.reject('Error fetching servers: ' + reason);
        });

        // load repositories:
        var getRepositoriesPromise = ws.sendDataRequest('getRepositories');
        getRepositoriesPromise.then(function(data) {
            repositories = data;
        }, function(reason) {
            deferred.reject('Error fetching repositories: ' + reason);
        });

        // load deployment:
        var getDeploymentDataPromise = ws.sendDataRequest('getDeploymentData', {
            deploymentId: deploymentId
        });
        getDeploymentDataPromise.then(function(data) {
            if (data.hasOwnProperty('repository_id')) {
                for (var i = repositories.length - 1; i >= 0; i--) {
                    if (repositories[i].id === data.repository_id) {
                        data.repository_id = repositories[i];
                        break;
                    }
                }
                for (var j = servers.length - 1; j >= 0; j--) {
                    if (servers[j].id === data.server_id) {
                        data.server_id = servers[j];
                        break;
                    }
                }
            }
            deferred.resolve(data);
        });

        return deferred.promise;
    };

    this.triggerDeployAction = function(deploymentId) {
        if (deploymentId === 0) {
            return false;
        }
        ws.sendTriggerRequest('startDeploy', {
            deploymentId: deploymentId
        })
    }
}]);