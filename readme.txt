=== Power Boost for Gravity Forms ===
Contributors: salzano
Tags: gravityforms, gravity forms
Requires at least: 4.0
Tested up to: 6.2.0
Requires PHP: 5.6
Stable tag: 3.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
An add-on for Gravity Forms. Enhances Gravity Forms for power users. 


== Description ==
 
Power Boost for Gravity Forms is a free WordPress plugin for Gravity Forms power users. The Resend Feeds feature helps me develop and test add-ons, and the Local JSON and Replace Forms features help me deploy changes to Gravity Forms in production.

= Features =

All features captured in screenshots below

* Adds 'Last Entry' column to forms list to indicate which forms are actually used
* Enables merge tags in HTML fields (form must have page breaks)
* Maintains .json file exports of each form when forms are edited and allows forms to be updated by loading the files. Local JSON works similarly to ACF and enables forms to be put into version control with themes or plugins.
* Adds a Resend Feeds button near the Resend Notifications button when viewing an entry
* Adds a tab 'Replace Forms' to the Import/Export page that updates existing forms instead of creating duplicates
* Adds field IDs to the left of labels when viewing or editing an entry in the dashboard
* Adds field IDs to the right of labels when editing forms
* Adds a "Copy Shortcode" row action link to the forms list
* Caches the database queries in the Gravity Forms dashboard widget

= Web page =

[https://breakfastco.xyz/power-boost-for-gravity-forms/](https://breakfastco.xyz/power-boost-for-gravity-forms/)

Have an idea for a new feature? Please visit the web page, and leave a comment.


== Installation ==
 
1. Search for Gravity Forms Power Boost in the Add New tab of the dashboard plugins page and press the Install Now button
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==
 
= How can I suggest a new feature for this plugin? =
 
[Visit this web page](https://breakfastco.xyz/power-boost-for-gravity-forms/), and leave a comment.


== Screenshots ==
 
1. Screenshot of the Gravity Forms list of forms. An additional column labeled "Last Entry" contains timestamps.
2. Screenshot of the Local JSON tab of a form's settings page. A load button is visible and allows the user to update the form to match its companion .json file. The file path where the .json files are stored is also shown.
3. Screenshot of a single Gravity Forms entry. An arrow points to a field ID number that appears to the left of a field name.
4. Screenshot of a single Gravity Forms entry. An arrow points to a Resend Feeds button near the Resend Notifications button.
5. Screenshot of the Gravity Forms list of forms. An arrow points to an additional row action link below a form name labeled "Copy Shortcode."
6. Screenshot of the Import/Export page. Shows an additional tab, titled "Replace Forms."
7. Screenshot of the form editor. Arrows point to field ID numbers near field labels.
8. Screenshot of the form editor. An arrow points to an HTML field's content containing merge tags of fields from previous pages.
9. Screenshot of the dashboard. The Gravity Forms widget says, "Cached with max age 360 minutes."

== Changelog ==

= 3.1.1 =
* [Added] Adds a short sentence to the dashboard widget telling users that they are looking at cached numbers.
* [Added] Adds a screenshot and documentation about the cached dashboard widget added in 3.1.0.

= 3.1.0 =
* [Added] Adds caching to the Gravity Forms dashboard widget by replacing the widget with our own copy. The 3 database queries are cached for a maximum of 6 hours instead of running every time the dashboard widget loads. Six hours can be changed with the a filter `gravityforms_dashboard_cache_duration`.
* [Fixed] Bug fix when retrieving a partial entry in the HTML field merge tags feature. Explicitly convert floats to strings so they are not interpreted as integers.
* [Changed] Changes tested up to version number to 6.2.0.

= 3.0.2 =
* [Fixed] Adds support for PHP 7.2 by fixing syntax errors around commas after final function parameters.
* [Fixed] Bug fix when linking users to redirect-type confirmations after using the Replace Forms feature.
* [Fixed] No longer writes a form .json file when the plugin is activated if a form's file already exists and does not need updating.

= 3.0.1 =
* [Fixed] Fixes a bug when loading a form JSON file on the Local JSON tab. Now explicity casts a form ID as an integer.
* [Changed] Changes tested up to version number to 6.1.1

= 3.0.0 =
* [Changed] Stops minimizing form JSON files saved by the Local JSON feature
* [Added] Adds a filter `gravityforms_local_json_minimize` to enable minimized form JSON files to restore old behavior
* [Fixed] Adds documentation for merge tags in HTML fields: form must have page breaks
* [Fixed] Replace Forms feature now maintains form active status after replacing forms instead of leaving previously-active forms inactive
* [Changed] Applies the WordPress coding standard, adds more translateable strings.

= 2.4.3 =
* [Fixed] Fixes a bug that broke Populate Anything Live Merge Tags

= 2.4.1 =
* [Fixed] Fixes a bug when trying to replace merge tags for fields with multiple inputs
* [Changed] Adds an early exit if the form editor is detected before attempting to replace merge tags in HTML fields
* [Changed] Replaces merges tags in HTML fields at a later priority after Populate Anything Live Merge Tags runs

= 2.4.0 =
* [Added] Enables merge tags in HTML fields
* [Changed] Changes tested up to version number to 6.0.1

= 2.3.2 = 
* [Fixed] Resend Feeds now handles feeds that store feed names in a feedName property rather than feed_name or provide no feed name at all. Also, a nice name is provided for the Partial Entries add-on which does not use feed names. 
* [Fixed] Resend Feeds now disables asynchronous feed processing so feeds are resent immediately after pressing the Resend button.
* [Fixed] Fixes a green checkmark not showing near "Feeds were resent successfully"

= 2.3.1 =
* [Fixed] Fixes a bug when creating form .json files during plugin activation. We need to check if Gravity Forms is running before trying to create the files since this feature operates without relying on Gravity Forms hooks that degrade nicely.

= 2.3.0 =
* [Added] Now creates .json file exports of each form during plugin activation.
* [Added] Form replacer now detects redirect-type confirmations and reminds users that these URLs might need to be updated after replacing forms.
* [Fixed] Fixes a bug in the Form replacer when providing an Edit Form link to users who just imported a single form.
* [Changed] Bumps tested up to version to 5.9.3

= 2.2.1 =
* [Fixed] Now reports failures while resending feeds. Previously, no indication was provided if any of the feeds failed to resend.

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

= 3.1.1 =
Adds a screenshot and documentation about the cached dashboard widget added in 3.1.0: "Adds caching to the Gravity Forms dashboard widget by replacing the widget with our own copy. The 3 database queries are cached for a maximum of 6 hours instead of running every time the dashboard widget loads. Six hours can be changed with the a filter `gravityforms_dashboard_cache_duration`."

= 3.1.0 =
Adds caching to the Gravity Forms dashboard widget by replacing the widget with our own copy. The 3 database queries are cached for a maximum of 6 hours instead of running every time the dashboard widget loads.

= 3.0.2 =
Adds support for PHP 7.2 by fixing syntax errors around commas after final function parameters. Thanks be to EffakT on Github for this fix. Bug fix when linking users to redirect-type confirmations after using the Replace Forms feature. No longer writes a form .json file when the plugin is activated if a form's file already exists and does not need updating.

= 3.0.1 =
Fixes a bug when loading a form JSON file on the Local JSON tab. Now explicity casts a form ID as an integer. Tested up to version 6.1.1.

= 3.0.0 =
Replace forms feature no longer changes active forms to drafts. The feature maintains the previous form status. Stops minimizing form .json files. Provides a filter to restore previous behavior. Major version bump because I've renamed files while applying the WordPress coding standard.

= 2.4.3 =
Bug fixes in the newest feature that enables merge tags in HTML fields. Now plays nice with Populate Anything Live Merge Tags.

= 2.4.0 = 
Enables merge tags in HTML fields

= 2.3.2 = 
Resend Feeds now handles feeds that store feed names in a feedName property rather than feed_name or provide no feed name at all. Also, a nice name is provided for the Partial Entries add-on which does not use feed names. Resend Feeds now disables asynchronous feed processing so feeds are resent immediately after pressing the Resend button. Fixes a green checkmark not showing near "Feeds were resent successfully."

= 2.3.0 =
Now creates .json file exports of each form during plugin activation. Form replacer now detects redirect-type confirmations and reminds users that these URLs might need to be updated after replacing forms. Fixes a bug in the Form replacer when providing an Edit Form link to users who just imported a single form. Bumps tested up to version to 5.9.3.

= 2.2.0 =
Adds a "Copy Shortcode" row action link to the forms list that copies a form's [gravityform] shortcode to the clipboard. Removes CSS that helps show long form names in the form switcher dropdown. Core Gravity Forms has caught up and fixed this bug.

= 1.2.0 =
This is the first version that was published on Github and shipped to the WordPress.org Plugin Repository.