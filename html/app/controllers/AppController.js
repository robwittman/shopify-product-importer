angular
    .module('productImporter.controllers')
    .controller('AppController', function($scope, $rootScope, UserService, ShopService, $cookies, Auth, $location) {
        $rootScope.logout = function() {
            $cookies.remove('authorization');
            Auth.setUser(false);
            $location.path('/login');
            $rootScope.user = false;
        }
    })
;
