@tracking_visitors_with_brevo
Feature: Tracking visitors with Brevo
    In order to follow what my visitors do and who they are in Brevo
    As a Store Owner
    I want the Brevo tracker on my shop pages

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for Free
        And the store allows paying Offline
        And the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the "United States" channel has the "tracking" Brevo module enabled
        And the "United States" channel has the Brevo tracker client key "rdhr1ilkhrmf1nm1xjx7n1vz"

    @ui
    Scenario: Browsing the shop loads the tracker
        When I visit the store
        Then the Brevo tracker should be loaded with the client key "rdhr1ilkhrmf1nm1xjx7n1vz"
        And the Brevo tracker should not identify anyone

    @ui
    Scenario: No tracker without the tracking module
        Given the "United States" channel has no Brevo tracking
        When I visit the store
        Then the Brevo tracker should not be loaded

    @ui
    Scenario: Signing in identifies the customer once
        Given there is a user "vimes@example.com" identified by "sylius"
        When I sign in with email "vimes@example.com" and password "sylius"
        Then the Brevo tracker should identify "vimes@example.com" as that customer
        When I visit the store
        Then the Brevo tracker should not identify anyone

    @ui @guest_checkout
    Scenario: A guest giving their email in the checkout is identified
        Given I have product "PHP T-Shirt" in the cart
        When I complete addressing step with email "detritus@example.com" and "United States" based billing address
        Then the Brevo tracker should identify "detritus@example.com"
