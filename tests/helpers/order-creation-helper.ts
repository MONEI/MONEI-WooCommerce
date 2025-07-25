import { WordPressApiClient } from '../setup/wordpress-api-client';
import { ProductType } from '../fixtures/product-types';
import { UserType } from '../fixtures/user-types';

export interface OrderData {
    id: number;
    order_key: string;
    total: string;
    status: string;
    currency: string;
    date_created: string;
}

export class OrderCreationHelper {
    private apiClient: WordPressApiClient;
    private createdOrderIds: number[] = [];

    constructor(apiClient?: WordPressApiClient) {
        this.apiClient = apiClient || new WordPressApiClient();
    }

    /**
     * Create a WooCommerce order programmatically via REST API
     */
    async createOrder(options: {
        productType?: ProductType;
        userType?: UserType;
        paymentMethod?: string;
        status?: string;
        lineItems?: Array<{ sku: string; quantity: number }>;
        shippingMethod?: string;
        couponCodes?: string[];
    }): Promise<OrderData> {
        const {
            productType,
            userType,
            paymentMethod = 'monei',
            status = 'pending',
            lineItems,
            shippingMethod = 'flat_rate',
            couponCodes = []
        } = options;

        // Build line items
        const items = lineItems || (productType ? [{
            sku: productType.sku,
            quantity: 1
        }] : []);

        // Build order data
        const orderData = {
            payment_method: paymentMethod,
            payment_method_title: this.getPaymentMethodTitle(paymentMethod),
            set_paid: false,
            status: status,
            billing: userType ? {
                first_name: userType.firstName,
                last_name: userType.lastName,
                address_1: userType.address,
                address_2: '',
                city: userType.city,
                state: userType.state || '',
                postcode: userType.postcode,
                country: userType.country,
                email: userType.email,
                phone: userType.phone
            } : undefined,
            shipping: userType ? {
                first_name: userType.firstName,
                last_name: userType.lastName,
                address_1: userType.address,
                address_2: '',
                city: userType.city,
                state: userType.state || '',
                postcode: userType.postcode,
                country: userType.country
            } : undefined,
            line_items: items,
            shipping_lines: [
                {
                    method_id: shippingMethod,
                    method_title: this.getShippingMethodTitle(shippingMethod),
                    total: '10.00'
                }
            ],
            coupon_lines: couponCodes.map(code => ({ code }))
        };

        try {
            const response = await this.apiClient.wooCommerce.post('orders', orderData);
            const order = response.data;

            this.createdOrderIds.push(order.id);

            console.log(`📦 Created order #${order.id}`);
            console.log(`   Status: ${order.status}`);
            console.log(`   Total: ${order.currency_symbol}${order.total}`);
            console.log(`   Key: ${order.order_key}`);

            return {
                id: order.id,
                order_key: order.order_key,
                total: order.total,
                status: order.status,
                currency: order.currency,
                date_created: order.date_created
            };
        } catch (error) {
            console.error('Failed to create order:', error);
            throw error;
        }
    }

    /**
     * Create multiple orders for batch testing
     */
    async createBatchOrders(count: number, options: Parameters<typeof this.createOrder>[0]): Promise<OrderData[]> {
        const orders: OrderData[] = [];

        for (let i = 0; i < count; i++) {
            const order = await this.createOrder(options);
            orders.push(order);
        }

        return orders;
    }

    /**
     * Update order status
     */
    async updateOrderStatus(orderId: number, status: string): Promise<void> {
        await this.apiClient.wooCommerce.put(`orders/${orderId}`, { status });
        console.log(`📝 Updated order #${orderId} status to: ${status}`);
    }

    /**
     * Add a note to an order
     */
    async addOrderNote(orderId: number, note: string, customerNote: boolean = false): Promise<void> {
        await this.apiClient.wooCommerce.post(`orders/${orderId}/notes`, {
            note: note,
            customer_note: customerNote
        });
    }

    /**
     * Clean up all created orders
     */
    async cleanup(): Promise<void> {
        for (const orderId of this.createdOrderIds) {
            try {
                await this.apiClient.wooCommerce.delete(`orders/${orderId}`, { force: true });
                console.log(`✅ Cleaned up order #${orderId}`);
            } catch (error) {
                console.error(`Failed to delete order ${orderId}:`, error.message);
            }
        }
        this.createdOrderIds = [];
    }

    /**
     * Get the list of created order IDs (for manual cleanup if needed)
     */
    getCreatedOrderIds(): number[] {
        return [...this.createdOrderIds];
    }

    /**
     * Build the pay-order URL for a given order
     */
    static buildPayOrderUrl(orderId: number, orderKey: string): string {
        return `/checkout/order-pay/${orderId}/?pay_for_order=true&key=${orderKey}`;
    }

    private getPaymentMethodTitle(method: string): string {
        const titles: Record<string, string> = {
            'monei': 'MONEI',
            'monei-hosted': 'MONEI Hosted',
            'monei_paypal': 'PayPal via MONEI',
            'monei_bizum': 'Bizum via MONEI',
            'monei_multibanco': 'Multibanco via MONEI',
            'monei_mbway': 'MBWay via MONEI'
        };
        return titles[method] || method;
    }

    private getShippingMethodTitle(method: string): string {
        const titles: Record<string, string> = {
            'flat_rate': 'Flat Rate',
            'free_shipping': 'Free Shipping',
            'local_pickup': 'Local Pickup'
        };
        return titles[method] || method;
    }
}