<?php

/**
 * profile
 * 
 * @package Sngine
 * @author Zamblek
 */

// fetch bootloader
require('bootloader.php');

// user access
if ($user->_logged_in || !$system['system_public']) {
  user_access();
}

// check username
if (is_empty($_GET['username']) || !valid_username($_GET['username'])) {
  _error(404);
}

try {

  //  SIMPLE & FAST QUERY (NO HEAVY JOINS)
  $get_profile = $db->query(sprintf("
    SELECT user_id, user_name, user_firstname, user_lastname, user_gender, user_picture, user_cover, user_biography 
    FROM users 
    WHERE user_name = %s
  ", secure($_GET['username'])));

  if ($get_profile->num_rows == 0) {
    _error(404);
  }

  $profile = $get_profile->fetch_assoc();

  // basic checks
  if ($user->banned($profile['user_id'])) {
    _error(404);
  }

  if ($user->blocked($profile['user_id'])) {
    _error(404);
  }

  // profile name
  $profile['name'] = $profile['user_firstname'] . " " . $profile['user_lastname'];

  // profile picture
  $profile['user_picture'] = get_picture($profile['user_picture'], $profile['user_gender']);

  // cover
  $profile['user_cover'] = $profile['user_cover'] ? $system['system_uploads'] . '/' . $profile['user_cover'] : '';

  // disable heavy stuff
  $profile['posts_count'] = 0;
  $profile['photos_count'] = 0;
  $profile['videos_count'] = 0;
  $profile['followers_count'] = 0;
  $profile['friends'] = [];
  $profile['photos'] = [];
  $profile['mutual_friends'] = [];

  // view switch
  switch ($_GET['view']) {

    case '':
      // no heavy loading
      $posts = [];
      $smarty->assign('posts', $posts);
      break;

    case 'friends':
      $profile['friends'] = [];
      break;

    case 'photos':
      $profile['photos'] = [];
      break;

    default:
      break;
  }

} catch (Exception $e) {
  _error("Error", $e->getMessage());
}

// page header
page_header($profile['name'], $profile['user_biography'], $profile['user_picture']);

// assign variables
$smarty->assign('profile', $profile);
$smarty->assign('view', $_GET['view']);

// page footer
page_footer('profile');