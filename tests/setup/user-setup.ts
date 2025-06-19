import { Browser, BrowserContext, Page } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { WordPressApiClient } from './wordpress-api-client';

export interface TestUser {
    username: string;
    email: string;
    password: string;
    role: 'administrator' | 'customer' | 'shop_manager';
    displayName: string;
    firstName: string;
    lastName: string;
    country: string;
    state?: string;
    city: string;
    address: string;
    postcode: string;
    phone: string;
}

export const TEST_USERS: Record<string, TestUser> = {
    ADMIN: {
        username: 'admin',
        email: 'admin@test.local',
        password: process.env.WP_ADMIN_PASS || 'admin',
        role: 'administrator',
        displayName: 'Test Admin',
        firstName: 'Test',
        lastName: 'Admin',
        country: 'ES',
        state: 'Barcelona',
        city: 'Barcelona',
        address: 'Test Street 123',
        postcode: '08001',
        phone: '+34666777888'
    },
    ES_CUSTOMER: {
        username: 'es_customer',
        email: 'customer@test.local',
        password: 'customer123',
        role: 'customer',
        displayName: 'Spanish Customer',
        firstName: 'Carlos',
        lastName: 'García',
        country: 'ES',
        state: 'Madrid',
        city: 'Madrid',
        address: 'Calle Mayor 1',
        postcode: '28001',
        phone: '+34600123456'
    },
    PT_CUSTOMER: {
        username: 'pt_customer',
        email: 'portugal@test.local',
        password: 'customer123',
        role: 'customer',
        displayName: 'Portuguese Customer',
        firstName: 'João',
        lastName: 'Silva',
        country: 'PT',
        city: 'Lisboa',
        address: 'Rua da Liberdade 1',
        postcode: '1250-096',
        phone: '+351911234567'
    },
    US_CUSTOMER: {
        username: 'us_customer',
        email: 'usa@test.local',
        password: 'customer123',
        role: 'customer',
        displayName: 'US Customer',
        firstName: 'John',
        lastName: 'Smith',
        country: 'US',
        state: 'CA',
        city: 'Los Angeles',
        address: '123 Main St',
        postcode: '90210',
        phone: '+1555123456'
    }
};

export class UserAuthenticationManager {
    private apiClient: WordPressApiClient;
    private requestUtils: RequestUtils | null = null;
    private baseUrl: string;

    constructor(apiClient: WordPressApiClient, baseUrl: string) {
        this.apiClient = apiClient;
        this.baseUrl = baseUrl;
    }

    async initializeRequestUtils(requestContext: any): Promise<void> {
        this.requestUtils = new RequestUtils(requestContext, {
            storageStatePath: 'tests/auth/admin-state.json'
        });
        await this.requestUtils.setupRest();
    }

    /**
     * Setup all test users during global setup
     */
    async setupTestUsers(): Promise<void> {
        console.log('🧑‍💼 Setting up test users...');

        for (const [userKey, userData] of Object.entries(TEST_USERS)) {
            if (userKey === 'ADMIN') {
                // Skip admin as it should already exist
                console.log(`  ✅ Admin user exists: ${userData.username}`);
                continue;
            }

            await this.ensureUserExists(userData);
        }

        console.log('✅ Test users setup complete');
    }

    /**
     * Create user if it doesn't exist
     */
    private async ensureUserExists(userData: TestUser): Promise<void> {
        try {
            // Check if user exists via API
            const existingUser = await this.getUserByUsername(userData.username);

            if (!existingUser) {
                console.log(`  🆕 Creating user: ${userData.username}`);
                await this.createUser(userData);
            } else {
                console.log(`  ✅ User exists: ${userData.username} (ID: ${existingUser.id})`);
                // Optionally update user data
                await this.updateUserBillingInfo(existingUser.id, userData);
            }
        } catch (error) {
            console.error(`  ❌ Failed to setup user ${userData.username}:`, error.message);
        }
    }

    /**
     * Get user by username via WordPress API
     */
    private async getUserByUsername(username: string): Promise<any | null> {
        try {
            const response = await fetch(`${this.baseUrl}/wp-json/wp/v2/users?search=${username}`, {
                headers: {
                    'Authorization': `Basic ${Buffer.from(`${process.env.WORDPRESS_ADMIN_USER}:${process.env.WP_API_APP_PASSWORD || process.env.WORDPRESS_ADMIN_PASSWORD}`).toString('base64')}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const users = await response.json();
                return users.find((user: any) => user.slug === username) || null;
            }
            return null;
        } catch (error) {
            console.warn(`Could not fetch user ${username}:`, error.message);
            return null;
        }
    }

    /**
     * Create new user via WordPress API
     */
    private async createUser(userData: TestUser): Promise<any> {
        const userPayload = {
            username: userData.username,
            email: userData.email,
            password: userData.password,
            roles: [userData.role],
            first_name: userData.firstName,
            last_name: userData.lastName,
            name: userData.displayName
        };

        const response = await fetch(`${this.baseUrl}/wp-json/wp/v2/users`, {
            method: 'POST',
            headers: {
                'Authorization': `Basic ${Buffer.from(`${process.env.WORDPRESS_ADMIN_USER}:${process.env.WP_API_APP_PASSWORD || process.env.WORDPRESS_ADMIN_PASSWORD}`).toString('base64')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userPayload)
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Failed to create user ${userData.username}: ${errorText}`);
        }

        const user = await response.json();
        console.log(`    ✅ User created: ${user.name} (ID: ${user.id})`);

        // Set up billing information for WooCommerce
        await this.updateUserBillingInfo(user.id, userData);

        return user;
    }

    /**
     * Update user's WooCommerce billing information
     */
    private async updateUserBillingInfo(userId: number, userData: TestUser): Promise<void> {
        try {
            const customerData = {
                billing: {
                    first_name: userData.firstName,
                    last_name: userData.lastName,
                    email: userData.email,
                    phone: userData.phone,
                    country: userData.country,
                    state: userData.state || '',
                    city: userData.city,
                    address_1: userData.address,
                    postcode: userData.postcode
                },
                shipping: {
                    first_name: userData.firstName,
                    last_name: userData.lastName,
                    country: userData.country,
                    state: userData.state || '',
                    city: userData.city,
                    address_1: userData.address,
                    postcode: userData.postcode
                }
            };

            // Use WooCommerce API to update customer
            const response = await this.apiClient.wooCommerce.put(`customers/${userId}`, customerData);
            console.log(`    💳 Billing info updated for user ${userData.username}`);
        } catch (error) {
            console.warn(`    ⚠️  Could not update billing info for ${userData.username}:`, error.message);
        }
    }

    /**
     * Authenticate user and save storage state
     */
    async authenticateUser(browser: Browser, userKey: string): Promise<string> {
        const userData = TEST_USERS[userKey];
        if (!userData) {
            throw new Error(`User ${userKey} not found in TEST_USERS`);
        }

        console.log(`🔐 Authenticating user: ${userData.username}`);

        const context = await browser.newContext({
            baseURL: this.baseUrl
        });
        const page = await context.newPage();

        try {
            // Go to login page
            await page.goto('/wp-login.php');

            // Fill login form
            await page.fill('#user_login', userData.username);
            await page.fill('#user_pass', userData.password);
            await page.click('#wp-submit');

            // Wait for successful login (redirect to dashboard or profile)
            await page.waitForURL(url =>
                url.pathname.includes('/wp-admin/') ||
                url.pathname.includes('/my-account/') ||
                url.pathname === '/'
            );

            // Save authentication state
            const storageStatePath = `tests/auth/${userKey.toLowerCase()}-state.json`;
            await context.storageState({ path: storageStatePath });

            console.log(`  ✅ Authentication saved: ${storageStatePath}`);
            return storageStatePath;

        } catch (error) {
            throw new Error(`Failed to authenticate user ${userData.username}: ${error.message}`);
        } finally {
            await context.close();
        }
    }

    /**
     * Create all authentication states during global setup
     */
    async createAllAuthStates(browser: Browser): Promise<Record<string, string>> {
        const authStates: Record<string, string> = {};

        // Create guest state (no authentication)
        const guestContext = await browser.newContext({ baseURL: this.baseUrl });
        const guestPage = await guestContext.newPage();
        await guestPage.goto('/');
        const guestStatePath = 'tests/auth/guest-state.json';
        await guestContext.storageState({ path: guestStatePath });
        await guestContext.close();
        authStates['GUEST'] = guestStatePath;
        console.log('  ✅ Guest state saved');

        // Create authenticated states for each user
        for (const userKey of Object.keys(TEST_USERS)) {
            try {
                const statePath = await this.authenticateUser(browser, userKey);
                authStates[userKey] = statePath;
            } catch (error) {
                console.error(`  ❌ Failed to create auth state for ${userKey}:`, error.message);
                // Use guest state as fallback
                authStates[userKey] = guestStatePath;
            }
        }

        return authStates;
    }
}