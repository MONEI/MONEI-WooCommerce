Feature: Monei Plugin Assertions and Preconditions

  Scenario: Successful transaction assertions
    Given I complete a successful transaction
    Then I should land on the order success page
    And the order should be created in the order page
    And the status of the order should be processing
    And there should be a success notice

  Scenario: Failed transaction assertions
    Given I complete a failed transaction
    Then I should land on the payment page
    And the order should be created in the order page
    And the status of the order should be on-hold
    And there should be a failure notice

  Scenario: Pending transaction assertions
    Given I complete a pending transaction
    Then I should land on the order success page
    And the order should be updated in the order page
    And the status of the order should be processing
    And there should be a success notice

  Scenario: Onboard Monei plugin - precondition
    Given the Monei plugin is active
    And WooCommerce is active
    And I visit the Monei settings page
    And I enter the API credentials
