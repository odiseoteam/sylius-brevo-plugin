@subscribing_to_the_newsletter
Feature: Subscribing to the newsletter
    In order to hear about the store
    As a Visitor or a Customer
    I want to subscribe to the newsletter kept in Brevo

    Background:
        Given the store operates on a single channel in "United States"
        And the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the "United States" channel has the "contacts" Brevo module enabled
        And the "United States" channel has the "newsletter" Brevo module enabled
        And the Brevo account has every contact attribute
        And the "United States" channel has the Brevo newsletter list 12

    @ui @shop
    Scenario: Subscribing from the footer
        When I subscribe to the newsletter with "rincewind@example.com"
        Then I should be notified that I am subscribed to the newsletter
        And the Brevo contact "rincewind@example.com" should be in the list 12

    @ui @shop
    Scenario: Subscribers are synced even when guests are not
        Given the "United States" channel does not sync guest contacts
        When I subscribe to the newsletter with "rincewind@example.com"
        Then the Brevo contact "rincewind@example.com" should be in the list 12

    @ui @shop
    Scenario: Bots filling the hidden field are ignored
        When a bot subscribes to the newsletter with "spam@example.com"
        Then I should be notified that I am subscribed to the newsletter
        And Brevo should not have received the contact "spam@example.com"

    @ui @shop
    Scenario: Confirming the subscription with double opt-in
        Given the "United States" channel asks subscribers to confirm with the Brevo template 5
        When I subscribe to the newsletter with "rincewind@example.com"
        Then I should be asked to confirm my newsletter subscription
        And Brevo should have emailed "rincewind@example.com" the template 5 to join the list 12
        And Brevo should not have received the contact "rincewind@example.com"
        When I follow the confirmation link of the Brevo email
        Then I should be notified that I am subscribed to the newsletter
        And the Brevo contact "rincewind@example.com" should be in the list 12

    @ui @account
    Scenario: Subscribing from the profile
        Given I am a logged in customer
        When I want to modify my profile
        And I subscribe to the newsletter
        And I save my changes
        Then the Brevo contact "shop@example.com" should be in the list 12

    @ui @account
    Scenario: Unsubscribing from the profile
        Given I am a logged in customer
        And I subscribed to the newsletter from my profile
        When I want to modify my profile
        And I unsubscribe from the newsletter
        And I save my changes
        Then the Brevo contact "shop@example.com" should have left the list 12

    @ui @account
    Scenario: A logged in customer needs no confirmation
        Given the "United States" channel asks subscribers to confirm with the Brevo template 5
        And I am a logged in customer
        When I subscribe to the newsletter with "shop@example.com"
        Then I should be notified that I am subscribed to the newsletter
        And the Brevo contact "shop@example.com" should be in the list 12
