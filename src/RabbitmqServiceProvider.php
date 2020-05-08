<?php

namespace Drupal\rabbitmq;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Site\Settings;
use Drupal\rabbitmq\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the language manager service.
 */
class RabbitmqServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $credentials = Settings::get('rabbitmq_credentials');
    if (!empty($credentials)) {
      foreach ($credentials as $key => $values) {
        $connectionFactoryServiceId = 'rabbitmq.connection.factory.' . $key;
        $connectionFactory = new Definition(ConnectionFactory::class, [
          new Reference('settings'),
          $key,
        ]);
        $container->setDefinition($connectionFactoryServiceId, $connectionFactory);

        $queueFactory = new Definition(QueueFactory::class, [
          new Reference($connectionFactoryServiceId),
          new Reference('module_handler'),
          new Reference('logger.channel.rabbitmq'),
          new Reference('config.factory'),
        ]);
        $container->setDefinition('queue.rabbitmq.' . $key, $queueFactory);
      }
    }
  }

}
