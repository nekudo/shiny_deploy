(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('DeploymentsController', DeploymentsController);

    DeploymentsController.$inject = ['deploymentsService', 'alertsService'];

    function DeploymentsController(deploymentsService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        vm.deployments = null;

        vm.getDeployments = getDeployments;
        vm.deleteDeployment = deleteDeployment;

        init();

        /**
         * Loads data required for deployments list view.
         */
        function init() {
            var promise = deploymentsService.getDeployments();
            promise.then(function(data) {
                vm.deployments = data;
            }, function(reason) {
                console.log('Error fetching deployments: ' + reason);
            });
        }

        /**
         * Returns list of deployments.
         *
         * @returns {null|Array}
         */
        function getDeployments() {
            return vm.deployments;
        }

        /**
         * Removes a deployment.
         *
         * @param {number} deploymentId
         */
        function deleteDeployment(deploymentId) {
            deploymentsService.deleteDeployment(deploymentId).then(function (data) {
                for (var i = vm.deployments.length - 1; i >= 0; i--) {
                    if (vm.deployments[i].id === deploymentId) {
                        vm.deployments.splice(i, 1);
                        break;
                    }
                }
                alertsService.pushAlert('Deployment successfully deleted.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }
    }
})();



(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('DeploymentsAddController', DeploymentsAddController);

    DeploymentsAddController.$inject = ['$location', 'deploymentsService', 'alertsService'];

    function DeploymentsAddController($location, deploymentsService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        // Properties
        vm.isAdd = true;
        vm.deployment = {};
        vm.servers = {};
        vm.repositories = {};
        vm.branches = {};
        vm.task = {};
        vm.taskFormMode = 'add';

        // Methods
        vm.addDeployment = addDeployment;
        vm.refreshBranches = refreshBranches;
        vm.showAddTask = showAddTask;
        vm.showEditTask = showEditTask;
        vm.addTask = addTask;
        vm.editTask = editTask;
        vm.deleteTask = deleteTask;

        // Init
        init();

        /**
         * Loads data required for add deployment form.
         */
        function init() {
            // load servers:
            deploymentsService.getServers().then(function (data) {
                vm.servers = data;
            }, function(reason) {
                console.log('Error fetching servers: ' + reason);
            });

            // load repositories:
            deploymentsService.getRepositories().then(function (data) {
                vm.repositories = data;
            }, function(reason) {
                console.log('Error fetching repositories: ' + reason);
            });
        }

        /**
         * Requests add-deployment action on project backend.
         */
        function addDeployment() {
            vm.deployment.server_id = vm.deployment.server.id;
            vm.deployment.repository_id = vm.deployment.repository.id;
            vm.deployment.branch = vm.deployment.branchObj.id;
            deploymentsService.addDeployment(vm.deployment).then(function(data) {
                $location.path('/deployments');
                alertsService.queueAlert('Deployment successfully added.', 'success');
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }

        /**
         * Refresh branches list if repository is changed.
         */
        function refreshBranches() {
            deploymentsService.getRepositoryBranches(vm.deployment.repository.id).then(function (data) {
                vm.branches = data;
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }

        /**
         * Shows form to add new task.
         */
        function showAddTask() {
            vm.taskFormMode = 'add';
            vm.task = {};
            jQuery('#editTaskModal').modal('show');
        }

        /**
         * Shows form to edit a task.
         *
         * @param {Number} index
         */
        function showEditTask(index) {
            vm.task = vm.deployment.tasks[index];
            vm.taskFormMode = 'edit';
            jQuery('#editTaskModal').modal('show');
        }

        /**
         * Adds new task.
         */
        function addTask() {
            if (!vm.deployment.hasOwnProperty('tasks')) {
                vm.deployment.tasks = [];
            }
            vm.deployment.tasks.push(vm.task);
            vm.task = {};
            jQuery('#editTaskModal').modal('hide');
        }

        /**
         * Updates an existing task.
         */
        function editTask() {
            vm.task = {};
            jQuery('#editTaskModal').modal('hide');
        }

        /**
         * Deletes a task.
         *
         * @param {Number} index
         */
        function deleteTask(index) {
            vm.deployment.tasks.splice(index, 1);
        }
    }
})();



(function () {
    "use strict";

    angular
        .module('shinyDeploy')
        .controller('DeploymentsEditController', DeploymentsEditController);

    DeploymentsEditController.$inject = ['$location', '$routeParams', 'deploymentsService', 'alertsService'];

    function DeploymentsEditController($location, $routeParams, deploymentsService, alertsService) {
        /*jshint validthis: true */
        var vm = this;

        // Properties
        vm.isEdit = true;
        vm.servers = {};
        vm.repositories = {};
        vm.branches = {};
        vm.deployment = {};
        vm.task = {};
        vm.taskFormMode = 'add';

        // Methods
        vm.updateDeployment = updateDeployment;
        vm.refreshBranches = refreshBranches;
        vm.showAddTask = showAddTask;
        vm.showEditTask = showEditTask;
        vm.addTask = addTask;
        vm.editTask = editTask;
        vm.deleteTask = deleteTask;

        // Init
        init();

        /**
         * Loads data required for edit deployment form.
         */
        function init() {
            // load servers:
            deploymentsService.getServers().then(function (data) {
                vm.servers = data;
            }, function(reason) {
                console.log('Error fetching servers: ' + reason);
            });

            // load repositories:
            deploymentsService.getRepositories().then(function (data) {
                vm.repositories = data;
            }, function(reason) {
                console.log('Error fetching repositories: ' + reason);
            });

            // load deployment:
            var deploymentId = ($routeParams.deploymentId) ? parseInt($routeParams.deploymentId) : 0;
            deploymentsService.getDeploymentData(deploymentId).then(function(data) {
                vm.deployment = data;
                vm.refreshBranches();
            }, function(reason) {
                $location.path('/deployments');
            });
        }

        /**
         * Updates deployment data.
         */
        function updateDeployment() {
            vm.deployment.server_id = vm.deployment.server.id;
            vm.deployment.repository_id = vm.deployment.repository.id;
            vm.deployment.branch = vm.deployment.branchObj.id;
            deploymentsService.updateDeployment(vm.deployment).then(function (data) {
                alertsService.pushAlert('Deployment successfully updated.', 'success');
            }, function (reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }

        /**
         * Refresh branches list if repository is changed.
         */
        function refreshBranches() {
            deploymentsService.getRepositoryBranches(vm.deployment.repository.id).then(function (data) {
                vm.branches = data;
            }, function(reason) {
                alertsService.pushAlert(reason, 'warning');
            });
        }

        /**
         * Shows form to add new task.
         */
        function showAddTask() {
            vm.taskFormMode = 'add';
            vm.task = {};
            jQuery('#editTaskModal').modal('show');
        }

        /**
         * Shows form to edit a task.
         *
         * @param {Number} index
         */
        function showEditTask(index) {
            vm.task = vm.deployment.tasks[index];
            vm.taskFormMode = 'edit';
            jQuery('#editTaskModal').modal('show');
        }

        /**
         * Adds new task.
         */
        function addTask() {
            if (!vm.deployment.hasOwnProperty('tasks')) {
                vm.deployment.tasks = [];
            }
            vm.deployment.tasks.push(vm.task);
            vm.task = {};
            jQuery('#editTaskModal').modal('hide');
        }

        /**
         * Updates an existing task.
         */
        function editTask() {
            vm.task = {};
            jQuery('#editTaskModal').modal('hide');
        }

        /**
         * Deletes a task.
         *
         * @param {Number} index
         */
        function deleteTask(index) {
            vm.deployment.tasks.splice(index, 1);
        }
    }
})();



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

        // Methods
        vm.triggerDeploy = triggerDeploy;
        vm.triggerGetChangedFiles = triggerGetChangedFiles;
        vm.displayChangedFiles = displayChangedFiles;
        vm.showDiff = showDiff;

        // Init
        init();
        vm.displayChangedFiles();

        /**
         * Loads data required for run deployment view.
         */
        function init() {
            // load deployment:
            var deploymentId = ($routeParams.deploymentId) ? parseInt($routeParams.deploymentId) : 0;
            deploymentsService.getDeploymentData(deploymentId).then(function (data) {
                vm.deployment = data;
                resolveRelations();
                getLocalRevision();
                getRemoteRevision();

            }, function(reason) {
                $location.path('/deployments');
            });
        }

        /**
         * Start new deployment job.
         */
        function triggerDeploy() {
            vm.changedFiles = {};
            vm.diff = '';
            deploymentsService.triggerDeployAction(vm.deployment.id);
        }

        /**
         * Fetch list of changed files.
         */
        function triggerGetChangedFiles() {
            vm.changedFiles = {};
            vm.diff = '';
            deploymentsService.triggerGetChangedFiles(vm.deployment.id);
        }

        function displayChangedFiles() {
            ws.addListener('updateChangedFiles', function(data) {
                $scope.$apply(function() {
                    vm.changedFiles = data.changedFiles;
                });
            });
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
         * Renders git diff output as html.
         *
         * @param {number} fileKey
         */
        function showDiff(fileKey) {
            vm.diff = $sce.trustAsHtml(Diff2Html.getPrettyHtmlFromDiff(vm.changedFiles[fileKey].diff));
        }

        /**
         * Fetches current revision of remote repository.
         *
         */
        function getRemoteRevision() {
            deploymentsService.getRemoteRevision(vm.deployment.id).then(function(revision) {
                vm.remoteRevision = revision;
            });
        }

        /**
         * Fetches local repository revision.
         */
        function getLocalRevision() {
            deploymentsService.getLocalRevision(vm.deployment.id).then(function(revision) {
                vm.localRevision = revision;
            });
        }
    }
})();