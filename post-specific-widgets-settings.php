<?php

add_action('admin_menu', 'post_specific_widgets__add_settings');
function post_specific_widgets__add_settings () {
  add_theme_page('Post-Specific Widgets', 'Post-Specific Widgets', 'administrator', basename(__FILE__), 'post_specific_widgets__settings');
}

function post_specific_widgets__settings() {
  $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
  $result = post_specific_widgets__actions($action);
  post_specific_widgets__show_settings($action, $result);
}

function post_specific_widgets__actions($action) {
  global $wpdb;
  switch ($action) {
    case "clean":
      // get the ID and template of every post in the system
      $postdata = $wpdb->get_results("select $wpdb->posts.ID, $wpdb->postmeta.meta_value from $wpdb->posts ".
        "inner join $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id ".
        "where $wpdb->posts.post_type not in ('revision', 'nav_menu_item') ".
        "and $wpdb->postmeta.meta_key='_wp_page_template'"
        , ARRAY_N);
      //print_r($postdata);
      $ids = array();
      $post_templates = array();
      foreach ($postdata as $p) {
        $ids[] = $p[0];
        $post_templates[$p[0]] = $p[1];
      }

      // get the data on all templates
      $template_sidebars = get_all_template_sidebars();

      // filter the known sidebars to those matching a post ID and registered sidebar
      $sidebars = wp_get_sidebars_widgets();
      $sidebars2 = array();

      $nsidebars = 0;
      $rids = array();
      foreach ($sidebars as $id => $widgets) {
        if (preg_match("|^_template-([0-9]+)-(.+)$|", $id, $match)) {
          $post_id = $match[1];
          if (in_array($post_id, $ids)) {
            $sidebar_id = $match[2];
            $tsb = isset($template_sidebars[$post_templates[$post_id]]) ? $template_sidebars[$post_templates[$post_id]] : array();

            if (isset($tsb[$sidebar_id]) && $tsb[$sidebar_id]) $sidebars2[$id] = $widgets;
            else { $nsidebars++; $rids[] = $post_id; }

            //$sidebars2[$id] = $widgets;
          } else { $nsidebars++; $rids[] = $post_id; }
        } else $sidebars2[$id] = $widgets;
      }
      wp_set_sidebars_widgets($sidebars2);
      $rids = count(array_unique($rids));
      return "<b>Clean up</b>: <b>$nsidebars</b> unused widget areas have been removed from <b>$rids</b> posts.";

    case "erase-template":
      $template = $_REQUEST['template'];
      $all = array_flip(get_page_templates());
      $templatename = $all[$template];
      if (empty($template) || empty($templatename))
        return "<b>Erase:</b> The template <b>&ldquo;$template&rdquo;</b> could not be found.";

      $postdata = $wpdb->get_results("select ID from $wpdb->posts ".
        "inner join $wpdb->postmeta where $wpdb->posts.ID = $wpdb->postmeta.post_id ".
        "and $wpdb->postmeta.meta_key = '_wp_page_template' and $wpdb->postmeta.meta_value = '$template'",
        ARRAY_N);
      $ids = array();
      foreach ($postdata as $p) $ids[] = $p[0];

      $sidebars = wp_get_sidebars_widgets();
      $sidebars2 = array();
      $nsidebars = 0;
      $rids = array();
      foreach ($sidebars as $id => $widgets) {
        if (preg_match("|^_template-([0-9]+)-(.+)$|", $id, $match)) {
          $post_id = $match[1];
          if (in_array($post_id, $ids)) { $nsidebars++; $rids[] = $post_id;
          } else $sidebars2[$id] = $widgets;
        } else $sidebars2[$id] = $widgets;
      }
      wp_set_sidebars_widgets($sidebars2);

      $rids = count(array_unique($rids));
      return "<b>Erase:</b> <b>$nsidebars</b> widget areas on <b>$rids</b> posts with the <b>&ldquo;$templatename&rdquo;</b> template have been removed.";

    case "erase-sidebar":
      $sidebar = $_REQUEST['sidebar'];
      $all_templates = get_all_template_sidebars();
      $templates = array();
      foreach ($all_templates as $template => $sidebars)
        if (isset($sidebars[$sidebar]) && $sidebars[$sidebar])
          $templates[] = $template;
      if (empty($templates))
        return "<b>Erase:</b> No templates using that sidebar could be found.";

      $sidebars = wp_get_sidebars_widgets();
      $sidebars2 = array();
      $nsidebars = 0;
      $rids = array();
      foreach ($sidebars as $id => $widgets) {
        if (preg_match("|^_template-([0-9]+)-(.+)$|", $id, $match)) {
          $post_id = $match[1];
          if ($sidebar == $match[2]) { $nsidebars++; $rids[] = $post_id; }
          else $sidebars2[$id] = $widgets;
        } else $sidebars2[$id] = $widgets;
      }
      wp_set_sidebars_widgets($sidebars2);
      $rids = count(array_unique($rids));
      return "<b>Erase:</b> <b>$nsidebars</b> widget areas on <b>$rids</b> posts have been removed.";

    case "erase-post":
      $post_id = $_REQUEST['post'];
      $post = get_post($post_id);
      if (empty($post))
        return "<b>Erase:</b> The post with ID $post_id cannot be found.";

      $sidebars = wp_get_sidebars_widgets();
      $sidebars2 = array();
      $nsidebars = 0;
      foreach ($sidebars as $id => $widgets) {
        if (preg_match("|^_template-{$post_id}-(.+)$|", $id, $match)) {
          $nsidebars++;
        } else $sidebars2[$id] = $widgets;
      }
      wp_set_sidebars_widgets($sidebars2);
      $title = apply_filters('the_title', $post->post_title);
      return "<b>Erase:</b> <b>$nsidebars</b> widget areas on the post <b>&ldquo;$title&rdquo;</b> have been removed.";

    case "erase-all":
      $sidebars = wp_get_sidebars_widgets();
      $sidebars2 = array();
      foreach ($sidebars as $id => $widgets) {
        if (!preg_match("|^_template-.*|", $id))
          $sidebars2[$id] = $widgets;
      }
      wp_set_sidebars_widgets($sidebars2);
      return "<b>Erase all</b>: All page-specific widgets have been permanently removed.";

    default: return false;
  }
}

function post_specific_widgets__show_settings ($action = false, $result = false) {
  // initial data gathering
  $theme = get_current_theme();
  $templates = get_page_templates();
  $templates["Default"] = "default";

  if (BANG_PW_DEBUG) do_action('log', 'Widgets settings: $templates', $templates);

  $template_sidebars = array();
  $template_pages = array();
  foreach ($templates as $templatename => $filename) {
    $template_sidebars[$filename] = post_specific_widgets__get_template_sidebars($filename);
    if (BANG_PW_DEBUG) do_action('log', 'Widgets: SIDEBARS', basename($filename), $template_sidebars[$filename]);
    $pages = get_pages(array(
      'meta_key' => '_wp_page_template',
      'meta_value' => $filename,
      'number' => 500,
    ));
    $template_pages[$filename] = post_specific_widgets__active_pages($pages);
  }
  if (BANG_PW_DEBUG) do_action('log', 'Widgets settings: $template_sidebars', $template_sidebars);
  if (BANG_PW_DEBUG) do_action('log', 'Widgets settings: $template_pages', $template_pages);

  $all_sidebars = array();
  foreach ($template_sidebars as $filename => $sidebars)
    foreach ($sidebars as $id => $name)
      $all_sidebars[$id] = $name;
  if (BANG_PW_DEBUG) do_action('log', 'Widgets settings: $all_sidebars', $all_sidebars);

  $reverse_templates = array_flip($templates);
  $sidebar_templates = array();
  foreach ($all_sidebars as $id => $name) $sidebar_templates[$id] = array();
  foreach ($template_sidebars as $filename => $sidebars)
    foreach ($sidebars as $id => $name)
      $sidebar_templates[$id][$filename] = $reverse_templates[$filename];
  if (BANG_PW_DEBUG) do_action('log', 'Widgets settings: $sidebar_templates', $sidebar_templates);

  //  write the pages

  ?><div id='bang-leftbar' class='post-specific-widgets'>
    <a href="http://www.bang-on.net">
      <img src="<?php echo plugins_url('images/bang-black-v.png', __FILE__); ?>" /></a>
    <div><h1>Post-Specific Widgets</h1></div>
  </div>

  <div id='bang-main'>
  <div class="wrap" id="page-widgets-wrap">

    <?php screen_icon("themes"); ?> <h2>Post-Specific Widgets</h2>

    <?php
      if ($action) {
        echo "<div id='message' class='updated'><p>$result</p></div>";
      }
    ?>

    <div class='tabs-bar'><p>
      <a href='#summary' class='tab current'>Summary</a>
      <a href='#sidebars' class='tab'>Widget Areas</a>
      <a href='#pages' class='tab'>Pages</a>
      <!--<a href='#settings' class='tab'>Settings</a>-->
      <!--<a href='#actions' class='tab'>Administrative Tasks</a>-->
      <a href='#help' class='tab'>Help</a>
      &nbsp; &nbsp; &raquo;
      &nbsp; <a href='widgets.php'>Widgets</a>
      &nbsp; &nbsp; <a href='themes.php'>Change Theme</a>
    </p></div>


    <div class="pane current" id="summary">
      <h2>Summary</h2>
      <table class='form-table'>
        <tr>
          <th scope="row">Current theme</th>
          <td><b><?php echo $theme; ?></b></td>
          <td><a class='button' href='themes.php'>Change theme</a></td>
          <td></td>
        </tr>
      </table>
      <br/>

      <h2>Templates</h2>
      <table class='form-table'>
        <tr><th>Template</th><th>Widget Areas</th><th>Pages with Widgets</th></tr>
          <?php
            foreach ($templates as $templatename => $filename) {
              $sidebars = $template_sidebars[$filename];
              $sidebars = array_map("post_specific_widgets__sidebar_name", $sidebars);

              $pages = $template_pages[$filename];
              $pages = post_specific_widgets__page_links($pages);
              if (empty($pages)) $pages = "<i>None</i>";

              echo "<tr>";
              echo "<td><b>$templatename</b></td><td class='items'>";
              if (empty($sidebars)) echo "<i>None</i>";
              else echo implode("&nbsp; ", $sidebars);
              echo "</td><td>".$pages."</td>";
              echo "</tr>";
            }
          ?>
      </table>

    </div>


    <div class="pane" id="sidebars">
      <h2>Widget Areas</h2>
      <?php
        echo "<table class='form-table'>";
        echo "<tr><th>Template</th><th>Widget areas</th><th></th><th>Used by pages</th></tr>";
        $ab = true;
        foreach ($templates as $templatename => $filename) {
          $sidebars = $template_sidebars[$filename];
          if (empty($sidebars)) continue;
          echo "<tr><td rowspan='".count($sidebars)."' class='rowspan ".($ab ? 'a' : 'b')."'><b>$templatename</b></td>";
          $ab = !$ab;

          $first = true;
          foreach ($sidebars as $sidebar => $sidebarname) {
            if (!$first) echo "<tr>";

            $pages = $template_pages[$filename];
            $pages = post_specific_widgets__page_links($pages);
            if (empty($pages)) $pages = "<i>None</i>";
            echo "<td>".post_specific_widgets__sidebar_name($sidebarname)."</td><td><tt>$sidebar</tt></td>";
            if ($first) {
            echo "<td rowspan='".count($sidebars)."' class='rowspan items ".($ab ? 'b' : 'a')."'>".$pages."</td>";

            }
            $first = false;
            echo "</tr>";
          }
        }
        echo "</table>";
      ?>
    </div>


    <div class="pane" id="pages">
      <h2>Pages</h2>
      <?php
        $real_templates = array();
        foreach ($template_sidebars as $filename => $sidebars)
          if (!empty($sidebars))
            $real_templates[] = $filename;

        //  new version
        echo "<table class='form-table'>";
        echo "<tr><th>Template</th><th>Widget Areas</th><th>Page</th><th></th></tr>";

        $ab = true;
        foreach ($templates as $templatename => $filename) {
          $sidebars = $template_sidebars[$filename];
          if (empty($sidebars)) continue;

          $pages = $template_pages[$filename];
          $pages = post_specific_widgets__active_pages($pages);
          if (empty($pages)) continue;

          echo "<tr><td rowspan='".count($pages)."' class='rowspan ".($ab ? 'a' : 'b')."'><b>$templatename</b></td>";
            $sidebars = array_map("post_specific_widgets__sidebar_name", $template_sidebars[$filename]);
            echo "<td rowspan='".count($pages)."' class='items rowspan ".($ab ? 'a' : 'b')."'>".implode("&nbsp; ", $sidebars)."</td>";
          $ab = !$ab;

          $first = true;
          foreach ($pages as $page) {
            if (!$first) echo "<tr>";
            $first = false;

            $link = post_specific_widgets__page_link($page);
            $id = $page->post_name;
            echo "<td>$link</td><td>$id</td>";
            echo "<td><a href='widgets.php?post={$page->ID}' class='button'>Configure Widgets</a></td>";
            echo "</tr>";
          }
        }
        echo "</table>";
      ?>
    </div>

    <div class="pane" id="help">
      <div class="leftcol">

      <h2>How to use this plugin</h2>
      <p>Add a <tt>Sidebars</tt> header to your template files naming the page-specific widget areas:</p>
      <pre><span class='preamble'>&lt;?php</span>
<span class='comment'>/*
Template Name: My Template
Sidebars: special (My Special Sidebar), footer (Footer Widgets)
*/</span></pre>
      <p>Each widget area needs a code, and optionally a name in brackets.
        Use the code anywhere within your template (including in dependency files such as <tt>sidebar.php</tt>):</p>
      <pre><span class='preamble'>&lt;?php</span> <span class='fn'>dynamic_sidebar</span>(<span class='string'>'special'</span>); <span class='preamble'>?&gt;</span></pre>
      <p>When you edit a page that has that template selected, you'll see a new meta box for the sidebars. Click the button to configure the widgets for that page.</p>
      <img src='<?php echo plugins_url('images/meta.png', __FILE__); ?>' style='margin-left: 10px;'>
      <p>This will bring up a page for editing the widgets for that page.</p>

      </div>
      <div class="rightcol faq">

        <h2>FAQ</h2>
          <h3>What does this plugin do?</h3>
            <p>The widgets you place in sidebars are normally the same across every page.
              This plugin lets you have sidebars that are different on each page.</p>

          <h3>So I can have a different sidebar on every single page of my site?</h3>
            <p>Yes.</p>

          <h3>What happens when I delete a page or change its template?</h3>
            <p>When you move a page to the trash, its widgets remain so that they can be reinstated together.</p>
            <p>When you empty the trash and permanently delete a page, the widgets you've added to the page get deleted.</p>
            <p>When you change a page's template, any sidebars that are the same on the two widgets will still be there in the new template.
              Any widgets in sidebars that aren't part of the new template will remain in the database but hidden.
              If you change back to the old template, those widgets will be right where you left them.</p>

          <h3>What will happen if I disable this plugin?</h3>
            <p>All the page-specific widgets you've configured will move into the <i>Inactive Plugins</i> section.</p>

      </div>
    </div>

  </div></div><?php
}


function post_specific_widgets__active_pages ($pages, $count = 100) {
  $pages = array_filter($pages, "post_specific_widgets__page_active");
  $pages = array_slice($pages, 0, $count);
  return $pages;
}

function post_specific_widgets__page_active ($page) {
  $sidebars_widgets = wp_get_sidebars_widgets($page);
  if (BANG_PW_DEBUG) do_action('log', 'Widgets: Page %s %s', $page->ID, apply_filters('the_title', $page->post_title), $sidebars_widgets);
  if (empty($sidebars_widgets))
    return false;
  foreach ($sidebars_widgets as $sidebar => $widgets) {
    if (!empty($widgets))
      return true;
  }
  return false;
}

function post_specific_widgets__sidebar_name ($name) {
  return "<span class='sidebar-item'>".$name."</span>";
}

function post_specific_widgets__page_link ($page) {
  $permalink = get_permalink($page);
  $title = apply_filters('the_title', $page->post_title);
  return "<a class='pagelink' href='$permalink'>$title</a>";
}

function post_specific_widgets__page_links ($pages, $count = 20) {
  $ellipsis = "";
  if (count($pages) > $count) {
    $pages = array_slice($pages, 0, $count);
    $ellipsis = "...";
  }
  $pages = array_map("post_specific_widgets__page_link", $pages);
  return implode("&nbsp; ", $pages).$ellipsis;
}
