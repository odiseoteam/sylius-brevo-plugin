@warning_about_missing_exchange_rates
Feature: Warning about missing exchange rates
    In order to have right amounts in Brevo
    As an Administrator
    I want to know which channels sharing a Brevo account need an exchange rate

    Background:
        Given the store operates on a channel named "Web" in "USD" currency
        And the store also operates on another channel named "Mobile" in "GBP" currency
        And the "Web" channel has a Brevo configuration with the API key "xkeysib-shared"
        And the "Web" channel has the "orders" Brevo module enabled
        And the "Mobile" channel has a Brevo configuration with the API key "xkeysib-shared"
        And the "Mobile" channel has the "catalog" Brevo module enabled
        And I am logged in as an administrator

    @ui
    Scenario: Being warned about a channel in another currency without exchange rate
        When I want to modify the Brevo configuration of the "Web" channel
        Then I should be warned that the "Mobile" channel needs an exchange rate from "GBP" to "USD"

    @ui
    Scenario: No warning once the exchange rate exists
        Given the exchange rate of "British Pound" to "US Dollar" is 1.3
        When I want to modify the Brevo configuration of the "Web" channel
        Then I should not be warned about missing exchange rates
