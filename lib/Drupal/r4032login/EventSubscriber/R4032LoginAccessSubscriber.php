<?php

/**
 * @file
 * Definition of Drupal\r4032login\EventSubscriber\R4032LoginAccessSubscriber.
 */

namespace Drupal\r4032login\EventSubscriber;

use Drupal\Core\EventSubscriber\AccessSubscriber;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Redirect 403 to User Login access subscriber.
 */
class R4032LoginAccessSubscriber extends AccessSubscriber {

  /**
   * Verifies that the current user can access the requested path.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the access got denied.
   */
  public function onKernelRequestAccessCheck(GetResponseEvent $event) {
    $request = $event->getRequest();

    // The controller is being handled by the HTTP kernel, so add an attribute
    // to tell us this is the controller request.
    $request->attributes->set('_controller_request', TRUE);

    if (!$request->attributes->has(RouteObjectInterface::ROUTE_OBJECT)) {
      // If no Route is available it is likely a static resource and access is
      // handled elsewhere.
      return;
    }

    // Wrap this in a try/catch to ensure the '_controller_request' attribute
    // can always be removed.
    try {
      $access = $this->accessManager->check($request->attributes->get(RouteObjectInterface::ROUTE_OBJECT), $request, $this->currentUser);
    }
    catch (\Exception $e) {
      $request->attributes->remove('_controller_request');
      throw $e;
    }

    $request->attributes->remove('_controller_request');

    if (!$access) {
      $response = $this->redirect4032Login($event);
      $event->setResponse($response);
    }
  }

  /**
   * Redirects anonymous users from 403 Access Denied pages.
   *
   * Redirect to the /user/login page with a message explaining that they
   * must log in to view the requested page and a query string parameter
   * appended to the url to return after login.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A response that redirects 403 Access Denied pages user login page.
   */
  public function redirect4032Login(GetResponseEvent $event) {
    $config = \Drupal::config('r4032login.settings');
    if ($this->currentUser->isAnonymous()) {
      // Show custom access denied message if set.
      if ($config->get('display_denied_message')) {
        $message = $config->get('access_denied_message');
        drupal_set_message($message, 'error');
      }
      // Handle redirection to the login form.
      $login_path = $config->get('user_login_path');
      $code = $config->get('default_redirect_code');
      return new RedirectResponse(url($login_path, array('absolute' => TRUE, 'query' => drupal_get_destination())), $code);
    }
    else {
      // Check to see if we are to redirect the user.
      $redirect = $config->get('redirect_authenticated_users_to');
      if ($redirect) {
        // Custom access denied page for logged in users.
        return new RedirectResponse(url($redirect, array('absolute' => TRUE)));
      }
      else {
        // Display the default access denied page.
        throw new AccessDeniedHttpException();
      }
    }
  }
}
