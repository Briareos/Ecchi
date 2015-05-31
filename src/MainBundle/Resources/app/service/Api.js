(function ($http, API_BASE) {
    this.javSearch = function (term, page) {
        return $http.get(API_BASE + '/jav-search', {
            params: {
                search: term,
                page: page
            }
        });
    };
    this.maniaxSearch = function (term) {
        return $http.get(API_BASE + '/maniax-search', {
            params: {
                search: term
            }
        });
    };

    this.downloadLink = function (download, referer, name) {
        return API_BASE + '/jav-download?download=' + encodeURIComponent(download) + '&referer=' + encodeURIComponent(referer) + '&name=' + encodeURIComponent(name);
    };
});