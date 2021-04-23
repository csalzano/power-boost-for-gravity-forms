# gravityforms-power-boost

A WordPress plugin. An add-on for Gravity Forms. Enhances the dashboard for Gravity Forms power users. 

## Features

1. Adds 'Last Entry' column to forms list to indicate which forms are actually used
1. Highlights forms rendered on the current page in the Forms menu of the Admin Bar. Adds forms that are embedded on the page to the list if they were not already present. Groups embedded forms at the top of the list.
1. Adds field IDs to the left of labels when viewing an entry
1. Adds a Resend Feeds button near the Resend Notifications button when viewing an entry

## Filter Hooks

- gfpb_rendered_form_css_classes

  Filters the CSS classes added to list items in the Recent Forms section of the
  Forms admin bar menu.

- gfpb_rendered_form_emoji

  Filters the emoji added next to form titles in the Recent Forms section of the
  Forms admin bar menu.