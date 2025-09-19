# Stanford Fields

9.1.0
--------------------------------------------------------------------------------
_Release Date: 2025-09-19_

- New search api processor to strip html for 3rd party APIs

9.0.1
--------------------------------------------------------------------------------
_Release Date: 2025-08-20_

- Added hook to replace graphql plugins

9.0.0
--------------------------------------------------------------------------------
_Release Date: 2025-08-20_

- Moved procedural hooks from .module file into class based hooks.

8.6.3
--------------------------------------------------------------------------------
_Release Date: 2025-08-15_

- Fixed safari bug where fieldset label was not visible

8.6.2
--------------------------------------------------------------------------------
_Release Date: 2025-08-13_

- Updated PHPUnit tests to provide data provider attribute.

8.6.1
--------------------------------------------------------------------------------
_Release Date: 2025-06-12_

- Use radio buttons instead of checkboxes for accurate filter behavior

8.6.0
--------------------------------------------------------------------------------
_Release Date: 2025-04-23_

- Add display options to hide additional settings on FontAwesome fields

8.5.1
--------------------------------------------------------------------------------
_Release Date: 2025-04-09_

- Fix element id attribute for proper attachment of BEF widgets.

8.5.0
--------------------------------------------------------------------------------
_Release Date: 2025-04-04_

- Add Better Exposed Filters plugin for combo box select lists & hierarchy taxonomy filters.

8.4.1
--------------------------------------------------------------------------------
_Release Date: 2025-02-24_

- Update book service override with book module 2.0+.

8.3.0
--------------------------------------------------------------------------------
_Release Date: 2024-09-13_

- Added configurable option on link fields to force relative internal links.

8.3.0
--------------------------------------------------------------------------------
_Release Date: 2024-09-04_

- New taxonomy field widget that uses the parent most terms as field labels.

8.2.7
--------------------------------------------------------------------------------
_Release Date: 2024-06-26_

- Allow install on D11.

8.2.6
--------------------------------------------------------------------------------
_Release Date: 2024-02-08_

- Remove ID attribute from oembed lazyload field.


8.2.5
--------------------------------------------------------------------------------
_Release Date: 2023-09-1_

- Update unit tests to support D9 and D10.

8.2.4
--------------------------------------------------------------------------------
_Release Date: 2023-09-08_

- Update unit tests for D10.

8.2.3
--------------------------------------------------------------------------------
_Release Date: 2023-03-16_

- Provide a graceful fallback for Localist field widget if the API fails.

8.2.2
--------------------------------------------------------------------------------
_Release Date: 2023-02-27_

- Allow ds module > 5.0
- Handle timeouts from localist api

8.2.1
--------------------------------------------------------------------------------
_Release Date: 2022-10-19_

- Added access check to hide the "Outline" tab from book module.

8.2.0
--------------------------------------------------------------------------------
_Release Date: 2022-10-13_

- D8CORE-6288 Enhance book module and service (#35)


8.1.13
--------------------------------------------------------------------------------
_Release Date: 2022-07-08_

- Eliminated deprecated functions and methods
- fixed composer namespace to lowercase


8.x-1.12
--------------------------------------------------------------------------------
_Release Date: 2022-05-02_

- Add some labels for field block display in views UI


8.x-1.11
--------------------------------------------------------------------------------
_Release Date: 2022-04-05_

- Graceful fallback if the localist base url is not set
- Updated localist URL references (#26)


8.x-1.10
--------------------------------------------------------------------------------
_Release Date: 2022-03-17_

- Asynchronously fetch Localist api data (#29)
- Removed D8 Tests


8.x-1.9
--------------------------------------------------------------------------------
_Release Date: 2022-01-27_

- Updated language for filter selection options. (#25)
- Sorted select options and use better default values (#24)
- D8CORE-5116: changed order of fields in localist field widget (#23)


8.x-1.8
--------------------------------------------------------------------------------
_Release Date: 2021-11-19_

- D8CORE-4521 Localist field widget for events urls (#21)


8.x-1.7
--------------------------------------------------------------------------------
_Release Date: 2021-10-08_

- D8CORE-4693 Invalidate events whose end dates have recently passed (#19)

8.x-1.5
--------------------------------------------------------------------------------
_Release Date: 2021-05-07_

- HSD8-1046 Invalidate entities whos date fields have recently passed (#16) (2e56375)

8.1.4
--------------------------------------------------------------------------------
_Release Date: 2021-03-05_

- D8CORE-3476 Create a new view display mode specific for viewfields (#14)

8.1.3
--------------------------------------------------------------------------------
_Release Date: 2021-01-13_

* Updated phpunit tests to pass in the new year.

8.1.2
--------------------------------------------------------------------------------
_Release Date: 2020-04-16_

* 8.1.2 (54f1a03)
* D8CORE-1644: Dev branch workflow (f163c12)
* CSD-79 Created a date only widget for datetime fields (#7) (b426b29)
* Update stanford_fields.info.yml (14cad23)
* D8CORE-1644: Dev branch workflow (f163c12)
* CSD-79 Created a date only widget for datetime fields (#7) (b426b29)
* Update stanford_fields.info.yml (14cad23)

8.x-1.1
--------------------------------------------------------------------------------
_Release Date: 2019-11-19_

- Add form validation to field UI form to prevent the user from entering a field
name that will generate a hashed table name.

8.x-1.0
--------------------------------------------------------------------------------
_Release Date: 2019-10-30_

- Initial Release
