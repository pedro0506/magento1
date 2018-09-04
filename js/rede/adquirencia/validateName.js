Validation.add(
    'validate-name', 'First and Last Name invalid.', function (v) {
        return Validation.get('IsEmpty').test(v) || /(?=^[A-Z]+ [A-Z ]+$)/.test(v);
    }
);