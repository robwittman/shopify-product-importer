angular
    .module('productImporter.controllers')
    .controller('NewShopController', function($scope, $rootScope, ShopService) {
        $scope.shop = {
            myshopify_domain: '',
            api_key: '',
            password: '',
            shared_secret: '',
            description: ''
        }

        $scope.submit = function() {
            ShopService.createShop($scope.shop).then(function(response) {
                alert('Shop successfully created');
                $location.path(`/shops/${response.data.shop.id}`);
            })
        }
    })
;
