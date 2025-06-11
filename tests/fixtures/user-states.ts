/**
 * Represents the configuration for a user state
 */
export interface UserState {
    id: string;
    name: string;
    isLoggedIn: boolean;
    hasSavedPaymentMethods: boolean;
    username?: string;
    password?: string;
}

/**
 * Available user states for testing
 */
export const USER_STATES: Record<string, UserState> = {
    // Guest checkout
    GUEST: {
        id: 'guest',
        name: 'Guest User',
        isLoggedIn: false,
        hasSavedPaymentMethods: false
    },

    // Logged-in user without saved payment methods
    LOGGED_IN: {
        id: 'logged_in',
        name: 'Logged-in User',
        isLoggedIn: true,
        hasSavedPaymentMethods: false,
        username: 'testuser',
        password: 'testpassword'
    },

    // Returning customer with saved payment methods
    RETURNING_WITH_SAVED_PAYMENT: {
        id: 'returning_with_saved_payment',
        name: 'Returning Customer with Saved Payment',
        isLoggedIn: true,
        hasSavedPaymentMethods: true,
        username: 'returninguser',
        password: 'returningpassword'
    }
};