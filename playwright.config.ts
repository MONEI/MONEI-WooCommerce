import { defineConfig, devices } from '@playwright/test';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
import * as dotenv from 'dotenv';
import * as path from 'path';
dotenv.config({ path: path.resolve(__dirname, '.env') });

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  globalSetup: require.resolve('./tests/setup/global-setup'),
  testDir: './tests',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    baseURL: process.env.TESTSITE_URL,
    trace: 'on-first-retry',
  },

  projects: [
    {
      name: 'setup',
      testMatch: /global\.setup\.ts/,
    },
    {
      name: 'transaction-tests',
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'] },
      testMatch: '**/payment-gateway-matrix.spec.ts',
    },
    {
      name: 'api-key-tests',
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'] },
      testMatch: '**/monei-settings-api.spec.ts',
      workers: 1
    },
    {
      name: 'pay-order-tests',
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'] },
      testMatch: '**/pay-order-gateway-tests.spec.ts',
    },
    {
      name: 'cc-vaulting-tests',
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'] },
      testMatch: '**/cc-vaulting-transaction.spec.ts',
    },
    {
      name: 'google-transaction-tests',
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'] },
      testMatch: '**/google-transaction.spec.ts',
    },
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'npm run start',
  //   url: 'http://localhost:3000',
  //   reuseExistingServer: !process.env.CI,
  // },
});
