@managing_brevo_configurations
Feature: Managing Brevo configurations
    In order to connect each channel to its Brevo account
    As an Administrator
    I want to configure Brevo per channel

    Background:
        Given the store operates on a single channel in "United States"
        And I am logged in as an administrator

    @ui
    Scenario: Configuring Brevo for a channel
        When I want to configure Brevo for a channel
        And I choose the "United States" channel
        And I set its API key to "xkeysib-secret"
        And I set its default sender to "Example Shop" with email "shop@example.com"
        And I add it
        Then I should be notified that it has been successfully created
        And I should see a Brevo configuration for the "United States" channel
        And the "United States" channel should use the Brevo API key "xkeysib-secret"
        And the "United States" channel should send from "Example Shop" <shop@example.com>

    @ui
    Scenario: Editing a configuration keeps the stored API key
        Given the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        When I want to modify the Brevo configuration of the "United States" channel
        Then the API key should not be shown
        And I should not be able to change its channel
        When I set its default sender to "New Shop" with email "new@example.com"
        And I save my changes
        Then I should be notified that it has been successfully edited
        And the "United States" channel should use the Brevo API key "xkeysib-secret"
        And the "United States" channel should send from "New Shop" <new@example.com>

    @ui
    Scenario: Replacing the API key
        Given the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        When I want to modify the Brevo configuration of the "United States" channel
        And I set its API key to "xkeysib-new"
        And I save my changes
        Then I should be notified that it has been successfully edited
        And the "United States" channel should use the Brevo API key "xkeysib-new"

    @ui
    Scenario: Enabling a module
        Given the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        When I want to modify the Brevo configuration of the "United States" channel
        And I enable the "Dummy module" module
        And I save my changes
        Then I should be notified that it has been successfully edited
        And the "United States" channel should have the "dummy" Brevo module enabled

    @ui
    Scenario: Trying to configure a channel twice
        Given the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        When I want to configure Brevo for a channel
        And I choose the "United States" channel
        And I add it
        Then I should be notified that this channel is already configured

    @ui
    Scenario: Trying to use an invalid sender email
        When I want to configure Brevo for a channel
        And I choose the "United States" channel
        And I set its default sender to "Example Shop" with email "not-an-email"
        And I add it
        Then I should be notified that the sender email is not valid

    @ui
    Scenario: Testing the connection
        Given the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the Brevo account is "shop@example.com" on the "free" plan
        When I want to modify the Brevo configuration of the "United States" channel
        And I test the connection
        Then I should be notified that I am connected to the Brevo account "shop@example.com" on the "free" plan

    @ui
    Scenario: Testing the connection with a rejected API key
        Given the "United States" channel has a Brevo configuration with the API key "xkeysib-wrong"
        And Brevo rejects the API key
        When I want to modify the Brevo configuration of the "United States" channel
        And I test the connection
        Then I should be notified that Brevo rejected the API key

    @ui
    Scenario: Choosing the customers list
        Given the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the Brevo account has the lists "Customers" and "Newsletter"
        When I want to modify the Brevo configuration of the "United States" channel
        And I choose "Customers" as its customers list
        And I save my changes
        Then I should be notified that it has been successfully edited
        And the "United States" channel should add its customers to the Brevo list "Customers"

    @ui
    Scenario: Setting up the newsletter
        Given the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the Brevo account has the lists "Customers" and "Newsletter"
        When I want to modify the Brevo configuration of the "United States" channel
        And I enable the "Contacts: customers as Brevo contacts" module
        And I enable the "Newsletter: Brevo list, shop form and API (needs Contacts)" module
        And I choose "Newsletter" as its newsletter list
        And I ask subscribers to confirm with the Brevo template 5
        And I save my changes
        Then I should be notified that it has been successfully edited
        And the "United States" channel should have the "newsletter" Brevo module enabled
        And the "United States" channel should have the Brevo newsletter list "Newsletter"
        And the "United States" channel should ask subscribers to confirm with the Brevo template 5

    @ui
    Scenario: Trying to enable the newsletter without contacts
        Given the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        When I want to modify the Brevo configuration of the "United States" channel
        And I enable the "Newsletter: Brevo list, shop form and API (needs Contacts)" module
        And I save my changes
        Then I should be notified that the Newsletter module needs the Contacts module

    @ui
    Scenario: Enabling the tracker fills its client key from Brevo
        Given the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And the Brevo account has the tracker client key "rdhr1ilkhrmf1nm1xjx7n1vz"
        When I want to modify the Brevo configuration of the "United States" channel
        And I enable the "Tracking: Brevo tracker in the shop (page views, visitors identified by email) and ecommerce events" module
        And I save my changes
        Then I should be notified that it has been successfully edited
        And the "United States" channel should use the Brevo tracker client key "rdhr1ilkhrmf1nm1xjx7n1vz"
