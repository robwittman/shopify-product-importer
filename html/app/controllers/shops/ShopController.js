angular
    .module('productImporter.controllers')
    .controller('ShopController', function($scope, $rootScope, $routeParams, ShopService) {
        $scope.shopId = $routeParams.shopId;
        $scope.shop = {};

        ShopService.getShop($scope.shopId).then(function(response) {
            $scope.shop = response.data.shop;
        })
    })
