=== Post-Specific Widgets ===
Contributors: marcus.downing
Tags: widgets
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: trunk

Add page-specific widget areas to templates with a `Sidebars:` header.

== Description ==

Add page-specific widget areas to templates with a `Sidebars:` header:

    <?php
    /*
    Template Name: Photos template
    Sidebars: notes (Photo Notes)
    */
    ...
    
Use these sidebars the normal way:

    dynamic_sidebar('notes');

At present this only works on Pages.

== Installation ==

Install the plugin the normal way:

1. Upload the `post-specific-widgets` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `Sidebars: code1 (Sidebar Name 1), code2 (Sidebar Name 2)` in the header of your template
1. On the Edit Post page, or from the admin bar on any post, click the 'Page Widgets' link to edit sidebar widgets for that page.

The header of each template that has unique sidebars should look like this:

    <?php
    /*
    Template Name: Photos template
    Sidebars: notes (Photo Notes)
    */
    ...

This will enable a special sidebar with the code `notes` and the name *Photo Notes*.
From within that template (or within a file loaded by that template, such as `sidebar.php`), add the usual WordPress sidebar code:

    dynamic_sidebar('notes');

To add widgets to a given page:

1. Edit the post or page
1. If this is a page, set the page's template to one with sidebars set up as above
1. Save
1. The 'Page Widgets' meta box will appear. Click the 'Configure Widgets' button.
1. The page-specific sidebars are hilighted in yellow. Drag widgets into them, and configure them as you would other widgets.

When reading the page itself, you can also reach the 'Page Widgets' panel from the Appearance menu in the WordPress admin bar.

== Frequently Asked Questions ==

= Can I have different widgets on every page? =

Yes.

= Can I have some widgets that are the same on all pages? =

Not all sidebars have to be post-specific - you can mix shared sidebars with unique ones.
Put both sidebars into your `sidebars.php`:

    dynamic_sidebar('sidebar');
    dynamic_sidebar('unique-sidebar');

Put widgets into the standard sidebar to have them show on all pages,
or into the unique sidebar for a given page.

= What happens when I delete a page or change its template? =

When you move a page to the trash its widgets remain.
The widgets are only deleted when you empty the trash and permanently delete the page.

When you change a page's template, any sidebars that are on both templates will still be there.
Any other widgets will remain in the database but hidden.
If you change back to the old template, those widgets will be right where you left them.

= What will happen if I disable this plugin? =

All the post-specific widgets will move into the 'Inactive Plugins' section.

= How can I get rid of all of these? =

The settings page includes a button to 'Erase All'.
It permanently removes *all* the post-specific widgets you've configured.
Don't do this unless you're absolutely certain you want to clear out all of your widgets.
There's no way to get them back.

= You shouldn't use colour in the admin area like that! What about colour blind users? =

Colour blind users can use this plugin just fine.

Despite the name, colour blind users aren't actually blind to colour:
they're less able to distinguish between certain hues,
but can clearly see differences in saturation and luminosity.
Our use of colour in the admin interface is limited to a bright yellow hilight,
which contrasts well against the grey or blue theme used by WordPress.
WordPress itself is well designed for colour blind users.

We test our software against the three most common forms of colour blindness with the excellent
[Color Oracle](http://colororacle.cartography.ch/).

== Screenshots ==

1. The meta box on an Edit Post screen, showing two page-specific sidebars.
1. The Page Widgets screen, with two page-specific sidebars in yellow.

== Changelog ==

= 1.2 =

Added proper support for different post types, and fixed several other bugs.

= 1.1 =

Fixed a lot of brokenness.

= 1.0 =

Initial version.
