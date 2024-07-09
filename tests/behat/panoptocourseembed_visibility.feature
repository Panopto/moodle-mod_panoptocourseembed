@mod @mod_panoptocourseembed
Feature: Check panoptocourseembed visibility works
  In order to check panoptocourseembed visibility works
  As a teacher
  I should create a panoptocourseembed activity

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Test     | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher  | Teacher   | First    | teacher1@example.com |
      | student  | Student   | First    | student1@example.com |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |
    And the following "activities" exist:
      | activity              | course | section | intro                       | idnumber | visible |
      | panoptocourseembed    | C1     | 1       | Swanky panoptocourseembed   | 1        | 1       |
      | panoptocourseembed    | C1     | 1       | Swanky panoptocourseembed 2 | 2        | 0       |

  Scenario: Hidden panoptocourseembed activity should be show as hidden.
    Given I log in as "teacher"
    When I am on "Test" course homepage with editing mode on
    Then "Swanky panoptocourseembed 2" activity should be hidden
    And I turn editing mode off
    And "Swanky panoptocourseembed 2" activity should be hidden
    And I log out

    And I log in as "student"
    And I am on "Test" course homepage
    And I should not see "Swanky panoptocourseembed 2"
    And I log out

  Scenario: Visible panoptocourseembed activity should be shown as visible.
    Given I log in as "teacher"
    When I am on "Test" course homepage with editing mode on
    Then "Swanky panoptocourseembed" activity should be visible
    And I log out

    And I log in as "student"
    And I am on "Test" course homepage
    And "Swanky panoptocourseembed" activity should be visible
    And I log out

  @javascript
  Scenario: Teacher can not show panoptocourseembed inside the hidden section
    Given I log in as "teacher"
    And I am on "Test" course homepage with editing mode on
    And I open "Swanky panoptocourseembed 2" actions menu
    And "Swanky panoptocourseembed 2" actions menu should have "Show" item
    And "Swanky panoptocourseembed 2" actions menu should not have "Make available" item
    And "Swanky panoptocourseembed 2" actions menu should not have "Make unavailable" item
    And I click on "Edit settings" "link" in the "Swanky panoptocourseembed 2" activity
    And I expand all fieldsets
    And the "Availability" select box should contain "Hide on course page"
    And the "Availability" select box should not contain "Make available but don't show on course page"
    And the "Availability" select box should contain "Show on course page"
    And I log out

    And I log in as "student"
    And I am on "Test" course homepage
    And I should not see "Swanky panoptocourseembed 2"
    And I log out
