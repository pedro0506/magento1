Validation.addAllThese(
    [
        ['validate-empty-spaces', 'No spaces are allowed.', function (v) {
            return Validation.get('IsEmpty').test(v) || !/^.*\s.*$/.test(v);
        }]
    ]
);
