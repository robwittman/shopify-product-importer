angular
    .module('productImporter.services')
    .factory('UserService', function($http) {
        var service = {};

        service.login = function(username, password) {
            return $http.post('/auth/login', $.param({
                email: username,
                password: password
            }))
        }
        service.getUsers = function() {
            return $http.get('/users');
        }
        service.getUser = function(id) {
            return $http.get(`/users/${id}`);
        }
        return service;
    })
;
