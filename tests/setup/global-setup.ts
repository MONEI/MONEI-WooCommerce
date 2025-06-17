// tests/setup/global-setup.ts
import { chromium, FullConfig } from '@playwright/test';
import { WordPressApiClient } from './wordpress-api-client';
import { TestDataManager } from './test-data-manager';

async function globalSetup(config: FullConfig) {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();

    const apiClient = new WordPressApiClient();
    const testDataManager = new TestDataManager(apiClient);
    const baseUrl = process.env.TESTSITE_URL || 'https://staging-site.ddev.site';

    // Authenticate as admin - use full URL
    await page.goto(`${baseUrl}/wp-admin`);
    await page.fill('#user_login', process.env.WP_ADMIN_USER || 'admin');
    await page.fill('#user_pass', process.env.WP_ADMIN_PASS || 'admin');
    await page.click('#wp-submit');

    // Save authentication state
    await context.storageState({ path: 'tests/auth/admin-state.json' });

    // test api client
    await apiClient.healthCheck();

    // Setup test data
     await testDataManager.setupTestProducts();
    // await testDataManager.setupProductsByType('simple');
    // await testDataManager.setupSubscriptionProducts('woocommerce');
    //await testDataManager.setupTestPages();
    //await testDataManager.setupMoneiPaymentMethods();

    await browser.close();
}

export default globalSetup;