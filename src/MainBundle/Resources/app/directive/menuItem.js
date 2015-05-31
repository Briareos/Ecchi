(function ($rootScope) {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            $rootScope.$on('$stateChangeSuccess', function (e, newState) {
                if (newState.name === attrs.uiSref) {
                    element.parent().addClass('active');
                } else {
                    element.parent().removeClass('active');
                }
            });
        }
    };
});