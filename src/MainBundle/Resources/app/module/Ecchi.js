(function () {
    var app = angular.module('Ecchi', ['ui.router', 'ui.bootstrap']);

    app.config(function ($stateProvider, $urlRouterProvider) {
        $stateProvider
            .state('jav', {
                url: '/jav',
                controller: 'JavController',
                templateUrl: 'jav.html'
            })
            .state('maniax', {
                url: '/maniax',
                controller: 'ManiaxController',
                templateUrl: 'maniax.html'
            })
            .state('home', {
                redirectTo: 'jav'
            });

        $urlRouterProvider.when('', '/jav');
    });

    app.constant('API_BASE', document.getElementsByTagName('base')[0].href.slice(0, -1))
})();
