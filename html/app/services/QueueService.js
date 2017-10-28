angular
    .module('productImporter.services')
    .factory('QueueService', function($http) {
        return {
            getQueue: function(params) {
                return $http.get('/queue', { params: params});
            }
        }
    })
