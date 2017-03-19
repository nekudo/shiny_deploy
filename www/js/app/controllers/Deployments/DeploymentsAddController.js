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
