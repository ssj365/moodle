@mod @mod_bigbluebuttonbn
Feature: Prepopulate breakout rooms allows meeting breakouts to be generated using group roster.

  Background:
    Given a BigBlueButton mock server is configured
    And I enable "bigbluebuttonbn" "mod" plugin
    And the following "course" exists:
    | fullname      | Test Course 1  |
    | shortname     | C1             |
    | category      | 0              |

  @javascript
  Scenario Outline: I should see breakout settings on the module form given groupmode is nogroups.
      # groupmode 0 = no groups
      # groupmode 1 = separate groups
      # groupmode 2 = visible groups
    Given the following "activities" exist:
    | activity        | name            | intro                           | course | idnumber         | type | breakoutenabled |  groupmode   |
    | bigbluebuttonbn | RoomRecordings  | Test Room Recording description | C1     | bigbluebuttonbn1 | 0    | <enabled>       | <groupmode>  |
    When I am on the "RoomRecordings" "bigbluebuttonbn activity" page logged in as "admin"
    And I click on "Settings" "link"
    And I expand all fieldsets
    Then I <shouldseeprepopulate> "Use course groups for breakout rooms (only when not using group mode)"

    Examples:
      | groupmode | enabled | shouldseeprepopulate  |
      | 2         | 1       | should not see        |
      | 2         | 0       | should not see        |
      | 1         | 1       | should not see        |
      | 1         | 0       | should not see        |
      | 0         | 1       | should see            |
      | 0         | 0       | should see            |
