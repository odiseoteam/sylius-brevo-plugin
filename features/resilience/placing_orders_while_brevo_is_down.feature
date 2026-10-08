@placing_orders_with_brevo
Feature: Placing orders while Brevo is down
    In order to never lose a sale because of Brevo
    As a Customer
    I want to complete my checkout whatever Brevo answers

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for Free
        And the store allows paying Offline
        And the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the "United States" channel has the "dummy" Brevo module enabled

    @ui
    Scenario: Placing an order sends it to Brevo
        Given I added product "PHP T-Shirt" to the cart
        And I complete addressing step with email "vimes@example.com" and "United States" based billing address
        And I proceed with "Free" shipping method and "Offline" payment
        When I confirm my order
        Then I should see the thank you page
        And Brevo should have received the order

    @ui
    Scenario: Placing an order while Brevo is down
        Given Brevo is down
        And I added product "PHP T-Shirt" to the cart
        And I complete addressing step with email "vimes@example.com" and "United States" based billing address
        And I proceed with "Free" shipping method and "Offline" payment
        When I confirm my order
        Then I should see the thank you page
        And Brevo should have received the order
