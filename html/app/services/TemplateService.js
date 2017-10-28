angular
    .module('productImporter.services')
    .factory('TemplateService', function($http) {
        return {
            getTemplates: function() {
                return $http.get('/templates');
            }
        }
    })
