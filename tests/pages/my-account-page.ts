import { Page } from '@playwright/test';

export class MyAccountPage {
    readonly page: Page;

    // Locators
    readonly loginUsernameInput = '#username';
    readonly loginPasswordInput = '#password';
    readonly loginButton = 'button[name="login"]';
    readonly logoutLink = '.woocommerce-MyAccount-navigation-link--customer-logout a';
    readonly ordersLink = '.woocommerce-MyAccount-navigation-link--orders a';
    readonly addressesLink = '.woocommerce-MyAccount-navigation-link--edit-address a';
    readonly paymentMethodsLink = '.woocommerce-MyAccount-navigation-link--payment-methods a';
    readonly accountDetailsLink = '.woocommerce-MyAccount-navigation-link--edit-account a';
    readonly savedCardsSection = '.woocommerce-SavedPaymentMethods-tokens';
    readonly addPaymentMethodButton = '.woocommerce-MyAccount-content a.button[href*="add-payment-method"]';

    constructor(page: Page) {
        this.page = page;
    }

    async navigateToMyAccount() {
        await this.page.goto('/my-account/');
        await this.page.waitForSelector('.woocommerce-MyAccount-navigation');
    }

    async login(username: string, password: string) {
        await this.page.fill(this.loginUsernameInput, username);
        await this.page.fill(this.loginPasswordInput, password);
        await this.page.click(this.loginButton);
        await this.page.waitForSelector('.woocommerce-MyAccount-navigation');
    }

    async logout() {
        await this.page.click(this.logoutLink);
        await this.page.waitForSelector('.woocommerce-account');
    }

    async navigateToOrders() {
        await this.page.click(this.ordersLink);
        await this.page.waitForSelector('.woocommerce-orders-table');
    }

    async navigateToAddresses() {
        await this.page.click(this.addressesLink);
        await this.page.waitForSelector('.woocommerce-Addresses');
    }

    async navigateToPaymentMethods() {
        await this.page.click(this.paymentMethodsLink);
        await this.page.waitForSelector('.woocommerce-MyAccount-paymentMethods');
    }

    async navigateToAccountDetails() {
        await this.page.click(this.accountDetailsLink);
        await this.page.waitForSelector('.woocommerce-EditAccountForm');
    }

    async getSavedCards() {
        const savedCards = await this.page.$$(`${this.savedCardsSection} li`);
        return savedCards.length;
    }

    async addNewPaymentMethod() {
        await this.page.click(this.addPaymentMethodButton);
        await this.page.waitForSelector('#add_payment_method');
    }

    async deletePaymentMethod(tokenId: string) {
        await this.page.click(`a.delete[data-token="${tokenId}"]`);
        await this.page.waitForSelector('.woocommerce-notices-wrapper');
    }

    async updateAccountDetails(details: {
        firstName?: string;
        lastName?: string;
        email?: string;
        currentPassword?: string;
        newPassword?: string;
        confirmNewPassword?: string;
    }) {
        if (details.firstName) await this.page.fill('#account_first_name', details.firstName);
        if (details.lastName) await this.page.fill('#account_last_name', details.lastName);
        if (details.email) await this.page.fill('#account_email', details.email);
        if (details.currentPassword) await this.page.fill('#password_current', details.currentPassword);
        if (details.newPassword) await this.page.fill('#password_1', details.newPassword);
        if (details.confirmNewPassword) await this.page.fill('#password_2', details.confirmNewPassword);

        await this.page.click('button[name="save_account_details"]');
        await this.page.waitForSelector('.woocommerce-message');
    }
}
