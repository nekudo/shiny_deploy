(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('DeploymentsRunController', DeploymentsRunController);

    DeploymentsRunController.$inject = [
        '$location',
        '$routeParams',
        '$scope',
        '$sce',
        'deploymentsService',
        'serversService',
        'repositoriesService',
        'ws'
    ];

    function DeploymentsRunController(
        $location,
        $routeParams,
        $scope,
        $sce,
        deploymentsService,
        serversService,
        repositoriesService,
        ws
    ) {
        /*jshint validthis: true */
        var vm = this;

        // Properties
        vm.deployment = {};
        vm.changedFiles = {};
        vm.diff = '';
        vm.remoteRevision = '';
        vm.localRevision = '';
        vm.deploymentLogs = [];
        vm.tasksToRun = {};

        // Methods
        vm.triggerDeploy = triggerDeploy;
        vm.triggerGetChangedFiles = triggerGetChangedFiles;
        vm.triggerShowDiff = triggerShowDiff;
        vm.triggerSetLocalRevision = triggerSetLocalRevision;
        vm.triggerSetRemoteRevision = triggerSetRemoteRevision;

        vm.updateChangedFiles = updateChangedFiles;
        vm.showDiff = showDiff;
        vm.setLocalRevision = setLocalRevision;
        vm.setRemoteRevision = setRemoteRevision;
        vm.closeChangedFiles = closeChangedFiles;
        vm.closeDiff = closeDiff;
        vm.showDeploymentLogs = showDeploymentLogs;
        vm.closeDeploymentLogs = closeDeploymentLogs;

        // Init
        init();

        /**
         * Loads data required for run deployment view.
         */
        function init() {
            // load deployment:
            var deploymentId = ($routeParams.deploymentId) ? parseInt($routeParams.deploymentId) : 0;
            deploymentsService.getDeploymentData(deploymentId).then(function (data) {
                vm.deployment = data;
                resolveRelations();
                initTasksToRun();
            }, function(reason) {
                $location.path('/deployments');
            });

            // Add listener:
            deploymentsService.addEventListener('setLocalRevision', vm.setLocalRevision);
            deploymentsService.addEventListener('setRemoteRevision', vm.setRemoteRevision);
            deploymentsService.addEventListener('updateChangedFiles', vm.updateChangedFiles);
            deploymentsService.addEventListener('showDiff', vm.showDiff);

            // Trigger actions:
            vm.triggerSetLocalRevision(deploymentId);
            vm.triggerSetRemoteRevision(deploymentId);
        }

        /**
         * Fetches data not directly stored within deployments table and stores it into deployment object.
         */
        function resolveRelations() {
            serversService.getServerData(vm.deployment.server_id).then(function(server) {
                vm.deployment.server = server;
            });
            repositoriesService.getRepositoryData(vm.deployment.repository_id).then(function(repository) {
                vm.deployment.repository = repository;
            });
        }

        /**
         * Init list of tasks to run during deployment.
         *
         * @returns {boolean}
         */
        function initTasksToRun() {
            if (vm.deployment.tasks.length === 0) {
                return true;
            }
            angular.forEach(vm.deployment.tasks, function(task, i) {
                vm.tasksToRun[task.id] = 0;
                if (task.hasOwnProperty('run_by_default') && task.run_by_default === "1") {
                    vm.tasksToRun[task.id] = 1;
                }
            });
            return true;
        }

        /**
         * Start new deployment job.
         */
        function triggerDeploy() {
            vm.changedFiles = {};
            vm.diff = '';
            deploymentsService.triggerJob('deploy', {
                deploymentId: vm.deployment.id,
                tasksToRun: vm.tasksToRun
            });
        }

        /**
         * Fetch list of changed files.
         */
        function triggerGetChangedFiles() {
            vm.changedFiles = {};
            vm.diff = '';
            deploymentsService.triggerJob('getChangedFiles', {
                deploymentId: vm.deployment.id
            });
        }

        /**
         * Triggers request to show file diff.
         *
         * @param {number} fileKey
         */
        function triggerShowDiff(fileKey) {
            deploymentsService.triggerJob('getFileDiff', {
                repositoryId: vm.deployment.repository_id,
                localRevision: vm.localRevision,
                remoteRevision: vm.remoteRevision,
                file: vm.changedFiles[fileKey].file
            });
        }

        /**
         * Fetches current revision of remote repository.
         *
         * @param {Number} deploymentId
         */
        function triggerSetRemoteRevision(deploymentId) {
            deploymentsService.triggerJob('setRemoteRevision', {
                deploymentId: deploymentId
            });
        }

        /**
         * Fetches local repository revision.
         *
         * @param {Number} deploymentId
         */
        function triggerSetLocalRevision(deploymentId) {
            deploymentsService.triggerJob('setLocalRevision', {
                deploymentId: deploymentId
            });
        }

        /**
         * Updates changed files list.
         *
         * @param {Object} data
         */
        function updateChangedFiles(data) {
            $scope.$apply(function() {
                vm.changedFiles = data.changedFiles;
            });
        }

        /**
         * Displays a file diff.
         *
         * @param {Object} data
         */
        function showDiff(data) {
            if (!data.hasOwnProperty('diff')) {
                return;
            }
            if (data.diff === null) {
                return;
            }
            $scope.$apply(function() {
                vm.diff = $sce.trustAsHtml(Diff2Html.getPrettyHtmlFromDiff(data.diff));
            });
        }

        /**
         * Sets remote revision value.
         *
         * @param {Object} data
         */
        function setRemoteRevision(data) {
            $scope.$apply(function() {
                vm.remoteRevision = (!data.revision) ? 'n/a' : data.revision;
            });
        }

        /**
         * Sets local revision value.
         *
         * @param {Object} data
         */
        function setLocalRevision(data) {
            $scope.$apply(function() {
                vm.localRevision = (!data.revision) ? 'n/a' : data.revision;
            });
        }

        /**
         * Closes changed files.
         */
        function closeChangedFiles() {
            vm.changedFiles = {};
        }

        /**
         * Closes file diff box.
         */
        function closeDiff() {
            vm.diff = '';
        }

        /**
         * Fetches list of deployment logs.
         */
        function showDeploymentLogs() {
            deploymentsService.getDeploymentLogs(vm.deployment.id).then(function (data) {
                vm.deploymentLogs = data;
            }, function(reason) {
                console.log(reason);
            });
        }

        /**
         * "Unsets" list of deployment logs.
         */
        function closeDeploymentLogs() {
            vm.deploymentLogs = [];
        }
    }
})();
