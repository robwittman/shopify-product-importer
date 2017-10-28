angular
    .module('productImporter.controllers')
    .controller('QueueController', function($scope, $rootScope, QueueService) {
        $scope.params = {};
        QueueService.getQueue($scope.params).then(function(response) {
            $scope.queue = response.data.queue;
        })
    })
