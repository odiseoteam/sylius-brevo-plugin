@sending_ecommerce_events_to_brevo
Feature: Sending ecommerce events to Brevo
    In order to follow up browsing and abandoned carts from Brevo
    As a Store Owner
    I want what my visitors view and buy sent to Brevo as events

    Background:
        Given the store operates on a single channel in "United States"
        And the store classifies its products as "T-Shirts"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And this product belongs to "T-Shirts"
        And the store ships everywhere for Free
        And the store allows paying Offline
        And the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the "United States" channel has the "tracking" Brevo module enabled
        And the "United States" channel has the Brevo tracker client key "rdhr1ilkhrmf1nm1xjx7n1vz"

    @ui
    Scenario: Viewing a product
        When I view product "PHP T-Shirt"
        Then the Brevo tracker should track "product_viewed" with "name" set to "PHP T-Shirt"
        And the Brevo tracker should track "product_viewed" with "price" set to "19.99"
        And the Brevo tracker should not view it in Brevo Ecommerce

    @ui
    Scenario: Viewing a product and a taxon of the synced catalog
        Given the "United States" channel has the "catalog" Brevo module enabled
        When I view product "PHP T-Shirt"
        Then the Brevo tracker should view the product "PHP T-Shirt" in Brevo Ecommerce
        When I browse products from taxon "T-Shirts"
        Then the Brevo tracker should view the taxon "T-Shirts" in Brevo Ecommerce

    @ui
    Scenario: Browsing a taxon
        When I browse products from taxon "T-Shirts"
        Then the Brevo tracker should track "category_viewed" with "name" set to "T-Shirts"

    @ui
    Scenario: No events without the tracking module
        Given the "United States" channel has no Brevo tracking
        When I view product "PHP T-Shirt"
        Then the Brevo tracker should not track "product_viewed"

    @ui @guest_checkout
    Scenario: A cart is sent once it has an email
        Given I added product "PHP T-Shirt" to the cart
        Then Brevo should not have received any "cart_updated" event
        When I complete addressing step with email "cheery@example.com" and "United States" based billing address
        Then Brevo should have received the "cart_updated" event for "cheery@example.com"
        And that event should have 1 "PHP T-Shirt" at "19.99"

    @ui @guest_checkout
    Scenario: Emptying the cart
        Given I added product "PHP T-Shirt" to the cart
        And I addressed the cart with email "cheery@example.com"
        When I removed product "PHP T-Shirt" from the cart
        Then Brevo should have received the "cart_deleted" event for "cheery@example.com"

    @ui @guest_checkout
    Scenario: Completing the checkout
        Given I added product "PHP T-Shirt" to the cart
        And I complete addressing step with email "cheery@example.com" and "United States" based billing address
        And I proceed with "Free" shipping method and "Offline" payment
        When I confirm my order
        Then Brevo should have received the "order_completed" event for "cheery@example.com"
        And that event should have 1 "PHP T-Shirt" at "19.99"
