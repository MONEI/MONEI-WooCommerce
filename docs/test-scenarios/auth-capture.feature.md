Feature: Process and Capture Authorized Orders with Monei Plugin

  Background:
    Given the shop is ready to checkout
    And the Monei plugin is onboarded
    And the Pre-Authorize setting is enabled

  Scenario: Authorize a payment with credit card on WooCommerce block checkout
    Given I visit the WooCommerce block checkout
    When I select credit card as the payment method
    And I complete the payment in the popup with the success magic number
    Then the transaction is a successful transaction
    And the order status should be on hold

  Scenario: Capture an authorized payment by changing order status to Completed
    Given the order status is on hold
    When I change the order status to Completed
    Then the transaction is a successful transaction
    And the order status should be Completed

  Scenario: Capture an authorized payment by changing order status to Processing
    Given the order status is on hold
    When I change the order status to Processing
    Then the transaction is a successful transaction
    And the order status should be Processing

  Scenario: Cancel an authorized payment by changing order status to Cancelled
    Given the order status is on hold
    When I change the order status to Cancelled
    Then the transaction fails
    And the order status should be Cancelled

  Scenario: Refund an authorized payment by changing order status to Refunded
    Given the order status is on hold
    When I change the order status to Refunded
    Then the transaction fails
    And the order status should be Refunded
