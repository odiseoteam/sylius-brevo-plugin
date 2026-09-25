@syncing_customers_to_brevo
Feature: Syncing customers to Brevo contacts
    In order to reach my customers from Brevo
    As a Store Owner
    I want every customer to be a Brevo contact with its data

    Background:
        Given the store operates on a single channel in "United States"
        And the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the "United States" channel has the "contacts" Brevo module enabled
        And the Brevo account has every contact attribute

    @ui @registration
    Scenario: Registering creates the contact
        When I register with email "carrot@example.com" and password "sergeant"
        Then the Brevo contact "carrot@example.com" should have "FIRSTNAME" set to "Carrot"
        And the Brevo contact "carrot@example.com" should have "LASTNAME" set to "Ironfoundersson"
        And the Brevo contact "carrot@example.com" should have "CHANNEL" set to "WEB-US"
        And the Brevo contact "carrot@example.com" should be linked to its customer

    @ui @registration
    Scenario: Registering adds the contact to the customers list
        Given the "United States" channel adds its customers to the Brevo list 7
        When I register with email "carrot@example.com" and password "sergeant"
        Then the Brevo contact "carrot@example.com" should be in the list 7

    @ui @account
    Scenario: Editing the profile updates the contact
        Given I am a logged in customer
        When I want to modify my profile
        And I specify the first name as "Will"
        And I save my changes
        Then the Brevo contact "shop@example.com" should have "FIRSTNAME" set to "Will"

    @ui @admin
    Scenario: Changing the email keeps the same contact
        Given there is a customer "Mike Ross" with an email "ross@teammike.com"
        And I am logged in as an administrator
        When I want to edit this customer
        And I change their email to "mike@example.com"
        And I save my changes
        Then the Brevo contact of the customer "mike@example.com" should now have the email "mike@example.com"

    @ui @admin
    Scenario: Adding a customer without an account creates the contact
        Given I am logged in as an administrator
        When I want to create a new customer
        And I specify their email as "guest@example.com"
        And I add them
        Then the Brevo contact "guest@example.com" should be linked to its customer

    @ui @admin
    Scenario: Customers without an account are left out when guests are not synced
        Given the "United States" channel does not sync guest contacts
        And I am logged in as an administrator
        When I want to create a new customer
        And I specify their email as "guest@example.com"
        And I add them
        Then Brevo should not have received the contact "guest@example.com"

    @ui @guest_checkout
    Scenario: A guest checkout fills the contact from the billing address
        Given the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for Free
        And I added product "PHP T-Shirt" to the cart
        When I go to the checkout addressing step
        And I specify the email as "jon.snow@example.com"
        And I specify the billing address as "Ankh Morpork", "Frost Alley", "90210", "United States" for "Jon Snow"
        And I complete the addressing step
        Then the Brevo contact "jon.snow@example.com" should have "FIRSTNAME" set to "Jon"
        And the Brevo contact "jon.snow@example.com" should have "LASTNAME" set to "Snow"
        And the Brevo contact "jon.snow@example.com" should have "CITY" set to "Ankh Morpork"
        And the Brevo contact "jon.snow@example.com" should have "COUNTRY" set to "US"
