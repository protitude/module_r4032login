<?php

/**
 * @file
 * Definition of Drupal\r4032login\EventSubscriber\R4032LoginSubscriber.
 */

namespace Drupal\r4032login\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Component\Utility\Xss;

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
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new R4032LoginSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UrlGeneratorInterface $url_generator, AccountInterface $current_user, RedirectDestinationInterface $redirect_destination) {
    $this->urlGenerator = $url_generator;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * Redirects on 403 Access Denied kernel exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the access is denied and redirects to user login page.
   */
  public function onKernelException(GetResponseEvent $event) {
    $config = $this->configFactory->get('r4032login.settings');
    $options = array();
    $options['query'] = $this->redirectDestination->getAsArray();
    $options['absolute'] = TRUE;
    $code = $config->get('default_redirect_code');
    if ($this->currentUser->isAnonymous()) {
      // Show custom access denied message if set.
      if ($config->get('display_denied_message')) {
        $message = $config->get('access_denied_message');
        $message_type = $config->get('access_denied_message_type');
        drupal_set_message(Xss::filterAdmin($message), $message_type);
      }
      // Handle redirection to the login form.
      $login_path = $config->get('user_login_path');
      $response = new RedirectResponse($this->urlGenerator->generateFromPath($login_path, $options), $code);
      $event->setResponse($response);
    }
    else {
      // Check to see if we are to redirect the user.
      $redirect = $config->get('redirect_authenticated_users_to');
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
    $events[KernelEvents::EXCEPTION][] = array('onKernelException');
    return $events;
  }

}
