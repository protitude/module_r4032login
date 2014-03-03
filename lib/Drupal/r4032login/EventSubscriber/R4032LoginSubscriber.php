<?php

/**
 * @file
 * Definition of Drupal\r4032login\EventSubscriber\R4032LoginSubscriber.
 */

namespace Drupal\r4032login\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Redirect 403 to User Login event subscriber.
 */
class R4032LoginSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new R4032LoginSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Redirects on 403 Access Denied kernel exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A response that redirects 403 Access Denied pages user login page.
   */
  public function onKernelExceptionCheck(GetResponseEvent $event) {
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
      $response = new RedirectResponse(url($login_path, array('absolute' => TRUE, 'query' => drupal_get_destination())), $code);
      $event->setResponse($response);
    }
    else {
      // Check to see if we are to redirect the user.
      $redirect = $config->get('redirect_authenticated_users_to');
      if ($redirect) {
        // Custom access denied page for logged in users.
        $response = new RedirectResponse(url($redirect, array('absolute' => TRUE)));
        $event->setResponse($response);
      }
      else {
        // Display the default access denied page.
        throw new AccessDeniedHttpException();
      }
    }
  }

  /**
   * Registers methods as kernel listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = array('onKernelExceptionCheck', 0);
    return $events;
  }
}
