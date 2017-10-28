angular
    .module('productImporter.services')
    .factory('ShopService', function($http) {
        var service = {};

        service.getShops = function() { return $http.get('/shops'); }
        service.getShop = function(id) { return $http.get(`/shops/${id}`); }
        service.createShop = function(shop) { return $http.post('/shops'); }
        service.updateShop = function(shopId, shop) { return $http.put(`/shops/${shopId}`, $.param(shop)); }

        return service;
    })
;
