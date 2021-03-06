@javascript
Feature: Filter products
  In order to filter products in the catalog
  As a regular user
  I need to be able to filter products with multiples number fields filters

  Background:
    Given the "default" catalog configuration
    And the following family:
      | code      |
      | furniture |
      | library   |
    And the following attributes:
      | code      | label     | type   | useable as grid filter |
      | component | Component | number | yes                    |
      | supplier  | Supplier  | number | yes                    |
    And the following "component" attribute options: Black and Green
    And the following "supplier" attribute options: Black and White and Red
    And the following products:
      | sku    | family    | supplier | component |
      | BOOK   | library   |          |           |
      | MUG-1  | furniture | 12       | 16        |
      | MUG-2  | furniture | 03       | 16        |
      | MUG-3  | furniture | 03       | 16        |
      | MUG-4  | furniture | 03       | 16        |
      | MUG-5  | furniture |          | 16        |
      | POST-1 | furniture | 03       |           |
      | POST-2 | furniture | 03       |           |
      | POST-3 | furniture | 01       |           |
    And the following product groups:
      | code   | label  | axis                | type    | products                          |
      | MUG    | Mug    | component, supplier | VARIANT | MUG-1, MUG-2, MUG-3, MUG-4, MUG-5 |
      | POSTIT | Postit | supplier            | X_SELL  | POST-1, POST-2, POST-3            |
      | EMPTY  | Empty  |                     | X_SELL  |                                   |
    And I am logged in as "Mary"
    And I am on the products page
    And I show the filter "Supplier"
    And I show the filter "Component"

  Scenario: Successfully filter products with the sames attributes
    Given I filter by "Supplier" with value "03"
    And I should be able to use the following filters:
      | filter    | value | result                 |
      | Component | empty | POST-1, POST-2         |
      | Component | > 16  |                        |
      | Component | < 16  |                        |
      | Component | > 15  | MUG-2, MUG-3 and MUG-4 |
      | Component | < 17  | MUG-2, MUG-3 and MUG-4 |
      | Component | >= 16 | MUG-2, MUG-3 and MUG-4 |
      | Component | <= 16 | MUG-2, MUG-3 and MUG-4 |
      | Component | = 16  | MUG-2, MUG-3 and MUG-4 |
      | Component | = 0   |                        |
      | Component | > 0   | MUG-2, MUG-3 and MUG-4 |
    And I hide the filter "Supplier"
    And I hide the filter "Component"

  Scenario: Successfully filter product without commons attributes
    Given I filter by "Component" with value "16"
    And I should be able to use the following filters:
      | filter   | value | result                        |
      | Supplier | empty | MUG-5                         |
      | Supplier | > 12  |                               |
      | Supplier | < 12  | MUG-2, MUG-3 and MUG-4        |
      | Supplier | > 11  | MUG-1                         |
      | Supplier | < 13  | MUG-1, MUG-2, MUG-3 and MUG-4 |
      | Supplier | >= 12 | MUG-1                         |
      | Supplier | <= 12 | MUG-1, MUG-2, MUG-3 and MUG-4 |
      | Supplier | = 12  | MUG-1                         |
      | Supplier | = 0   |                               |
      | Supplier | > 0   | MUG-1, MUG-2, MUG-3 and MUG-4 |
    And I hide the filter "Supplier"
    And I hide the filter "Component"
