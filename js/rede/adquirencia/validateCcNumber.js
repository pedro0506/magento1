Validation.add(
    'validate-cc-number', 'Please enter a valid credit card number.', function (v, elm) {
        const validateCC = new ValidateCC(elm);
        return Validation.get('IsEmpty').test(v) || validateCC.validate();
    }
);