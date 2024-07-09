@mod @mod_panoptocourseembed
Feature: Set panoptocourseembed ID Number
  In order to set a panoptocourseembed ID number
  As a teacher
  I should create a panoptocourseembed activity and set an ID number

  @javascript
  Scenario: panoptocourseembed ID number input box should be shown.
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Test     | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher  | Teacher   | First    | teacher1@example.com |
      | student  | Student   | First    | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student  | C1     | student        |
      And the following "activities" exist:
      | activity              | course | section | intro                                 | idnumber |
      | panoptocourseembed    | C1     | 1       | Panoptocourseembed with ID number set | C1PANOPTOCOURSEEMBED1 |

    When I log in as "teacher"
    And I am on "Test" course homepage with editing mode on
    Then "Panoptocourseembed with ID number set" activity should be visible
    And I turn editing mode off
    And "Panoptocourseembed with ID number set" activity should be visible
    And I log out

    And I log in as "student"
    And I am on "Test" course homepage
    And I should see "Panoptocourseembed with ID number set"
    And I log out

    And I log in as "teacher"
    And I am on "Test" course homepage
    And I turn editing mode on
    And I open "Panoptocourseembed with ID number set" actions menu
    And I click on "Edit settings" "link" in the "Panoptocourseembed with ID number set" activity
    And I expand all fieldsets
    And I should see "ID number" in the "Common module settings" "fieldset"
    And the field "ID number" matches value "C1PANOPTOCOURSEEMBED1"
