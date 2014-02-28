<?php

/**
 * @file
 * Definition of Drupal\r4032login\R4032LoginServiceProvider.
 */

namespace Drupal\r4032login;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Redirect 403 to User Login service provider.
 */
class R4032LoginServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('access_subscriber')) {
      // Override the AccessSubscriber service class.
      $definition = $container->getDefinition('access_subscriber');
      $definition->setClass('Drupal\r4032login\EventSubscriber\R4032LoginAccessSubscriber');
    }
  }
}
