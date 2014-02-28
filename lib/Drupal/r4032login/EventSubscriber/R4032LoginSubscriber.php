<?php

/**
 * @file
 * Definition of Drupal\r4032login\EventSubscriber\R4032LoginSubscriber.
 */

namespace Drupal\r4032login\EventSubscriber;

use Drupal\Core\KeyValueStore\StateInterface;
use Drupal\Core\DestructableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirect 403 to User Login event subscriber.
 */
class R4032LoginSubscriber implements EventSubscriberInterface, DestructableInterface {

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\KeyValueStore\StateInterface $state
   *   The state key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

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
    drupal_set_message(t('R4032LoginSubscriber event'));
  }

  /**
   * Registers methods as kernel listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = array('onKernelRequestAccessCheck', 0);
    return $events;
  }

  /**
   * Implements \Drupal\Core\DestructableInterface::destruct().
   */
  public function destruct() {
    $this->state->set('r4032login.subscriber.destructed', TRUE);
  }
}
