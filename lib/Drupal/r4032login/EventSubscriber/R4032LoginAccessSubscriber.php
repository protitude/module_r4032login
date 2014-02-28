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
      $response = new RedirectResponse(url('user/login', array('absolute' => TRUE)));
      $event->setResponse($response);
    }
  }
}
