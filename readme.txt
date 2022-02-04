=== Power Boost for Gravity Forms ===
Contributors: salzano
Tags: gravityforms, gravity forms
Requires at least: 4.0
Tested up to: 5.8.3
Requires PHP: 5.6
Stable tag: 2.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
An add-on for Gravity Forms. Enhances the dashboard for Gravity Forms power users. 


== Description ==
 
Power Boost for Gravity Forms is a free WordPress plugin for Gravity Forms power users like me. I mostly run this plugin on local copies of sites to make my job easier, but the Local JSON and Replace Forms features help me deploy changes to Gravity Forms in production.

= Features =

All features captured in screenshots below

*   Adds 'Last Entry' column to forms list to indicate which forms are actually used.
*	Adds a feature called Local JSON that maintains .json file exports of each form when forms are edited and allows forms to be updated by loading their .json files. Local JSON works similarly to ACF and enables forms to be put into version control with themes or plugins.
*	Adds field IDs to the left of labels when viewing or editing an entry in the dashboard.
*	Adds field IDs to the right of labels when editing forms
*   Adds a Resend Feeds button near the Resend Notifications button when viewing an entry.
*	Adds a tab 'Replace Forms' to the Import/Export page that updates existing forms instead of creating duplicates.

= Web page =

[https://entriestogooglesheet.com/gravity-forms-power-boost](https://entriestogooglesheet.com/gravity-forms-power-boost)

Have an idea for a new feature? Please visit the web page, and leave a comment.


== Installation ==
 
1. Search for Gravity Forms Power Boost in the Add New tab of the dashboard plugins page and press the Install Now button
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==
 
= How can I suggest a new feature for this plugin? =
 
[Visit this web page](https://entriestogooglesheet.com/gravity-forms-power-boost), and leave a comment.


== Screenshots ==
 
1. Screenshot of the Gravity Forms list of forms. An additional column labeled "Last Entry" contains timestamps.
2. Screenshot of the Local JSON tab of a form's settings page. A load button is visible and allows the user to update the form to match its companion .json file. The file path where the .json files are stored is also shown.
3. Screenshot of a single Gravity Forms entry. An arrow points to a field ID number that appears to the left of a field name.
4. Screenshot of a single Gravity Forms entry. An arrow points to a Resend Feeds button near the Resend Notifications button.
6. Screenshot of the Import/Export page. Shows an additional tab, titled "Replace Forms."
7. Screenshot of the form editor. Arrows point to field ID numbers near field labels.

== Changelog ==

= 2.2.0 =
* [Added] Adds a "Copy Shortcode" row action link to the forms list that copies a form's [gravityform] shortcode to the clipboard.
* [Changed] Changes the tested up to version number to 5.8.3.
* [Removed] Removes CSS that helps show long form names in the form switcher dropdown. Core Gravity Forms has caught up and fixed this bug.

= 2.1.0 =
* [Added] Adds a feature that puts form IDs near field labels while editing forms.
* [Changed] Changes the plugin icon and banner so this plugin looks better on WordPress.org. The icon had an odd seam between two colors, and the banner was ugly.

= 2.0.1 =
* [Fixed] Fixes a bug, do nothing if Gravity Forms is not running by always checking if it is active.

= 2.0.0 =
* [Added] Adds a feature called Local JSON that maintains .json file exports of each form when forms are edited and allows forms to be updated by loading their .json files. Local JSON works similarly to ACF and enables forms to be put into version control with themes or plugins.
* [Removed] Removes a feature that highlights forms in the admin bar if they are rendered on the current page. This feature was broken by WordPress 5.4's introduction of the `wp_body_open` hook. Details [here](https://github.com/csalzano/power-boost-for-gravity-forms/issues/3).

= 1.5.0 =
* [Added] Adds a feature that puts field IDs near labels when editing entries in the dashboard. This behavior matches an existing feature that puts field IDs near labels when viewing entires in the dashboard.
* [Fixed] Bug fix, prevents forms that do not exist from being updated when using the Replace Forms feature on the Import/Export page. Only forms that already exist will be affected by Replace Forms regardless of how many forms are in the .json file that was uploaded.

= 1.4.0 =
* [Added] Adds a feature that allows the export .json files to update existing forms. A new tab is added to the Import/Export page titled, "Replace Forms." When form export files are uploaded to this page, existing forms are updated. This differs from the built-in "Import Forms" feature that always inserts forms and creates duplicates.

= 1.3.0 =
* [Added] Adds a few CSS rules to better display long form names in the form switcher dropdown. Gravity Forms 2.5 changes the dashboard to conceal form names after the first 21 characters in the dropdown used to choose a form.

= 1.2.1 =
* [Fixed] Fixes a bug for forms with no active feeds. Changes the message telling users the form has no active feeds to use less words.

= 1.2.0 =
* [Added] First version with this readme.txt


== Upgrade Notice ==
 
= 1.2.0 =
This is the first version that was published on Github and shipped to the WordPress.org Plugin Repository.