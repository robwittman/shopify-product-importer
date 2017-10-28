angular
    .module('productImporter.controllers')
    .controller('NewUserController', function($rootScope, $scope, UserService) {
        $scope.user = {
            email: '',
            role: 'publisher',
            password: '',
            confirm: ''
        }

        $scope.submit = function() {
            if ($scope.user.password == '') {
                alert('Invalid password');
                return;
            }
            if ($scope.user.password !== $scope.user.confirm) {
                alert("Passwords do not match");
                return;
            }
            UserService.createUser($scope.user).then(function(response) {
                alert("User created!");
                $scope.user = {
                    email: '',
                    role: '',
                    password: '',
                    confirm: ''
                }
                $location.path(`/users/${response.data.id}`);
            })
        }
    })
;
