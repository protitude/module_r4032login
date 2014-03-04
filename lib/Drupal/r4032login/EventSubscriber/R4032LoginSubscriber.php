<?php

/**
 * @file
 * Definition of Drupal\r4032login\EventSubscriber\R4032LoginSubscriber.
 */

namespace Drupal\r4032login\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Routing\UrlGeneratorInterface;
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
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new R4032LoginSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration system.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(ConfigFactory $config_factory, UrlGeneratorInterface $url_generator, AccountInterface $current_user) {
    $this->urlGenerator = $url_generator;
    $this->config = $config_factory->get('r4032login.settings');
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
  public function onKernelException(GetResponseEvent $event) {
    $options = array();
    $options['query'] = drupal_get_destination();
    $options['absolute'] = TRUE;
    $code = $this->config->get('default_redirect_code');
    if ($this->currentUser->isAnonymous()) {
      // Show custom access denied message if set.
      if ($this->config->get('display_denied_message')) {
        $message = $this->config->get('access_denied_message');
        drupal_set_message($message, 'error');
      }
      // Handle redirection to the login form.
      $login_path = $this->config->get('user_login_path');
      $response = new RedirectResponse($this->urlGenerator->generateFromPath($login_path, $options), $code);
      $event->setResponse($response);
    }
    else {
      // Check to see if we are to redirect the user.
      $redirect = $this->config->get('redirect_authenticated_users_to');
      if ($redirect) {
        // Custom access denied page for logged in users.
        $response = new RedirectResponse($this->urlGenerator->generateFromPath($redirect, $options), $code);
        $event->setResponse($response);
      }
      else {
        // Display the default access denied page.
        throw new AccessDeniedHttpException();
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * The priority for the exception must be as low as possible this subscriber
   * to respond with AccessDeniedHttpException.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = array('onKernelException', -255);
    return $events;
  }
}
