@mod @mod_bigbluebuttonbn
Feature: Prepopulate breakout rooms allows meeting breakouts to be generated using group roster.

  Background:
    Given a BigBlueButton mock server is configured
    And I enable "bigbluebuttonbn" "mod" plugin
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Test Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity        | name                       | intro                           | course | idnumber         | type | recordings_imported | breakoutlimit |
      | bigbluebuttonbn | RoomRecordings             | Test Room Recording description | C1     | bigbluebuttonbn1 | 0    | 0                   | 0             |
      | bigbluebuttonbn | RoomRecordingsWithbreakout | Test Room with breakout         | C1     | bigbluebuttonbn1 | 0    | 0                   | 1             |

    @javascript
    Scenario: I should see breakout settings on the module form
        Given the following config values are set as admin:
        | bigbluebuttonbn_guestaccess_enabled | 1 |
        When I am on the "RoomRecordings" "bigbluebuttonbn activity editing" page logged in as "admin"
        Then I should see "Guest access"
        Then I click on "Expand all" "link"
        Then I should see "Allow guest access"
        And I should not see "Meeting link"
        And I should not see "Meeting password"
        When I set the field "Allow guest access" to "1"
        Then I should see "Guests joining must be admitted by a moderator"
        And I should see "Meeting link"
        And I should see "Meeting password"
        And I should see "Copy link"
        And I should see "Copy password"
        Then I log out

    @javascript
    Scenario: I should see breakout room notification on activity page
        Given the following config values are set as admin:
        | bigbluebuttonbn_guestaccess_enabled | 1 |
        When I am on the "RoomRecordingsWithguest" "bigbluebuttonbn activity" page logged in as "admin"
        Then I should see "Add guests"
        And I click on "Add guests" "button"
        And I should see "Meeting link"
        And I should see "Meeting password"
        And I should see "Copy link"
        And I should see "Copy password"
        When I set the field "Guest emails" to "123"
        When I click on "OK" "button" in the "Add guests to this meeting" "dialogue"
        Then I should see "Invalid email: 123"
        When I set the field "Guest emails" to "testuser@email.com"
        When I click on "OK" "button" in the "Add guests to this meeting" "dialogue"
        Then I should see "An invitation will be sent to testuser@email.com."
        Then I log out