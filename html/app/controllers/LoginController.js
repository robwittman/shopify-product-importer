angular
    .module('productImporter.controllers')
    .controller('LoginController', function($scope, $rootScope, $location, Auth, UserService, jwtHelper, $cookies) {
        $scope.username = '';
        $scope.password = '';

        $scope.login = function() {
            UserService.login($scope.username, $scope.password).then(function(response) {
                $cookies.put('authorization', response.data.access_token);
                var payload = jwtHelper.decodeToken(response.data.access_token);
                Auth.setUser(payload.user);
                $rootScope.user = payload.user;
                $location.path('/products');
            })
        }
    })
