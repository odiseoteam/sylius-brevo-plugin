@syncing_orders_to_brevo
Feature: Syncing orders to Brevo
    In order to follow my sales and trigger post-purchase automations in Brevo
    As a Store Owner
    I want the completed orders of my channel to be Brevo Ecommerce orders

    Background:
        Given the store operates on a single channel in "United States"
        And the store ships everywhere for Free
        And the store allows paying with "Cash on Delivery"
        And the store has a product "PHP T-Shirt" priced at "$20.00"
        And the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the "United States" channel has the "orders" Brevo module enabled
        And there is a customer "john@example.com" that placed an order "#00000001"
        And the customer bought 2 "PHP T-Shirt" products
        And the customer "John Doe" addressed it to "Seaside Fwy", "90802" "Los Angeles" in the "United States" with identical billing address
        And the customer chose "Free" shipping method with "Cash on Delivery" payment
        And I am logged in as an administrator

    @ui
    Scenario: Marking an order as paid updates it
        Given I am viewing the summary of the order "#00000001"
        When I mark this order as paid
        Then the Brevo order "#00000001" should be "paid" with an amount of 40.00
        And the Brevo order "#00000001" should have 2 "PHP_T_SHIRT" at 20.00
        And the Brevo order "#00000001" should belong to "john@example.com"

    @ui
    Scenario: Cancelling an order updates it
        Given I am viewing the summary of the order "#00000001"
        When I cancel this order
        Then the Brevo order "#00000001" should be "cancelled" with an amount of 40.00
