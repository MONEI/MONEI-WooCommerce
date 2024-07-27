Feature: Process Subscriptions with Monei Plugin

  Background:
    Given the shop is ready to checkout
    And the subscription plugin is configured
    And a subscription product is in the cart

  Scenario: Successful subscription transaction with credit card on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select credit card as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction
    And the subscription should be active

  Scenario: Failed subscription transaction with credit card on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select credit card as the payment method
    And I attempt to complete the payment in the popup with the failure magic number
    Then the transaction fails
    And the subscription should be inactive

  Scenario: Renew subscription manually with credit card on WooCommerce Order Details Page
    Given I visit the WooCommerce Order Details Page
    When I select the action renew subscription manually
    And click the button "Renew Subscription"
    Then I see a success message
    And the subscription should be renewed

  Scenario: Modify subscription payment method by the merchant
    Given the Merchant is logged in
    And there is an active subscription
    And I visit the WooCommerce Order Details Page of the subscription
    When I select a new payment method for the subscription
    Then the subscription payment method should be updated
    When I click the button "Renew Subscription"
    Then I see a success message
    And the subscription should be renewed with the new payment method

  Scenario: Modify subscription payment method by the user
    Given the User is logged in
    And I visit the WooCommerce My Account Page
    When I select a new payment method for the subscription
    Then the subscription payment method should be updated
    When I login as the Merchant
    And I visit the WooCommerce Order Details Page of the subscription
    Then the subscription payment method should be updated
    When I click the button "Renew Subscription"
    Then I see a success message
    And the subscription should be renewed with the new payment method

