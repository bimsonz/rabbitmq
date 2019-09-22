<?php

namespace Drupal\rabbitmq;

use Drupal\Core\Site\Settings;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;

/**
 * RabbitMQ connection factory class.
 */
class ConnectionFactory {
  const DEFAULT_SERVER_ALIAS = 'localhost';
  const DEFAULT_HOST = self::DEFAULT_SERVER_ALIAS;
  const DEFAULT_PORT = 5672;
  const DEFAULT_USER = 'guest';
  const DEFAULT_PASS = 'guest';

  const CREDENTIALS = 'rabbitmq_credentials';

  /**
   * The RabbitMQ connection.
   *
   * @var \PhpAmqpLib\Connection\AMQPStreamConnection
   */
  protected $connection;

  /**
   * The settings service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings service.
   */
  public function __construct(Settings $settings) {
    // Cannot continue if the library wasn't loaded.
    assert(class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection'),
      'Could not find php-amqplib. See the rabbitmq/README.md file for details.'
    );
    $this->settings = $settings;
  }

  /**
   * Get a configured connection to RabbitMQ.
   *
   * @return \PhpAmqpLib\Connection\AMQPSSLConnection|\PhpAmqpLib\Connection\AMQPStreamConnection
   *   The AMQP or SSL connection.
   */
  public function getConnection() {
    if (empty($this->connection)) {
      $defaultCredentials = [
        'host' => static::DEFAULT_SERVER_ALIAS,
        'port' => static::DEFAULT_PORT,
        'username' => static::DEFAULT_USER,
        'password' => static::DEFAULT_PASS,
        'vhost' => '/',
      ];

      $credentials = Settings::get(self::CREDENTIALS, $defaultCredentials);

      if (!empty($credentials['ssl'])) {
        $connection = new AMQPSSLConnection(
          $credentials['host'],
          $credentials['port'],
          $credentials['username'],
          $credentials['password'],
          $credentials['vhost'],
          $credentials['ssl'],
          $credentials['options']
        );
      }
      else {
        $defaultOptions = [
          'insist' => FALSE,
          'login_method' => 'AMQPLAIN',
          'login_response' => NULL,
          'locale' => 'en_US',
          'connection_timeout' => 3.0,
          'read_write_timeout' => 3.0,
          'context' => NULL,
          'keepalive' => FALSE,
          'heartbeat' => 0,
        ];

        if (empty($credentials['options'])) {
          $credentials['options'] = [];
        }

        $credentials['options'] = array_merge($defaultOptions, $credentials['options']);

        $connection = new AMQPStreamConnection(
          $credentials['host'],
          $credentials['port'],
          $credentials['username'],
          $credentials['password'],
          $credentials['vhost'],
          $credentials['options']['insist'],
          $credentials['options']['login_method'],
          $credentials['options']['login_response'],
          $credentials['options']['locale'],
          $credentials['options']['connection_timeout'],
          $credentials['options']['read_write_timeout'],
          $credentials['options']['context'],
          $credentials['options']['keepalive'],
          $credentials['options']['heartbeat']
        );
      }
      $this->connection = $connection;
    }
    return $this->connection;
  }

}
