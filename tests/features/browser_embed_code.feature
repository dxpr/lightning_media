@lightning @lightning_media @api @errors
Feature: Creating media assets from within the media browser using embed codes

  @2c43f38c
  Scenario Outline: Creating media assets from within the media browser using embed codes
    Given I am logged in as a user with the "access media_browser entity browser pages, access media overview, create media" permissions
    When I visit "/entity-browser/iframe/media_browser"
    And I press the "Create embed" button
    And I enter "<embed_code>" for "input"
    And I press the "Update" button
    And I enter "<title>" for "Name"
    And I press "Place"
    And I visit "/admin/content/media"
    Then I should see "<title>"

    Examples:
      | embed_code                                             | title                            |
      | https://www.youtube.com/watch?v=zQ1_IbFFbzA            | The Pill Scene                   |
      | https://vimeo.com/14782834                             | Cache Rules Everything Around Me |
      | https://twitter.com/webchick/status/672110599497617408 | angie speaks                     |
      | https://www.instagram.com/p/jAH6MNINJG                 | Drupal Does LSD                  |

  @abfaddda
  Scenario: Embed code widget should require input
    Given I am logged in as a user with the "access media_browser entity browser pages" permission
    When I visit "/entity-browser/iframe/media_browser"
    And I press the "Create embed" button
    And I press "Place"
    Then I should see the error message "You must enter a URL or embed code."

  @0fa271df
  Scenario: Embed code widget should ensure that input can be matched to a media bundle
    Given I am logged in as a user with the "access media_browser entity browser pages" permission
    When I visit "/entity-browser/iframe/media_browser"
    And I press the "Create embed" button
    And I enter "The quick brown fox gets eaten by hungry lions." for "input"
    And I press the "Update" button
    And I press "Place"
    Then I should see the error message containing "Could not match any bundles to input:"

  @security @6a9aaf7f
  Scenario: Embed code widget will not allow the user to create media of bundles to which they do not have access
    Given I am logged in as a user with the "access media_browser entity browser pages" permission
    When I visit "/entity-browser/iframe/media_browser"
    And I press the "Create embed" button
    And I enter "https://twitter.com/webchick/status/824051274353999872" for "input"
    And I press the "Update" button
    Then the "#entity" element should be empty
