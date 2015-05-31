(function ($scope, Api) {
    $scope.items = [];
    $scope.error = null;
    $scope.submitting = false;
    $scope.form = {
        search: ''
    };

    $scope.search = function () {
        $scope.error = null;
        $scope.submitting = true;
        Api.maniaxSearch($scope.form.search)
            .then(function (response) {
                $scope.items = response.data.items;
            }, function (data) {
                $scope.error = data;
            })
            .finally(function () {
                $scope.submitting = false;
            });
    };
});