@syncing_products_to_brevo
Feature: Syncing products to Brevo
    In order to show my products in Brevo emails and automations
    As a Store Owner
    I want the products of my channel to be Brevo Ecommerce products

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "T-Shirt banana" priced at "$12.54"
        And the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the "United States" channel has the "catalog" Brevo module enabled
        And I am logged in as an administrator

    @ui
    Scenario: Changing a price updates the product
        When I want to modify the "T-Shirt banana" product
        And I change its price to $15.00 for "United States" channel
        And I save my changes
        Then the Brevo product "T_SHIRT_BANANA" should cost 15.00

    @ui
    Scenario: Disabling a product deletes it
        When I want to modify the "T-Shirt banana" product
        And I disable it
        And I save my changes
        Then the Brevo product "T_SHIRT_BANANA" should be deleted
