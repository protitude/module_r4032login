<?php

namespace Drupal\r4032login\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\Xss;

/**
 * Redirect 403 to User Login event subscriber.
 */
class R4032LoginSubscriber extends HttpExceptionSubscriberBase {

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
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a new R4032LoginSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, RedirectDestinationInterface $redirect_destination, PathMatcherInterface $path_matcher) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->redirectDestination = $redirect_destination;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Redirects on 403 Access Denied kernel exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function on403(GetResponseEvent $event) {
    $config = $this->configFactory->get('r4032login.settings');
    $options = array();
    $options['query'] = $this->redirectDestination->getAsArray();
    $options['absolute'] = TRUE;
    $code = $config->get('default_redirect_code');

    // Check if the path should be ignored.
    $noredirect_pages = trim($config->get('match_noredirect_pages'));
    if ($noredirect_pages !== '') {
      $path_to_match = $this->redirectDestination->get();
      try {
        // Clean up path from possible language prefix, GET arguments, etc.
        $path_to_match = '/' . Url::fromUserInput($path_to_match)->getInternalPath();
      } catch (\Exception $e) {
      }
      if ($this->pathMatcher->matchPath($path_to_match, $noredirect_pages)) {
        return;
      }
    }

    if ($this->currentUser->isAnonymous()) {
      // Show custom access denied message if set.
      if ($config->get('display_denied_message')) {
        $message = $config->get('access_denied_message');
        $message_type = $config->get('access_denied_message_type');
        drupal_set_message(Xss::filterAdmin($message), $message_type);
      }
      // Handle redirection to the login form.
      $login_path = $config->get('user_login_path');
      $url = Url::fromUserInput($login_path, $options)->toString();
      $response = new RedirectResponse($url, $code);
      $event->setResponse($response);
    }
    else {
      // Check to see if we are to redirect the user.
      $redirect = $config->get('redirect_authenticated_users_to');
      if ($redirect) {
        // Custom access denied page for logged in users.
        if ($redirect === '<front>') {
          $url = \Drupal::urlGenerator()->generate('<front>');
        }
        else {
          $url = Url::fromUserInput($redirect, $options)->toString();
        }

        $response = new RedirectResponse($url, $code);
        $event->setResponse($response);
      }
    }
  }

}
