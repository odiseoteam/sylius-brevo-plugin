@sending_lifecycle_events_to_brevo
Feature: Sending lifecycle events to Brevo
    In order to welcome new customers and follow up paid orders from Brevo
    As a Store Owner
    I want registrations and payments sent to Brevo as events

    Background:
        Given the store operates on a single channel in "United States"
        And the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the "United States" channel has the "tracking" Brevo module enabled

    @ui @registration
    Scenario: Registering in the shop
        When I register with email "carrot@example.com" and password "sergeant"
        Then Brevo should have received the "customer_registered" event for "carrot@example.com"
        And that event should have "first_name" set to "Carrot"

    @ui @registration
    Scenario: No event without the tracking module
        Given the "United States" channel has no Brevo tracking
        When I register with email "carrot@example.com" and password "sergeant"
        Then Brevo should not have received any "customer_registered" event

    @ui @admin
    Scenario: Marking an order as paid
        Given the store ships everywhere for Free
        And the store allows paying with "Cash on Delivery"
        And the store has a product "PHP T-Shirt" priced at "$20.00"
        And there is a customer "john@example.com" that placed an order "#00000001"
        And the customer bought 2 "PHP T-Shirt" products
        And the customer "John Doe" addressed it to "Seaside Fwy", "90802" "Los Angeles" in the "United States" with identical billing address
        And the customer chose "Free" shipping method with "Cash on Delivery" payment
        And I am logged in as an administrator
        And I am viewing the summary of the order "#00000001"
        When I mark this order as paid
        Then Brevo should have received the "order_paid" event for "john@example.com"
        And that event should have "order_id" set to "00000001"
        And that event should have 2 "PHP T-Shirt" at "20.00"
