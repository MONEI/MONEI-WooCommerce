Feature: Process Refunds with Monei Plugin

  Background:
    Given the shop is ready to checkout
    And the Monei plugin is onboarded
    And the Merchant is logged in

  Scenario: Process a full refund for a successful transaction
    Given I visit the WooCommerce Order Received Page
    And I select the order to refund
    When I process a full refund
    Then the transaction is a successful transaction
    And the order should be marked as refunded

  Scenario: Process a partial refund for a successful transaction
    Given I visit the WooCommerce Order Received Page
    And I select the order to refund
    When I process a partial refund
    Then the transaction is a successful transaction
    And the order status will not change

  Scenario: Process a full refund for a failed transaction
    Given I visit the WooCommerce Order Received Page
    And I select the order to refund
    When I process a full refund
    Then the refund is not processed
    And the order status will not change

  Scenario: Process a partial refund for a failed transaction
    Given I visit the WooCommerce Order Received Page
    And I select the order to refund
    When I process a partial refund
    Then the refund is not processed
    And the order status will not change
