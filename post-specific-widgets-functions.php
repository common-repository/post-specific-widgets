<?php

//  PHP version overrides
if (!function_exists('lcfirst')) {
  function lcfirst($str) {
    return (string)(strtolower(substr($str,0,1)).substr($str,1));
  }
}

function post_specific_widgets__debug_sidebars ($label = "") {
  global $wp_registered_sidebars;
  if (BANG_PW_DEBUG) do_action('log', 'Widgets sidebars $label', array_keys($wp_registered_sidebars));
}

// Find the specific sidebars listed this template's headers
function post_specific_widgets__get_post($p = false, $id = false) {
  if (is_numeric($p)) {
    $id = $p;
    $p = null;
  }

  if (!empty($p)) return $p;
  if ($id) {
    $p = get_post($id);
    if (!empty($p)) return $p;
  }

  global $post;
  if (!empty($post)) return $post;

  if (isset($_REQUEST['post'])) {
    $p = get_post($_REQUEST['post']);
    if (!empty($post)) return $p;
  }

  if (isset($_REQUEST['page'])) {
    $p = get_post($_REQUEST['page']);
    if (!empty($post)) return $p;
  }

  return null;
}

function post_specific_widgets__get_post_template ($post = false) {
  if (!is_numeric($post) && is_string($post)) {
    $template = locate_template(array($post), false, false);
    $template = apply_filters('template_include', $template);
    if (BANG_PW_DEBUG >= 2) do_action('log', 'Widgets: Already template', $post, $template);
    return $template;
  }

  $post = post_specific_widgets__get_post($post);
  $type = get_post_type($post);
  if (BANG_PW_DEBUG >= 2) do_action('log', 'Widgets: Finding template for post #%s %s (%s)', $post->ID, apply_filters('the_title', $post->post_title), $type);
  $template = "default";

  //  pages
  if ($type == "page") {
    //  look for a template metadata option
    $templatename = get_post_meta($post->ID, '_wp_page_template', true);
    if (BANG_PW_DEBUG >= 2) do_action('log', 'Widgets: _wp_page_template', $templatename);
    if ($templatename) {
      $template = locate_template($templatename, false, false);
    }
  }
  $tname = $template;

  //  default template
  if ($template == "default" || empty($template)) {
    if ($type == "page") {
      if ($post)
        $templates = array("page-{$post->post_name}.php", "page-{$post->id}.php", "page.php", "index.php");
      else
        $templates = array("page.php", "index.php");
    } else {
      if ($post)
        $templates = array("single-$type.php", "single.php", "index.php");
      else
        $templates = array("index.php");
    }

    $template = locate_template($templates, false, false);
  }

  //  filter
  $template = apply_filters('template_include', $template);

  if (BANG_PW_DEBUG >= 3) do_action('log', 'Widgets: Page %s %s: template', $post->ID, apply_filters('the_title', $post->post_title), $tname, $template);
  return $template;
}

function post_specific_widgets__get_template_sidebars ($post) {
  //  get the actual file
  $template = post_specific_widgets__get_post_template($post);
  if (BANG_PW_DEBUG >= 2) do_action('log', 'Widgets: Finding sidebars for post %s (%s)', $post, basename($template));
  if (empty($template)) return array();
  $template_data = implode( '', file( $template ));

  //  find the Widgets: header
  if (preg_match('|Sidebars:(.*)$|mi', $template_data, $match)) {
    $sidebars = explode(",", $match[1]);
    $sidebars = array_map("trim", $sidebars);
    $sidebars = array_filter($sidebars);

    $objs = array();
    foreach ($sidebars as $sidebar) {
      if (preg_match('|([^ ]+)\s*\((.+)\)|', $sidebar, $match))
        $objs[$match[1]] = $match[2];
      else
        $objs[$sidebar] = $sidebar;
    }
    if (BANG_PW_DEBUG) do_action('log', 'Widgets: Sidebars for post %s (%s)', $post, basename($template), $objs );
    return $objs;
  } else if (BANG_PW_DEBUG) do_action('log', 'Widgets: No sidebars for post %s (%s)', $post, basename($template));
  return array();
}

function post_specific_widgets__get_all_template_sidebars () {
  $templates = get_page_templates();
  $template_sidebars = array();
  foreach ($templates as $templatename => $filename) {
    $template_sidebars[$filename] = post_specific_widgets__get_template_sidebars($filename);
    //do_action('log', 'Widgets: SIDEBARS', basename($filename), $template_sidebars[$filename]);
  }
  return $template_sidebars;
}


function post_specific_widgets__get_sidebars_widgets($post, $merge = false, $register_sidebars = false) {
  if (!$post) return;
  if (is_object($post)) $post = $post->ID;

  // load the array from the 'sidebars_widgets' custom field
  $widgets = get_post_meta($post, 'sidebars_widgets', true);
  $widgets = maybe_unserialize($widgets);
  if (!$widgets) $widgets = array();

  // fill in any blank sidebars
  $sidebars = post_specific_widgets__get_template_sidebars($post);
  foreach ($sidebars as $id => $name)
    if (!isset($widgets[$id]))
      $widgets[$id] = array();

  //  register sidebars
  if ($register_sidebars)
    foreach ($sidebars as $id => $name)
      post_specific_widgets__register_sidebar($id, $name);

  if ($merge)
    $widgets = wp_parse_args($widgets, $merge);
  return $widgets;
}

function post_specific_widgets__set_sidebars_widgets($post, $newvalue) {
  //  strip out non-template-specific sidebars
  $sidebars = post_specific_widgets__get_template_sidebars($post);
  $widgets = array_intersect_key($newvalue, $sidebars);

  //$widgets = maybe_serialize($widgets);

  if (!$post) return;
  if (is_object($post)) $post = $post->ID;
  update_post_meta($post, 'sidebars_widgets', $widgets);
}

function post_specific_widgets__base_sidebars_widgets($post, $newvalue, $oldvalue) {
  $sidebars = post_specific_widgets__get_template_sidebars($post);
  $value = $newvalue;
  foreach ($sidebars as $id => $name) {
    if (isset($value[$id])) {
      if (isset($oldvalue[$id]))
        $value[$id] = $oldvalue[$id];
      else
        unset($value[$id]);
    }
  }
  return $value;
}

function post_specific_widgets__register_sidebar($id, $name) {
  global $wp_registered_sidebars;
  //if (BANG_PW_DEBUG) do_action('log', 'Widgets: $wp_registered_sidebars', $wp_registered_sidebars);

  $sidebars = $wp_registered_sidebars;
  if (!isset($wp_registered_sidebars))
    $sidebars = array();
  $sidebars = array_filter($sidebars, 'post_specific_widgets__sidebar_not_unique');
  if (isset($sidebars['sidebar']))
    $template = $sidebars['sidebar'];
  else if (isset($sidebars['sidebar-1']))
    $template = $sidebars['sidebar-1'];
  else if (!empty($sidebars))
    $template = array_shift($sidebars);
  else {
    $template = array(
      'before_widget' => '<li id="%1$s" class="widget %2$s">',
      'after_widget' => '</li>',
      'before_title' => '<h2 class="widget-title">',
      'after_title' => '</h2>',
      );
  }

  register_sidebar(array(
    'id' => $id,
    'name' => $name,
    'before_widget' => $template['before_widget'],
    'after_widget' => $template['after_widget'],
    'before_title' => $template['before_title'],
    'after_title' => $template['after_title'],
  ));

  $wp_registered_sidebars[$id]['unique'] = true;
}

function post_specific_widgets__sidebar_not_unique($sidebar) {
  return !isset($sidebar['unique']) || !$sidebar['unique'];
}