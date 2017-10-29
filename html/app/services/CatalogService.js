angular
    .module('productImporter.services')
    .factory('CatalogService', function($http) {
        return {
            getCatalog: function() {
                return $http.get('/catalog');
            },
            getCatalogProduct: function(id) {
                return $http.get(`/catalog/${id}`);
            },
            createCatalogProduct: function(product) {
                return $http.post('/catalog', $.param(product));
            },
            updateCatalogProduct: function(productId, product) {
                return $http.put(`/catalog/${productId}`, $.param(product));
            },
            deleteCatalogProduct: function(productId) {
                return $http.delete(`/catalog/${productId}`);
            }
        }
    })
