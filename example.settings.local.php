<?php

/**
 * @file
 * Example settings to connect to RabbitMQ.
 *
 * This is the default data to add to your settings.local.php.
 */

$settings['rabbitmq_credentials']['default'] = [
  'host' => 'localhost',
  'port' => 5672,
  'vhost' => '/',
  'username' => 'guest',
  'password' => 'guest',
  // Uncomment the lines below if you are using AMQP over SSL.
  /*
  'ssl' => [
    'verify_peer_name' => FALSE,
    'verify_peer' => FALSE,
    'local_pk' => '~/.ssh/id_rsa',
  ],
   */
  'options' => [
    'connection_timeout' => 5,
    'read_write_timeout' => 5,
  ],
];

$settings['queue_default'] = 'queue.rabbitmq.default';

/**
 * Define a secondary connection by following the example below.
 *
 * Each connection will be assigned a dynamic service name based
 * on the key used to define it.
 *
 * @code
 * $settings['rabbitmq_credentials']['qa'] = [
 *   'host' => 'http://qa-rabbitmq-host.com',
 *   'port' => 5672,
 *   'vhost' => '/',
 *   'username' => 'qa',
 *   'password' => 'qa',
 *   'options' => [
 *     'connection_timeout' => 15,
 *     'read_write_timeout' => 5,
 *   ],
 * ];
 * @endcode
 *
 * The above code will define an alias as follows.
 * @code
 * $settings['queue_service_qa'] = 'queue.rabbitmq.qa';
 * @endcode
 *
 * Queues that use the default `queue.rabbitmq.default` service will use
 * the default connection.
*/
