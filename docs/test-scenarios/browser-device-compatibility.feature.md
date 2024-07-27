Feature: Browser and Device Compatibility with Monei Plugin

  Background:
    Given the shop is ready to checkout
    And the Monei plugin is onboarded

  Scenario: Successful transaction with credit card on WooCommerce block checkout on Chrome
    Given I visit the WooCommerce block checkout on Chrome
    When I select credit card as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction

  Scenario: Successful transaction with credit card on WooCommerce shortcode checkout on Chrome
    Given I visit the WooCommerce shortcode checkout on Chrome
    When I select credit card as the payment method
    And I complete the payment on the hosted page with the success magic number
    Then the transaction is a successful transaction

  Scenario: Successful transaction with credit card on WooCommerce block checkout on Firefox
    Given I visit the WooCommerce block checkout on Firefox
    When I select credit card as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction

  Scenario: Successful transaction with credit card on WooCommerce shortcode checkout on Firefox
    Given I visit the WooCommerce shortcode checkout on Firefox
    When I select credit card as the payment method
    And I complete the payment on the hosted page with the success magic number
    Then the transaction is a successful transaction

  Scenario: Successful transaction with credit card on WooCommerce block checkout on Safari
    Given I visit the WooCommerce block checkout on Safari
    When I select credit card as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction

  Scenario: Successful transaction with credit card on WooCommerce shortcode checkout on Safari
    Given I visit the WooCommerce shortcode checkout on Safari
    When I select credit card as the payment method
    And I complete the payment on the hosted page with the success magic number
    Then the transaction is a successful transaction

  Scenario: Successful transaction with credit card on WooCommerce block checkout on Edge
    Given I visit the WooCommerce block checkout on Edge
    When I select credit card as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction

  Scenario: Successful transaction with credit card on WooCommerce shortcode checkout on Edge
    Given I visit the WooCommerce shortcode checkout on Edge
    When I select credit card as the payment method
    And I complete the payment on the hosted page with the success magic number
    Then the transaction is a successful transaction

  Scenario: Successful transaction with credit card on WooCommerce block checkout on mobile device
    Given I visit the WooCommerce block checkout on mobile device
    When I select credit card as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction

  Scenario: Successful transaction with credit card on WooCommerce shortcode checkout on mobile device
    Given I visit the WooCommerce shortcode checkout on mobile device
    When I select credit card as the payment method
    And I complete the payment on the hosted page with the success magic number
    Then the transaction is a successful transaction

  Scenario: View settings page of credit card payment method on mobile device
    Given I visit the settings page of the credit card payment method on mobile device
    Then I should see the settings page according to the design
