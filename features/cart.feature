Feature: Cart Management
  In order to buy products
  As a customer
  I want to add products to my cart

  Scenario: Successfully add a product to cart
    When a user adds product "first" with quantity 2 to cart
    Then the cart response should have status code 201
    And the cart response should contain message "Product added to cart successfully."

  Scenario: Fail to add non-existent product
    When a user adds product "non-existent" with quantity 1 to cart
    Then the cart response should have status code 404
    And the cart response should contain message "Product with code \"non-existent\" not found."

  Scenario: Fail to add more than allowed quantity
    When a user adds product "first" with quantity 51 to cart
    Then the cart response should have status code 422
    And the cart response should contain message "Total quantity in cart cannot exceed 50."

  Scenario: Fail to add more than allowed quantity of a single product
    When a user adds product "first" with quantity 21 to cart
    Then the cart response should have status code 422
    And the cart response should contain message "Quantity of a single product in cart cannot exceed 20."
