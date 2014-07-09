/**
 * Created by vadym on 6/13/14.
 */

'use strict';

app_module.controller('inlineCrud', ['$scope','$http','Requirement', function ($scope,$http,Requirement) {

    $scope.reqv = {};
    $scope.form = app_module.base_url + 'js/ng/quotes/one/templates/form.html';
    $scope.crud = app_module.base_url + 'js/ng/quotes/one/templates/crud.html';
    $scope.Requirement = Requirement;
    $scope.requirements = Requirement.requirements;
    $scope.actionButtonSet = {};

    $scope.$on( 'reqv.update', function( event, args ) {
        $scope.reqv = args;
    });
    $scope.$on( 'requirements.update', function( event ) {
        $scope.requirements = Requirement.requirements;
    });
}])
;