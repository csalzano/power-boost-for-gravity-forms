#  power-boost-for-gravity-forms

A WordPress plugin. An add-on for Gravity Forms. Enhances the dashboard for Gravity Forms power users.

## Features

1. Adds 'Last Entry' column to forms list to indicate which forms are actually used.
   ![screenshot-1](assets/screenshot-1.png)
1. Highlights forms rendered on the current page in the Forms menu of the Admin Bar. Adds forms that are embedded on the page to the list if they were not already present. Groups embedded forms at the top of the list.
   ![screenshot-2](assets/screenshot-2.png)
1. Adds field IDs to the left of labels when viewing or editing an entry in the dashboard.
   ![screenshot-3](assets/screenshot-3.png)
1. Adds a Resend Feeds button near the Resend Notifications button when viewing an entry.
   ![screenshot-4](assets/screenshot-4.png)
1. Reveals long form names that Gravity Forms 2.5 cuts off in the form switcher dropdown.
   ![screenshot-5](assets/screenshot-5.png)
1. Adds a tab 'Replace Forms' to the Import/Export page that updates existing forms instead of creating duplicates
   ![screenshot-6](assets/screenshot-6.png)

## Filter Hooks

- gfpb_rendered_form_css_classes

  Filters the CSS classes added to list items in the Recent Forms section of the
  Forms admin bar menu.

- gfpb_rendered_form_emoji

  Filters the emoji added next to form titles in the Recent Forms section of the
  Forms admin bar menu.