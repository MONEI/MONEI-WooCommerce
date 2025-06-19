import { chromium, FullConfig } from '@playwright/test';
import { WordPressApiClient } from './wordpress-api-client';
import { TestDataManager } from './test-data-manager';
import { UserAuthenticationManager } from './user-setup';

async function globalSetup(config: FullConfig) {
    const browser = await chromium.launch();
    const baseUrl = process.env.TESTSITE_URL || 'https://staging-site.ddev.site';

    const apiClient = new WordPressApiClient();
    const testDataManager = new TestDataManager(apiClient);
    const userManager = new UserAuthenticationManager(apiClient, baseUrl);

    try {
        await apiClient.healthCheck();

        await testDataManager.setupTestProducts();
        await testDataManager.setupTestPages();
        await testDataManager.setupMoneiPaymentMethods();

        await userManager.setupTestUsers();

        const authStates = await userManager.createAllAuthStates(browser);

        console.log('🎉 Global setup complete!');
        console.log('Available auth states:', Object.keys(authStates));

    } catch (error) {
        console.error('💥 Global setup failed:', error.message);
        throw error;
    } finally {
        await browser.close();
    }
}

export default globalSetup;