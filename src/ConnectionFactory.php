<?php

namespace Drupal\rabbitmq;

use Drupal\Core\Site\Settings;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;

/**
 * RabbitMQ connection factory class.
 */
class ConnectionFactory {
  const DEFAULT_CREDENTIALS_KEY = 'default';
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
   * The name of the server to connect to.
   *
   * @var string
   */
  protected $credentialsKey;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings service.
   * @param string $credentialsKey
   *   The key of the credentials to use for the connection.
   */
  public function __construct(Settings $settings, $credentialsKey = 'default') {
    // Cannot continue if the library wasn't loaded.
    assert(class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection'),
      'Could not find php-amqplib. See the rabbitmq/README.md file for details.'
    );
    $this->settings = $settings;
    $this->credentialsKey = $credentialsKey;
  }

  /**
   * Get a configured connection to RabbitMQ.
   *
   * @return \PhpAmqpLib\Connection\AMQPSSLConnection|\PhpAmqpLib\Connection\AMQPStreamConnection
   *   The AMQP or SSL connection.
   */
  public function getConnection() {
    if (empty($this->connection)) {
      if (!empty($credentials['ssl'])) {
        $connection = $this->getSecureConnection();
      }
      else {
        $connection = $this->getStandardConnection();
      }

      $this->connection = $connection;
    }

    return $this->connection;
  }

  /**
   * Get the credentials defined in the settings.php file.
   *
   * @return array
   *   Array of credentials.
   */
  protected function getCredentials() {
    $defaultCredentials['default'] = [
      'host' => static::DEFAULT_SERVER_ALIAS,
      'port' => static::DEFAULT_PORT,
      'username' => static::DEFAULT_USER,
      'password' => static::DEFAULT_PASS,
      'vhost' => '/',
    ];

    $credentials = $this->settings->get(self::CREDENTIALS, $defaultCredentials);

    if (!array_key_exists($this->credentialsKey, $credentials)) {
      $this->credentialsKey = static::DEFAULT_CREDENTIALS_KEY;
    }

    return $credentials[$this->credentialsKey];
  }

  /**
   * Return SSL connection object.
   *
   * @return \PhpAmqpLib\Connection\AMQPSSLConnection
   *   SSL connection object.
   */
  protected function getSecureConnection() {
    $credentials = $this->getCredentials();
    return new AMQPSSLConnection(
      $credentials['host'],
      $credentials['port'],
      $credentials['username'],
      $credentials['password'],
      $credentials['vhost'],
      $credentials['ssl'],
      $credentials['options']
    );
  }

  /**
   * Return standard connection object.
   *
   * @return \PhpAmqpLib\Connection\AMQPStreamConnection
   *   Standard connection object.
   */
  protected function getStandardConnection() {
    $credentials = $this->getCredentials();
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

    return new AMQPStreamConnection(
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

}
