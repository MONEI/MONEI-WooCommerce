/**
 * Represents the configuration for a checkout type
 */
export interface CheckoutType {
    id: string;
    name: string;
    url: string;
    isBlockCheckout: boolean;
}

/**
 * Available checkout types in WooCommerce
 */
export const CHECKOUT_TYPES: Record<string, CheckoutType> = {
    // Standard WooCommerce checkout
    CLASSIC: {
        id: 'classic',
        name: 'Classic Checkout',
        url: '/checkout-shortcode/',
        isBlockCheckout: false
    },

    // WooCommerce Blocks checkout
    BLOCK: {
        id: 'block',
        name: 'Block Checkout',
        url: '/checkout/',
        isBlockCheckout: true
    },

    // Pay for Order page (for failed payment recovery)
    PAY_FOR_ORDER: {
        id: 'pay_for_order',
        name: 'Pay for Order',
        url: '/checkout/order-pay/',
        isBlockCheckout: false
    }
};