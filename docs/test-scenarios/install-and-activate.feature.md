Feature: Install and Activate Monei Plugin

  Background:
    Given WooCommerce is installed and activated

  Scenario: Install Monei plugin from package
    Given I have the Monei plugin package
    When I install the Monei plugin from the package
    Then the Monei plugin should be installed without errors
    And the Monei plugin should be activated

  Scenario: Update Monei plugin from previous version
    Given I have an older version of the Monei plugin installed
    When I update the Monei plugin to the latest version
    Then the Monei plugin should be updated without errors
    And the Monei plugin should be activated



  