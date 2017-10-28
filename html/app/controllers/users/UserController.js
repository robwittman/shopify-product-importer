angular
    .module('productImporter.controllers')
    .controller('UserController', function($rootScope, $scope, UserService, $routeParams) {
        $scope.user = {};
        UserService.getUser($routeParams.userId).then(function(response) {
            $scope.user = response.data.user;
        })
    })
;
