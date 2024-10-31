<?php
/*
Plugin Name: Post-Specific Widgets
Plugin URI: http://www.bang-on.net/
Description: Add page-specific widget areas to templates with a "Sidebars:" header.
Version: 1.3
Tested up to: 3.5
Author: Marcus Downing
Author URI: http://www.bang-on.net/
*/

/*  Copyright 2011  Marcus Downing  (email : marcus@bang-on.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/*
  The current template for a page is stored in the custom field '_wp_page_template'.
  The 'default' value indicates 'page.php' or 'index.php'; otherwise it's the file name.

  We're intercepting:
   - the Edit Post page, to add a summary and link if relevant
   - the Widgets page, to replace the normal sidebars with post-specific ones
   - the Dynamic Sidebars function, to return a special sidebar

  We're also hooking the link on the Edit Post page into a lightbox.
*/

if (!defined('BANG_PW_DEBUG'))
  define('BANG_PW_DEBUG', false);

include_once("post-specific-widgets-functions.php");
if (is_admin())
  include_once("post-specific-widgets-settings.php");


//  1. The Edit form meta box

//add_action('admin_init', 'post_specific_widgets__admin_init');
add_action('admin_menu', 'post_specific_widgets__admin_init');
add_action('user_admin_menu', 'post_specific_widgets__admin_init');
add_action('network_admin_menu', 'post_specific_widgets__admin_init');
function post_specific_widgets__admin_init () {
  $scriptparts = explode("/", $_SERVER['SCRIPT_NAME']);
  $script = array_pop($scriptparts);
  if (BANG_PW_DEBUG >= 2) do_action('log', 'Widgets: Admin init', $script);

  wp_register_script('post_widgets', plugins_url('scripts/settings.js', __FILE__), array('jquery'));
  wp_register_style('post_widgets', plugins_url('admin.css', __FILE__));

  // permissions
  $post_id = $_REQUEST['post'];
  $user = wp_get_current_user();
  if (BANG_PW_DEBUG >= 2) do_action('log', 'Checking page permissions for post', $post_id, $user);
  if (current_user_can('edit_pages', $post_id)) {
    if (BANG_PW_DEBUG >= 2) do_action('log', 'Adjusting permissions for user', $user->ID);
    $user->add_cap('edit_theme_options');
  }

  //  Edit Post / Edit Page
  if ($script == "post.php" && current_user_can('edit_page', $post_id)) {
    if (BANG_PW_DEBUG >= 2) do_action('log', 'Widgets: About to try for post %s', $post_id);
    $sidebars = post_specific_widgets__get_template_sidebars($post_id);
    if (BANG_PW_DEBUG >= 2) do_action('log', 'Widgets: trying for post %s', $post_id, $sidebars);

    if (!empty($sidebars)) {
      $pt = get_post_type($post_id);
      $posttype = get_post_type_object($pt);
      $type = $posttype->labels->singular_name;
      add_meta_box('widgets_meta', "$type Widgets", 'post_specific_widgets__meta', $pt, 'side', 'core');
    }
    wp_enqueue_style('post_widgets');
  }

  //  Settings page
  if ($script == "themes.php") {
    $page = $_REQUEST['page'];
    if ($page == "post-specific-widgets-settings.php") {
      wp_enqueue_script('thickbox');
      wp_enqueue_script('post_widgets');
      wp_enqueue_style('thickbox');
      wp_enqueue_style('post_widgets');
      //wp_enqueue_script('widgets_settings_js', plugins_url('scripts/.js', __FILE__), array('jquery'));
      //wp_enqueue_style('widgets_settings_css', plugins_url('settings.css', __FILE__));
    }
  }

  if ($script == "widgets.php") {
    if (BANG_PW_DEBUG >= 2) do_action('log', 'About to try widgets page');
    wp_enqueue_style('post_widgets');
    if (!empty($post_id)) {
      $post = get_post($post_id);
      if (!empty($post) && current_user_can('edit_page', $post_id)) {
        if (!isset($_REQUEST['hidemessage']) || !$_REQUEST['hidemessage']) {
          add_filter('admin_body_class', 'post_specific_widgets__body_class');
        }
      }
    }
  }

  //wp_enqueue_script('widgets_lb', plugins_url('edit.js', __FILE__), array('jquery'));
}

function post_specific_widgets__body_class($classes) {
  return "$classes post-specific-widgets";
}

function post_specific_widgets__meta ($post) {
  $sidebars = post_specific_widgets__get_template_sidebars($post);
  if (empty($sidebars)) {
    ?><a href="http://www.bang-on.net">
      <img src="<?php echo plugins_url('images/bang.png', __FILE__); ?>" style="float: right;" /></a>
    <p>This template has <b>no</b> page-specific widget areas.</p><?php
    return;
  }
  $count = count($sidebars);

  ?><a href="http://www.bang-on.net">
    <img src="<?php echo plugins_url('images/bang.png', __FILE__); ?>" style="float: right; margin-top: -38px;" /></a>
  <p>This template has <b><?php echo $count ?></b> page-specific widget area<?php if ($count != 1) echo "s" ?>.</p>
  <p style="text-align: center; margin: 14px 0px;">
    <a href="widgets.php?post=<?php echo $post->ID; ?>" class="button-primary" id="config-widgets">Configure Widgets</a></p>
  <?php
}
//  &TB_iframe=true&hidemessage=true' class='thickbox'


add_filter('user_has_cap', 'post_specific_widgets__user_caps', 10, 3);
function post_specific_widgets__user_caps($allcaps, $metacaps, $fnargs) {
  if (in_array('edit_theme_options', $metacaps)) {
    if (BANG_PW_DEBUG >= 2) do_action('log', 'Widget caps: desired caps', $metacaps);
    global $post;
    $post_id = null;
    if (isset($post->ID)) $post_id = $post->ID;
    if (isset($_REQUEST['post'])) $post_id = $_REQUEST['post'];
    if (BANG_PW_DEBUG >= 2) do_action('log', 'Widget caps: for post', $post_id);

    if (current_user_can('edit_pages', $post_id)) {
      $allcaps['edit_theme_options'] = true;
      if (BANG_PW_DEBUG >= 2) do_action('log', 'Widget caps: Resulting caps', array_keys($allcaps));
    }
  }
  
  return $allcaps;
}


//  2. Change the title and behaviour of the Widgets page, if it has a 'post' parameter.

add_action('sidebar_admin_setup', 'post_specific_widgets__admin_setup');
function post_specific_widgets__admin_setup () {
  //debug_sidebars("sidebar_admin_setup");

  if (isset($_REQUEST['post']) && !empty($_REQUEST['post'])) {
    add_filter('gettext', 'post_specific_widgets__title', 100, 3);
    //wp_enqueue_script('post_id', plugins_url('
  }
}

function post_specific_widgets__title ($translation, $text, $domain) {
  if ($text == "Widgets") {
    $post = get_post($_REQUEST['post']);
    $posttype = get_post_type_object(get_post_type($post));
    $type = $posttype->labels->singular_name;
    $title = apply_filters('the_title', $post->post_title);
    return "$type Widgets: $title";
  }
  return $translation;
}

add_action('widgets_admin_page', 'post_specific_widgets__admin_page');
function post_specific_widgets__admin_page () {
  if (isset($_REQUEST['post'])) {
    $post_id = $_REQUEST['post'];
    $post = get_post($post_id);
    if (!empty($post)) {

      if (!isset($_REQUEST['hidemessage']) || !$_REQUEST['hidemessage']) {

        ?><div class='updated' id='post-specific-widgets-updated'>
          <a href="http://www.bang-on.net"><img src="<?php echo plugins_url('images/bang.png', __FILE__); ?>" style="float: right; margin: 5px;" /></a>

          <p>
          Hilighted in <span style='color: #7c6c00; font-weight: bold;'>yellow</span>
            are the unique widgets and sidebars for the <?php
            $type = get_post_type_object($post->post_type);
            if (empty($type)) echo "post";
            else echo lcfirst($type->labels->singular_name);
          ?>:&nbsp;
          <b><a href='post.php?post=<?php echo $post->ID; ?>&action=edit'><?php echo apply_filters('the_title', $post->post_title); ?></a></b> &nbsp; &nbsp; 
          <a class='button' href='post.php?post=<?php echo $post->ID; ?>&action=edit'>Edit page</a></b> &nbsp; 
          <a class='button' href='<?php echo get_permalink($post->ID); ?>'>View page</a> &nbsp; &nbsp; 
          </p><p>
          To edit the site-wide widgets and sidebars instead,&nbsp;
          <a href='widgets.php'>click here</a> . &nbsp; 
          <a class='button' href='themes.php?page=post-specific-widgets-settings.php'>Page Widgets Settings</a>
          </p></div><?php
        
        
        echo "<script type='text/javascript'>jQuery(function($) {\n";
        // adjust the "ajaxurl" variable to include the "post" parameter
        echo "  ajaxurl = ajaxurl+'?post=$post_id';\n";

        // adjust the sidebars that should be post-specific
        $sidebars = post_specific_widgets__get_template_sidebars($post);
        foreach ($sidebars as $id => $name) {
          echo "  console.log('post specific area $id');\n";
          echo "  $('#{$id}').closest('.widgets-holder-wrap').addClass('post-specific');\n";
          echo "  $('#{$id}').droppable({'accept': '.widget'});\n";
        }
        echo "});</script>\n";
      }

      return;
    }
  }
}


//  3. Render and save the right sidebars

add_filter('sidebars_widgets', 'post_specific_widgets__sidebars_widgets');
function post_specific_widgets__sidebars_widgets ($value) {
  global $post;
  if ($post)
    return post_specific_widgets__get_sidebars_widgets($post, $value, true);

  if (isset($_REQUEST['post']))
    return post_specific_widgets__get_sidebars_widgets($_REQUEST['post'], $value, true);

  return $value;
}


add_filter('pre_update_option_sidebars_widgets', 'post_specific_widgets__set', 10, 2);
function post_specific_widgets__set ($newvalue, $oldvalue) {
  if (isset($_REQUEST['post'])) {
    $post_id = $_REQUEST['post'];
    post_specific_widgets__set_sidebars_widgets($post_id, $newvalue);
    return post_specific_widgets__base_sidebars_widgets($post_id, $newvalue, $oldvalue);
  }

  return $newvalue;
}


//  4. Admin bar link
add_action('admin_bar_menu', 'post_specific_widgets__admin_bar_init', 90);
function post_specific_widgets__admin_bar_init () {
  global $wp_admin_bar;
  $post = get_queried_object();
  $post_type = get_post_type_object($post->post_type);
  if (!empty($post) && current_user_can('edit_page', $post->ID)) {
    $sidebars = post_specific_widgets__get_template_sidebars($post->ID);
    if (!empty($sidebars)) {
      $wp_admin_bar->add_node(array(
        'parent' => false,
        'title' => sprintf(__('Edit %s\'s Widgets'), is_front_page() ? "Home Page" : $post_type->labels->singular_name),
        'href' => admin_url('widgets.php?post='.$post->ID),
      ));
    }
  }
}
