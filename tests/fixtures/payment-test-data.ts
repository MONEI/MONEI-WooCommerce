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
            phoneNumber: '+3450000000000'
        },
        fail: {
            phoneNumber: '+3450000000000'
        }
    },
    // Add other payment methods as needed
};