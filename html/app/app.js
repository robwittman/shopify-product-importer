angular
    .module('productImporter', [
        'ngAnimate',
        'ngRoute',
        'productImporter.controllers',
        'productImporter.services',
        'ngCookies',
        'angular-jwt',
        'ngFileUpload',
        'toaster'
    ])
    .config(function($routeProvider, $httpProvider) {
        var $cookies;
    angular.injector(['ngCookies']).invoke(['$cookies', function(_$cookies_) {
      $cookies = _$cookies_;
    }]);
    $routeProvider
        .when('/shops', {
            controller: 'ListShopsController',
            templateUrl: 'views/shops/index.html',
        })
        .when('/shops/new', {
            controller: "NewShopController",
            templateUrl: 'views/shops/new.html'
        })
        .when('/shops/:shopId', {
            controller: "ShopController",
            templateUrl: 'views/shops/show.html'
        })
        .when('/users', {
            controller: 'ListUsersController',
            templateUrl: 'views/users/index.html',
        })
        .when('/users/new', {
            controller: "NewUserController",
            templateUrl: 'views/users/new.html'
        })
        .when('/users/:userId', {
            controller: "UserController",
            templateUrl: 'views/users/show.html'
        })
        .when('/products', {
            controller: 'ProductController',
            templateUrl: 'views/products/new.html'
        })
        .when('/login', {
            controller: "LoginController",
            templateUrl: 'views/auth/login.html'
        })
        .when('/queue', {
            controller: "QueueController",
            templateUrl: 'views/queue.html'
        })
        .otherwise({redirectTo: '/products'});

        var authToken = $cookies.get('authorization');
        $httpProvider.defaults.headers.post = {'Content-Type' :'application/x-www-form-urlencoded', 'Authorization': 'Bearer '+authToken};
    	$httpProvider.defaults.headers.get = {'Content-Type' :'application/x-www-form-urlencoded', 'Authorization': 'Bearer '+authToken};
    }).run(function($rootScope, $location, Auth, $cookies, jwtHelper) {
        if ($cookies.get('authorization')) {
            console.log("Authorization cookie found");
            var auth = $cookies.get('authorization');
            var payload = jwtHelper.decodeToken(auth);
            Auth.setUser(payload.user);
            $rootScope.user = {
                username: payload.name,
                is_admin: payload.admin
            }
            if ($location.path() === '/login') {
                return $location.path('/products');
            }
        }
        $rootScope.$on('$routeChangeStart', function(event) {
            // Ignore login
            if ($location.path() === '/login') {
                return;
            }
            if (!Auth.isLoggedIn()) {
                console.log("Unauthorized user not allowed");
                $location.path('/login');
            } else {
                console.log("User authorized. They may proceed");
            }
        })
    });
