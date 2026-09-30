@syncing_taxons_to_brevo_categories
Feature: Syncing taxons to Brevo categories
    In order to use my catalog in Brevo automations and emails
    As a Store Owner
    I want the taxons of my channel to be Brevo Ecommerce categories

    Background:
        Given the store operates on a single channel in "United States"
        And the store has "Category" taxonomy
        And the "Category" taxon has children taxons "T-Shirts" and "Caps"
        And channel "United States" has menu taxon "Category"
        And the "United States" channel has a Brevo configuration with the API key "xkeysib-secret"
        And I am logged in as an administrator

    @ui @taxons
    Scenario: Renaming a taxon updates its category
        Given the "United States" channel has the "catalog" Brevo module enabled
        When I want to modify the "T-Shirts" taxon
        And I rename it to "Shirts" in "English (United States)"
        And I save my changes
        Then the Brevo category "t_shirts" should be named "Shirts"
        And the Brevo category "t_shirts" should link to the "category/t-shirts" taxon page

    @ui @taxons
    Scenario: Renaming a parent taxon renames the categories of its children
        Given the "United States" channel has the "catalog" Brevo module enabled
        And the "Caps" taxon has children taxons "Men" and "Women"
        When I want to modify the "Caps" taxon
        And I rename it to "Hats" in "English (United States)"
        And I save my changes
        Then the Brevo category "men" should be named "Hats > Men"
        And the Brevo category "women" should be named "Hats > Women"

    @ui @taxons
    Scenario: Disabling a taxon deletes its category
        Given the "United States" channel has the "catalog" Brevo module enabled
        When I want to modify the "Caps" taxon
        And I disable it
        And I save my changes
        Then the Brevo category "caps" should be deleted

    @ui @taxons
    Scenario: Nothing is sent without the catalog module
        When I want to modify the "T-Shirts" taxon
        And I rename it to "Shirts" in "English (United States)"
        And I save my changes
        Then Brevo should not have received any category

    @ui @configuration
    Scenario: Turning the catalog module on activates Brevo Ecommerce
        Given Brevo Ecommerce is not activated yet
        When I want to modify the Brevo configuration of the "United States" channel
        And I enable the "Catalog: products and taxons as Brevo Ecommerce products and categories" module
        And I save my changes
        Then Brevo Ecommerce should be activated showing amounts in "USD"
