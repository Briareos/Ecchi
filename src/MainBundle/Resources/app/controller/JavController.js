(function ($scope, $sce, Api) {
    $scope.posts = [];
    $scope.error = null;
    $scope.older = false;
    $scope.newer = false;
    $scope.url = null;
    $scope.form = {
        search: '',
        page: 1
    };
    $scope.search = function (e) {
        $scope.error = null;
        $scope.submitting = true;
        Api.javSearch($scope.form.search, $scope.form.page)
            .then(function (response) {
                var data = response.data;
                angular.forEach(data.posts, function (post) {
                    post.raw = $sce.trustAsHtml(post.raw);
                });
                $scope.posts = data.posts;
                $scope.older = data.older;
                $scope.newer = data.newer;
                $scope.url = data.url;
            }, function (data) {
                $scope.error = data;
            })
            .finally(function () {
                $scope.submitting = false;
            });
    };
    $scope.downloadLink = function (post) {
        return Api.downloadLink(post.download, $scope.url, post.fullId)
    };
    $scope.goOlder = function () {
        if (!$scope.older) {
            return;
        }
        $scope.form.page++;
        $scope.search();
    };

    $scope.goNewer = function () {
        if (!$scope.newer) {
            return;
        }
        $scope.form.page = Math.max(1, $scope.form.page - 1);
        $scope.search();
    };
});
