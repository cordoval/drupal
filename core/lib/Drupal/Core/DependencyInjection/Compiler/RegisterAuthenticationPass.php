<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\RegisterAuthenticationPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services tagged 'authentication_provider'.
 */
class RegisterAuthenticationPass implements CompilerPassInterface {

  /**
   * Adds authentication providers to the authentication manager.
   *
   * Check for services tagged with 'authentication_provider' and add them to
   * the authentication manager.
   *
   * @see \Drupal\Core\Authentication\AuthenticationManager
   * @see \Drupal\Core\Authentication\AuthenticationProviderInterface
   *
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('authentication')) {
      return;
    }
    $manager = $container->getDefinition('authentication');
    foreach ($container->findTaggedServiceIds('authentication_provider') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $manager->addMethodCall('addProvider', array(
        $id,
        new Reference($id),
        $priority,
      ));
    }
  }
}
