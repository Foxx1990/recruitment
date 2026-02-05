Feature: Order Management
  In order to apply promotions and view order details
  As a customer
  I want to assign promotions to my order and see calculated totals

  Background:
    Given there is an order for user "First user" with product "first" quantity 2

  Scenario: Assign order-level promotion and view order details
    When a user assigns promotion "order_promotion" to order 1
    Then the order response should have status code 201
    And the order response should contain message "Promotion assigned to order successfully."
    When a user requests details of order 1
    Then the order response should have status code 200
    And the order response should contain 1 items
    And the order response itemsTotal should be 2066
    And the order response adjustmentsTotal should be -268
    And the order response total should be 1798
    And the first item should have discountedUnitPrice 899
    And the first item should have distributedOrderDiscountValue 134

  Scenario: Assign item-level promotion and view order details
    When a user assigns promotion "first_item_promotion" to order 1
    Then the order response should have status code 201
    And the order response should contain message "Promotion assigned to order successfully."
    When a user requests details of order 1
    Then the order response should have status code 200
    And the order response should contain 1 items
    And the order response itemsTotal should be 1758
    And the order response adjustmentsTotal should be 0
    And the order response total should be 1758
    And the first item should have discountedUnitPrice 879

  Scenario: Try to assign non-existent promotion to order
    When a user assigns promotion "non_existent_promotion" to order 1
    Then the order response should have status code 404
    And the order response should contain message "Promotion not found."

  Scenario: Try to assign promotion to non-existent order
    When a user assigns promotion "order_promotion" to order 999
    Then the order response should have status code 404
    And the order response should contain message "Order not found."
