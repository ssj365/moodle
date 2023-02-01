@mod @mod_bigbluebuttonbn
Feature: Prepopulate breakout rooms allows meeting breakouts to be generated using group roster.

  Background:
    Given a BigBlueButton mock server is configured
    And I enable "bigbluebuttonbn" "mod" plugin
    And the following "courses" exist:
      | fullname      | shortname | category | groupmode |
      | Test Course 1 | C1        | 0        | 1         |
    And the following "activities" exist:
      | activity        | name                       | intro                           | course | idnumber         | type | breakoutlimit | prepopulatebreakout |
      | bigbluebuttonbn | RoomRecordings             | Test Room Recording description | C1     | bigbluebuttonbn1 | 0    | 0             | 0                   |
      | bigbluebuttonbn | RoomRecordingsWithbreakout | Test Room with breakout         | C1     | bigbluebuttonbn1 | 0    | 10            | 1                   |

    @javascript
    Scenario: I should see breakout settings on the module form
      When I am on the "RoomRecordings" "bigbluebuttonbn activity editing" page logged in as "admin"
      Then I should see "Room settings"
      Then I click on "Expand all" "link"
      Then I should see "Prepopulate breakout rooms"
      And I should not see "Breakout limit"
      When I set the field "Prepopulate breakout rooms" to "1"
      Then I should see "Breakout limit"
      Then I log out
      