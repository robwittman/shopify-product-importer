angular
    .module('productImporter.controllers')
    .controller('ListUsersController', function($rootScope, $scope, UserService) {
        $scope.users = [];
        UserService.getUsers().then(function(response) {
            $scope.users = response.data.users;
        })
    })
;
