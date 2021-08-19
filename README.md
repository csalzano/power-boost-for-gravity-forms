#  power-boost-for-gravity-forms

A WordPress plugin. An add-on for Gravity Forms. Enhances the dashboard for Gravity Forms power users.

## Features

1. Adds 'Last Entry' column to forms list to indicate which forms are actually used.
&nbsp;
   ![screenshot-1](assets/screenshot-1.png)
&nbsp;
1. Adds a feature called Local JSON that maintains .json file exports of each form when forms are edited and allows forms to be updated by loading their .json files. Local JSON works similarly to ACF and enables forms to be put into version control with themes or plugins.
&nbsp;
   ![screenshot-2](assets/screenshot-2.png)
&nbsp;
1. Adds field IDs to the left of labels when viewing or editing an entry in the dashboard.
&nbsp;
   ![screenshot-3](assets/screenshot-3.png)
&nbsp;
1. Adds a Resend Feeds button near the Resend Notifications button when viewing an entry.
&nbsp;
   ![screenshot-4](assets/screenshot-4.png)
&nbsp;
1. Reveals long form names that Gravity Forms 2.5 cuts off in the form switcher dropdown.
&nbsp;
   ![screenshot-5](assets/screenshot-5.png)
&nbsp;
1. Adds a tab 'Replace Forms' to the Import/Export page that updates existing forms instead of creating duplicates
&nbsp;
   ![screenshot-6](assets/screenshot-6.png)
&nbsp;

## Filter Hooks

`gravityforms_local_json_save_path`

   The absolute file path to a directory where the form export .json files are saved. Defaults to `wp-content/uploads/gf-json/`
&nbsp;
&nbsp;
`gravityforms_local_json_save_form`

   Allows a forms array containing a single form to be edited just before it is written to the .json file