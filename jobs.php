<?php
/**
 * jobs
 * 
 * @package Sngine
 * @author Zamblek
 */

// fetch bootloader
require('bootloader.php');

if (!$system['jobs_enabled']) {
  _error(404);
}

if ($user->_logged_in || !$system['system_public']) {
  user_access();
}

try {

  $_GET['view'] = (isset($_GET['view'])) ? $_GET['view'] : '';

  switch ($_GET['view']) {

    case '':

      $promoted_jobs = [];
      $get_promoted = $db->query("SELECT posts.post_id FROM posts 
        INNER JOIN posts_jobs ON posts.post_id = posts_jobs.post_id 
        WHERE posts.in_group = '0' 
        AND posts.in_event = '0' 
        AND posts.post_type = 'job' 
        AND posts_jobs.available = '1' 
        AND posts.boosted = '1' 
        AND (posts.pre_approved = '1' OR posts.has_approved = '1') 
        ORDER BY RAND() LIMIT 3");

      while ($promoted_job = $get_promoted->fetch_assoc()) {
        $post = $user->get_post($promoted_job['post_id']);
        if ($post) {
          $promoted_jobs[] = $post;
        }
      }

      $smarty->assign('promoted_jobs', $promoted_jobs);

      $where_query = "";
      $url = "";

      page_header(__("Jobs") . ' | ' . __($system['system_title']), __($system['system_description_jobs']));

      // categories
      $all_categories = $user->get_categories("jobs_categories");
      $categories = array_slice($all_categories, 0, 10);

      $smarty->assign('all_categories', $all_categories);
      $smarty->assign('categories', $categories);

      break;


    case 'search':

      if (!isset($_GET['query']) || is_empty($_GET['query'])) {
        redirect('/jobs');
      }

      $smarty->assign('query', htmlentities($_GET['query'], ENT_QUOTES, 'utf-8'));

      $where_query = sprintf('AND (posts.text LIKE %1$s OR posts_jobs.title LIKE %1$s)', secure($_GET['query'], 'search'));
      $url = "/search/" . $_GET['query'];

      page_header(__("Jobs") . ' &rsaquo; ' . __("Search") . ' | ' . __($system['system_title']), __($system['system_description_jobs']));

      $all_categories = $user->get_categories("jobs_categories");
      $categories = array_slice($all_categories, 0, 10);

      $smarty->assign('all_categories', $all_categories);
      $smarty->assign('categories', $categories);

      break;


    case 'category':

      $current_category = $user->get_category("jobs_categories", $_GET['category_id'], true);
      if (!$current_category) {
        _error(404);
      }

      $smarty->assign('current_category', $current_category);

      $where_query = sprintf("AND posts_jobs.category_id = %s", secure($current_category['category_id'], 'int'));
      $url = "/category/" . $current_category['category_id'] . "/" . $current_category['category_url'];

      page_header(__("Jobs") . ' &rsaquo; ' . __($current_category['category_name']) . ' | ' . __($system['system_title']), __($current_category['category_description']));

      if (!$current_category['sub_categories'] && !$current_category['parent']) {
        $all_categories = $user->get_categories("jobs_categories");
        $categories = array_slice($all_categories, 0, 10);
      } else {
        $categories = $user->get_categories("jobs_categories", $current_category['category_id']);
        $all_categories = $categories;
      }

      $smarty->assign('all_categories', $all_categories);
      $smarty->assign('categories', $categories);

      break;


    default:
      _error(404);
      break;
  }

  // ===============================
  // FILTER + PAGINATION
  // ===============================

  $distance_clause = "";
  $distance_query = "";
  $order_query = "";
  $country_query = "";

  $order_query .= " ORDER BY posts.post_id DESC ";

  // COUNTRY FILTER
  if ($system['newsfeed_location_filter_enabled'] && isset($_GET['country']) && $_GET['country'] != "all") {

    $selected_country = $user->get_country_by_name($_GET['country']);
    $smarty->assign('selected_country', $selected_country);

    if ($selected_country) {
      $country_query .= sprintf(
        " AND ( (posts.user_type = 'user' AND user_post_author.user_country = %s) 
        OR (posts.user_type = 'page' AND page_post_author.page_country = %s) )",
        secure($selected_country['country_id'], 'int'),
        secure($selected_country['country_id'], 'int')
      );
    }
  }

  require('includes/class-pager.php');

  $params['selected_page'] = (!isset($_GET['page']) || (int) $_GET['page'] == 0) ? 1 : $_GET['page'];

  $author_join = " 
    LEFT JOIN users AS user_post_author 
    ON posts.user_type = 'user' 
    AND posts.user_id = user_post_author.user_id 
    AND user_post_author.user_banned = '0'

    LEFT JOIN pages AS page_post_author 
    ON posts.user_type = 'page' 
    AND posts.user_id = page_post_author.page_id 
  ";

  // total
  $total = $db->query("
    SELECT COUNT(*) as count 
    FROM posts 
    INNER JOIN posts_jobs ON posts.post_id = posts_jobs.post_id 
    $author_join
    WHERE posts.in_group = '0' 
    AND posts.in_event = '0' 
    AND posts_jobs.available = '1' 
    AND (posts.pre_approved = '1' OR posts.has_approved = '1')
    $where_query
    $country_query
  ");

  $params['total_items'] = $total->fetch_assoc()['count'];
  $params['items_per_page'] = $system['jobs_results'];
  $params['url'] = $system['system_url'] . '/jobs' . $url . '/%s';

  $pager = new Pager($params);
  $limit_query = $pager->getLimitSql();

  // jobs
  $rows = [];

  $get_rows = $db->query("
    SELECT posts.post_id 
    FROM posts 
    INNER JOIN posts_jobs ON posts.post_id = posts_jobs.post_id 
    $author_join
    WHERE posts.in_group = '0' 
    AND posts.in_event = '0' 
    AND posts_jobs.available = '1' 
    AND (posts.pre_approved = '1' OR posts.has_approved = '1')
    $where_query
    $country_query
    $order_query
    $limit_query
  ");

  while ($row = $get_rows->fetch_assoc()) {
    $post = $user->get_post($row['post_id']);
    if ($post) {
      $rows[] = $post;
    }
  }

  $smarty->assign('rows', $rows);
  $smarty->assign('pager', $pager->getPager());
  $smarty->assign('view', $_GET['view']);

  // countries list
  $countries = $user->get_countries();
  $smarty->assign('countries', $countries);

  // ads
  $ads = $user->ads('jobs');
  $smarty->assign('ads', $ads);

} catch (Exception $e) {
  _error(__("Error"), $e->getMessage());
}

page_footer('jobs');