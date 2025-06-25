export const PAYMENT_TEST_DATA = {
    creditCard: {
        success: {
            cardNumber: '4444444444444422',
            expiry: '12/34',
            cvc: '123',
            cardholderName: 'John Doe'
        },
        threeDSecure: {
            cardNumber: '4444444444444406',
            expiry: '12/34',
            cvc: '123',
            cardholderName: 'John Doe'
        },
        fail: {
            cardNumber: '4444444444444406',
            expiry: '12/34',
            cvc: '123',
            cardholderName: 'John Doe'
        }
    },
    bizum: {
        success: {
            phoneNumber: '+34500000000'
        },
        fail: {
            phoneNumber: '+34500000000'
        }
    },
    paypal: {
        success: {
            user: 'paypal-personal@monei.net',
            pw: 'monei12345'
        },
        fail: {
            user: 'CCREJECT-REFUSED@paypal.com',
            pw: 'PayPal2016'
        }
    }
};