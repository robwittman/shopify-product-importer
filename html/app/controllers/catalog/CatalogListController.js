angular
    .module('productImporter.controllers')
    .controller('CatalogListController', function($scope, $rootScope, CatalogService, toaster) {
        $scope.catalog = [];
        CatalogService.getCatalog().then(function(response) {
            $scope.catalog = response.data.products;
        })
    })
