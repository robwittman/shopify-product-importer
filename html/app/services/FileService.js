angular
    .module('productImporter.services')
    .factory('FileService', function($http) {
        return {
            getFile: function(hash) {
                return $http.get(`/files/${hash}`);
            }
        }
    })
