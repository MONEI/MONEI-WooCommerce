Feature: Make Payment Transactions with Monei Plugin

  Background:
    Given the shop is ready to checkout

  Scenario: Successful transaction with credit card on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select credit card as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction

  Scenario: Failed transaction with credit card on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select credit card as the payment method
    And I attempt to complete the payment in the popup with the failure magic number
    Then the transaction fails

  Scenario: Pending transaction with credit card on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select credit card as the payment method
    And I attempt to complete the payment in the popup with the pending magic number
    Then the transaction is pending

  Scenario: Successful transaction with credit card on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select credit card as the payment method
    And I complete the payment on the hosted page with the success magic number
    Then the transaction is a successful transaction

  Scenario: Failed transaction with credit card on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select credit card as the payment method
    And I attempt to complete the payment on the hosted page with the failure magic number
    Then the transaction fails

  Scenario: Pending transaction with credit card on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select credit card as the payment method
    And I attempt to complete the payment on the hosted page with the pending magic number
    Then the transaction is pending

  Scenario: Successful transaction with bizum on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select bizum as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction

  Scenario: Failed transaction with bizum on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select bizum as the payment method
    And I attempt to complete the payment in the popup with the failure magic number
    Then the transaction fails

  Scenario: Pending transaction with bizum on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select bizum as the payment method
    And I attempt to complete the payment in the popup with the pending magic number
    Then the transaction is pending

  Scenario: Successful transaction with bizum on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select bizum as the payment method
    And I complete the payment on the hosted page with the success magic number
    Then the transaction is a successful transaction

  Scenario: Failed transaction with bizum on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select bizum as the payment method
    And I attempt to complete the payment on the hosted page with the failure magic number
    Then the transaction fails

  Scenario: Pending transaction with bizum on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select bizum as the payment method
    And I attempt to complete the payment on the hosted page with the pending magic number
    Then the transaction is pending

  Scenario: Successful transaction with cofidis on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select cofidis as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction

  Scenario: Failed transaction with cofidis on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select cofidis as the payment method
    And I attempt to complete the payment in the popup with the failure magic number
    Then the transaction fails

  Scenario: Pending transaction with cofidis on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select cofidis as the payment method
    And I attempt to complete the payment in the popup with the pending magic number
    Then the transaction is pending

  Scenario: Successful transaction with cofidis on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select cofidis as the payment method
    And I complete the payment on the hosted page with the success magic number
    Then the transaction is a successful transaction

  Scenario: Failed transaction with cofidis on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select cofidis as the payment method
    And I attempt to complete the payment on the hosted page with the failure magic number
    Then the transaction fails

  Scenario: Pending transaction with cofidis on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select cofidis as the payment method
    And I attempt to complete the payment on the hosted page with the pending magic number
    Then the transaction is pending

  Scenario: Successful transaction with paypal on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select paypal as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction

  Scenario: Failed transaction with paypal on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select paypal as the payment method
    And I attempt to complete the payment in the popup with the failure magic number
    Then the transaction fails

  Scenario: Pending transaction with paypal on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select paypal as the payment method
    And I attempt to complete the payment in the popup with the pending magic number
    Then the transaction is pending

  Scenario: Successful transaction with paypal on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select paypal as the payment method
    And I complete the payment on the hosted page with the success magic number
    Then the transaction is a successful transaction

  Scenario: Failed transaction with paypal on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select paypal as the payment method
    And I attempt to complete the payment on the hosted page with the failure magic number
    Then the transaction fails

  Scenario: Pending transaction with paypal on WooCommerce shortcode checkout
    Given I visit the WooCommerce shortcode checkout
    When I select paypal as the payment method
    And I attempt to complete the payment on the hosted page with the pending magic number
    Then the transaction is pending

