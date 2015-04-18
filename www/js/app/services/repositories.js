app.service('repositoriesService', function (ws) {
    /**
     * Fetches list of repositories.
     *
     * @returns {promise}
     */
    this.getRepositories = function () {
        return ws.sendDataRequest('getRepositories');
    };

    /**
     * Adds new repository.
     *
     * @param {Array} repositoryData
     * @returns {promise}
     */
    this.addRepository = function (repositoryData) {
        var requestParams = {
            repositoryData: repositoryData
        };
        return ws.sendDataRequest('addRepository', requestParams);
    };

    /**
     * Removes a repository from database.
     *
     * @param {number} repositoryId
     * @returns {promise}
     */
    this.deleteRepository = function (repositoryId) {
        var requestParams = {
            repositoryId: repositoryId
        };
        return ws.sendDataRequest('deleteRepository', requestParams);
    };

    /**
     * Updates existing repository.
     *
     * @param {Array} repositoryData
     * @returns {promise}
     */
    this.updateRepository = function (repositoryData) {
        var requestParams = {
            repositoryData: repositoryData
        };
        return ws.sendDataRequest('updateRepository', requestParams);
    };

    /**
     * Fetches repository data.
     *
     * @param {number} repositoryId
     * @returns {bool|promise}
     */
    this.getRepositoryData = function(repositoryId) {
        if (repositoryId === 0) {
            return false;
        }
        var requestParams = {
            repositoryId: repositoryId
        };
        return ws.sendDataRequest('getRepositoryData', requestParams);
    };
});