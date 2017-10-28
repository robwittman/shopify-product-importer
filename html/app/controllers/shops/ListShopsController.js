angular
    .module('productImporter.controllers')
    .controller('ListShopsController', function($scope, $rootScope, ShopService) {
        $scope.shops = [];
        ShopService.getShops().then(function(response) {
            $scope.shops = response.data.shops;
        })
    })
