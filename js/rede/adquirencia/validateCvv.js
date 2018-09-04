Validation.add(
    'validate-cvv-number', 'Please enter a valid credit card verification number.', function (v, elm) {
        return Validation.get('IsEmpty').test(v) || /^[0-9]{3,4}$/.test(v);
    }
);