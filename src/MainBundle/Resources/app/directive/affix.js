(function () {
    return function (scope, element, attr) {
        element.css('width', element.width());
        element.affix({
            offset: {
                top: attr.affixTop,
                bottom: attr.affixBottom
            }
        });
    };
});