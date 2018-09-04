Validation.add(
    'validate-cpf-number', 'Please enter a valid CPF number.', function (v, elm) {
        const validateCPF = new ValidateCPF(elm);
        return Validation.get('IsEmpty').test(v) || validateCPF.validate();
    }
);