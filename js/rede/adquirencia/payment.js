const ValidateCC = Class.create();
ValidateCC.prototype = {
    initialize: function (element) {
        this.cardNumber = element.value.replace(/[ -]/g, '');
        //this.brandsPatterns = getBrandsPatterns();
    },

    validate: function () {
        const cardType = getCardType(this.cardNumber);
        return this.isValidLuhn() && isValidCcLength(this.cardNumber, cardType);
    },
    
    isValidLuhn: function () {
        if (this.cardNumber == null) {
            return false;
        }

        let tmp, sum = 0;
        const limite = this.cardNumber.length - 1;
        let posicao = 1;

        for (let indexInvertido = limite; indexInvertido >= 0; indexInvertido--, posicao++) {
            tmp = parseInt(this.cardNumber.charAt(indexInvertido));

            if (posicao % 2 == 0) {
                tmp *= 2;

                if (tmp >= 10) {
                    tmp -= 9;
                }
            }

            sum += tmp;
        }

        return sum % 10 == 0;
    }
};

function getCardType(cardNumber) {
    const brandsPatterns = getBrandsPatterns();

    for (let i = 0; i < brandsPatterns.length; i++)
    {
        if (cardNumber.match(brandsPatterns[i].pattern)) {
            return brandsPatterns[i];
        }
    }

    return null;
}

function isValidCcLength(cardNumber, cardType) {
    if (!cardNumber || !cardType) {
        return false;
    }
    
    for(let i = 0; i < cardType.valid_length.length; i++) {
        if (cardType.valid_length[i] == cardNumber.length) {
            return true;
        }
    }

    return false;
}

function getCharCode(e) {
    let charCode = null;
    if (window.event) {
        charCode = window.event.keyCode;
    } else if (e) {
        charCode = e.which;
    }

    return charCode;
}

function isAllowedCharCode(charCode) {
    if (charCode == null || charCode == 0 || charCode == 8 || charCode == 9 || charCode == 13 || charCode == 27) {
        return true;
    }

    return false;
}

function isNumber(e) {
    const charCode = getCharCode(e);

    if (isAllowedCharCode(charCode)) {
        return true;
    }

    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }

    return true;
}

function isAlphabetic(e) {
    const charCode = getCharCode(e);

    if (isAllowedCharCode(charCode)) {
        return true;
    }

    const strKey = String.fromCharCode(charCode);
    if (/^[a-zA-Z ]$/.test(strKey)) {
        return true;
    }

    return false;
}

function formatCc(element, brandCode) {
    const v = element.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');

    if (brandCode === 'DC') {
        element.value = [v.slice(0, 4), v.slice(4, 10), v.slice(10)].join(' ');
    }
    else {
        const parts = [];
        for (i = 0, len = v.length; i < len; i += 4) {
            parts.push(v.slice(i, i + 4));
        }
        if (parts.length) {
            element.value = parts.join(' ');
        }
    }
}

function unformatCc(element) {
    element.value = element.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
}

function getBrandsPatterns() {
    const patterns = [
        {
            others: 'any',
            pattern: /^\d{13,19}$/,
            valid_length: [13, 14, 15, 16, 17, 18, 19]
        }
    ];

    return patterns;
}

function getBrandsImages() {
    const src = [
        {
            name: 'rede',
            colorSrc: 'rede.png',
            wbSrc: 'rede.png',
            code: 'REDE'
        }, {
            name: 'jcb',
            colorSrc: 'jcb.png',
            wbSrc: 'jcb.png',
            code: 'JCB'
        },
        {
            name: 'elo',
            colorSrc: 'elo.png',
            wbSrc: 'elo.png',
            code: 'ELO'
        },
        {
            name: 'amex',
            colorSrc: 'amex.png',
            wbSrc: 'amex.png',
            code: 'AMX'
        },
        {
            name: 'visa',
            colorSrc: 'visa.png',
            wbSrc: 'visa.png',
            code: 'VI'
        },
        {
            name: 'mastercard',
            colorSrc: 'mastercard.png',
            wbSrc: 'mastercard.png',
            code: 'MC'
        },
        {
            name: 'hipercard',
            colorSrc: 'hipercard.png',
            wbSrc: 'hipercard.png',
            code: 'HPC'
        },
        {
            name: 'hiper',
            colorSrc: 'hiper.png',
            wbSrc: 'hiper.png',
            code: 'HP'
        },
        {
            name: 'diners',
            colorSrc: 'diners.png',
            wbSrc: 'diners.png',
            code: 'DC'
        },
        {
            name: 'credz',
            colorSrc: 'credz.png',
            wbSrc: 'credz.png',
            code: 'CZ'
        }
    ];

    return src;
}
