(function () {
    "use strict";
    angular
        .module('shinyDeploy')
        .directive('changedFileStatus', changedFileStatus);

    function changedFileStatus() {

        return {
            restrict: 'C',
            template: '<span class="label {{ itemTypeClass }}">{{ itemTypeName }}</span>',
            scope: {
                itemType: '=type'
            },
            replace: true,

            link: function($scope, element, attributes) {
                switch ($scope.itemType) {
                    case 'A':
                        $scope.itemTypeClass = 'label-success';
                        $scope.itemTypeName = 'Added';
                        break;
                    case 'C':
                        $scope.itemTypeClass = 'label-info';
                        $scope.itemTypeName = 'Copied';
                        break;
                    case 'D':
                        $scope.itemTypeClass = 'label-danger';
                        $scope.itemTypeName = 'Deleted';
                        break;
                    case 'M':
                        $scope.itemTypeClass = 'label-primary';
                        $scope.itemTypeName = 'Modified';
                        break;
                    case 'R':
                        $scope.itemTypeClass = 'label-info';
                        $scope.itemTypeName = 'Renamed';
                        break;

                }
                $scope.typeClass = ' label-primary';
            }
        };

    }

}());