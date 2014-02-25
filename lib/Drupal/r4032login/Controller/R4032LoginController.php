<?php

/**
 * @file
 * Contains \Drupal\r4032login\Controller\R4032LoginController.
 */

namespace Drupal\r4032login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for r4032login routes.
 */
class R4032LoginController extends ControllerBase {

  /**
   * Redirects anonymous users from 403 Access Denied pages to the /user/login
   * page with a message explaining that they must log in to view the requested
   * page and a query string parameter appended to the url to return
   * after login.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A response that redirects 403 Access Denied pages user login page.
   */
  public function redirect4032Login() {
    global $user, $language;
    if (user_is_anonymous()) {
      // Show the access denied message.
      if (\Drupal::config('r4032login.settings')->get('display_denied_message') && empty($_POST)) {
        $message = \Drupal::config('r4032login.settings')->get('access_denied_message');
        drupal_set_message($message, 'error');
      }
      $page_match = FALSE;
      $pages = \Drupal::config('r4032login.settings')->get('match_noredirect_pages');
      if ($pages) {
        // When on an access denied page, Drupal stores the original path in
        // $_GET['destination'] in drupal_deliver_html_page().
        // Convert the Drupal path to lowercase.
        $path = drupal_strtolower(drupal_get_path_alias($_GET['destination']));
        // Compare the lowercase internal and lowercase path alias (if any).
        $page_match = drupal_match_path($path, $pages);
        if ($path != $_GET['destination']) {
          $page_match = $page_match || drupal_match_path($_GET['destination'], $pages);
        }
      }
      if ($page_match) {
        // Display the default login page.
        return drupal_get_form('user_login');
      }
      // Handle redirection to the login form.
      // using drupal_goto() with destination set causes a recursive redirect loop
      $login_path = \Drupal::config('r4032login.settings')->get('user_login_path');
      $code = \Drupal::config('r4032login.settings')->get('default_redirect_code');
      // The code in drupal_get_destination() doesn't preserve any query string
      // on 403 pages, so reproduce the part we want here.
      $path = $_GET['destination'];
      $query = drupal_http_build_query(drupal_get_query_parameters(NULL, array('q', 'destination')));
      if ($query != '') {
        $path .= '?' . $query;
      }
      $destination = array('destination' => $path);
      header('Location: ' . url($login_path, array('query' => $destination, 'absolute' => TRUE)), TRUE, $code);
      drupal_exit();
    }
    else {
      // Check to see if we are to redirect the user.
      $redirect = \Drupal::config('r4032login.settings')->get('redirect_authenticated_users_to');
      if (empty($redirect)) {
        // Display the default access denied page.
        return theme('r4032login_denied');
      }
      else {
        // Custom access denied page for logged in users.
        header('Location: ' . url($redirect, array('absolute' => TRUE)));
        drupal_exit();
      }
    }
  }
}
