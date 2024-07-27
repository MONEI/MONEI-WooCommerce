Feature: Configure Settings with Monei Plugin

  Background:
    Given the shop is ready to configure settings
    And the Monei plugin is onboarded

  Scenario: Verify settings fields for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I select credit card as the payment method
    Then I should see the field "Enable/Disable"
    And I should see the field "Use Redirect Flow"
    And I should see the field "Apple Pay / Google Pay"
    And I should see the field "Test mode"
    And I should see the field "Title"
    And I should see the field "Description"
    And I should see the field "Hide Logo"
    And I should see the field "Account ID"
    And I should see the field "API Key"
    And I should see the field "Saved cards"
    And I should see the field "Pre-Authorize"
    And I should see the field "What to do after payment?"
    And I should see the field "Debug Log"

  Scenario: Enable credit card payment method
    Given I navigate to the Monei plugin settings page
    When I enable the "Enable/Disable" field
    Then the credit card payment method should be enabled
    And I should see the credit card payment method in the checkout

  Scenario: Use Redirect Flow for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I enable the "Use Redirect Flow" field
    Then the customer should be redirected to the Hosted Payment Page
    And I should see the Hosted Payment Page during checkout

  Scenario: Enable Apple Pay / Google Pay for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I enable the "Apple Pay / Google Pay" field
    Then the customer should see the Apple Pay or Google Pay button
    And I should see the Apple Pay or Google Pay button in the checkout

  Scenario: Enable Test mode for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I enable the "Test mode" field
    Then the payment gateway should be in test mode
    And I should be able to make a test transaction

  Scenario: Set Title for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I set the "Title" field to "Credit Card Payment"
    Then the title should be "Credit Card Payment"
    And I should see "Credit Card Payment" as the payment method title in the checkout

  Scenario: Set Description for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I set the "Description" field to "Pay with your credit card"
    Then the description should be "Pay with your credit card"
    And I should see "Pay with your credit card" as the payment method description in the checkout

  Scenario: Hide Logo for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I enable the "Hide Logo" field
    Then the payment method logo should be hidden
    And I should not see the payment method logo in the checkout

  Scenario: Set Account ID for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I set the "Account ID" field to "test_account_id"
    Then the account ID should be "test_account_id"
    And the transactions should be associated with "test_account_id"

  Scenario: Set API Key for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I set the "API Key" field to "test_api_key"
    Then the API key should be "test_api_key"
    And the transactions should use "test_api_key"

  Scenario: Enable Saved cards for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I enable the "Saved cards" field
    Then the customer should be able to pay via saved cards
    And I should see the option to use saved cards in the checkout

  Scenario: Enable Pre-Authorize for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I enable the "Pre-Authorize" field
    Then the payment should be pre-authorized
    And I should see the pre-authorization in the transaction details

  Scenario: Set action after payment for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I set the "What to do after payment?" field to "Complete order"
    Then the action after payment should be "Complete order"
    And the order should be completed after the payment

  Scenario: Enable Debug Log for credit card payment method
    Given I navigate to the Monei plugin settings page
    When I enable the "Debug Log" field
    Then the Monei events should be logged inside WooCommerce > Status > Logs > Select MONEI Logs
    And I should see the Monei events in the logs
