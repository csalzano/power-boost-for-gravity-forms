=== Power Boost for Gravity Forms ===
Contributors: salzano
Tags: gravityforms, gravity forms
Requires at least: 4.0
Tested up to: 5.8.0
Requires PHP: 5.6
Stable tag: 1.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
An add-on for Gravity Forms. Enhances the dashboard for Gravity Forms power users. 


== Description ==
 
Power Boost for Gravity Forms is a free WordPress plugin for Gravity Forms power users like me. I run this plugin on local copies of sites to make my job easier.

Features:

*   Adds 'Last Entry' column to forms list to indicate which forms are actually used.
*   Highlights forms rendered on the current page in the Forms menu of the Admin Bar. Adds forms that are embedded on the page to the list if they were not already present. Groups embedded forms at the top of the list.
*	Adds field IDs to the left of labels when viewing or editing an entry in the dashboard.
*   Adds a Resend Feeds button near the Resend Notifications button when viewing an entry.
*	Reveals long form names that Gravity Forms 2.5 cuts off in the form switcher dropdown.
*	Adds a tab 'Replace Forms' to the Import/Export page that updates existing forms instead of creating duplicates.

Web page: 

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
2. Screenshot of an expanded Forms menu in the WordPress admin bar. The first two forms are highlighted with a different link color and a push pin emoji.
3. Screenshot of a single Gravity Forms entry. An arrow points to a field ID number that appears to the left of a field name.
4. Screenshot of a single Gravity Forms entry. An arrow points to a Resend Feeds button near the Resend Notifications button.
5. Screenshot of the form selector drop down. It has been widened to display long form names rather than cut them off.
6. Screenshot of the Import/Export page. Shows an additional tab, titled "Replace Forms."

== Changelog ==

= 1.5.0 =
* [Added] Adds a feature that puts field IDs near labels when editing entries in the dashboard. This behavior matches an existing feature that puts field IDs near labels when viewing entires in the dashboard.

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