<?php

namespace Drupal\Tests\rabbitmq\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rabbitmq\Queue\QueueBase;
use Drupal\rabbitmq\Queue\QueueFactory;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class RabbitMqTestBase is a base class for RabbitMQ tests.
 */
abstract class RabbitMqBaseTest extends KernelTestBase {
  const MODULE = 'rabbitmq';

  /**
   * Modules to consider.
   *
   * @var array
   */
  public static $modules = ['system', QueueBase::MODULE];

  /**
   * Server factory.
   *
   * @var \Drupal\rabbitmq\ConnectionFactory
   */
  protected $connectionFactory;

  /**
   * The name requested for the temporary queue created during tests.
   *
   * @var string
   */
  protected $queueName;

  /**
   * The routing key, actually equal to the queue name, but not necessarily so.
   *
   * @var string
   */
  protected $routingKey;

  /**
   * {@inheritdoc}
   */
  public function __construct($name = NULL, array $data = [], $dataName = '') {
    // Set to FALSE to enable step debugging: with the default TRUE value,
    // PHPUnit eval's the code, disabling debugging.
    $this->setRunTestInSeparateProcess(TRUE);

    parent::__construct($name, $data, $dataName);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig([QueueBase::MODULE]);
    $time = $this->container->get('datetime.time')->getCurrentTime();
    $this->routingKey = $this->queueName = 'test-' . date('c', $time);

    // Override the database queue to ensure all requests to it come to us.
    $this->container->setAlias('queue.database', QueueFactory::SERVICE_NAME);
    $this->connectionFactory = $this->container->get('rabbitmq.connection.factory');

    $config = $this->config('rabbitmq.config');
    $queues = $config->get('queues');
    $queues[$this->queueName] = [
      'passive' => FALSE,
      'durable' => TRUE,
      'exclusive' => FALSE,
      'auto_delete' => FALSE,
      'nowait' => FALSE,
      'routing_keys' => [],
    ];
    $config->set('queues', $queues)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $connection = $this->connectionFactory->getConnection();

    $channel = $connection->channel();
    if ($channel instanceof AMQPChannel) {
      $channel->close();
    }

    $connection->close();

    parent::tearDown();
  }

  /**
   * Initialize a server and free channel.
   *
   * @param string $name
   *   Queue name.
   *
   * @return array[]
   *   - \AMQPChannel: A channel to the default queue.
   *   - string: the queue name.
   */
  protected function initChannel($name = QueueFactory::DEFAULT_QUEUE_NAME) {
    $connection = $this->connectionFactory->getConnection();
    $this->assertTrue($connection instanceof AMQPStreamConnection, 'Default connections is an AMQP Connection');

    $channel = $connection->channel();
    $this->assertTrue($channel instanceof AMQPChannel, 'Default connection provides channels');

    list($actualName,,) = $channel->queue_declare(
      $name,
      FALSE,
      TRUE,
      FALSE,
      FALSE
    );
    $this->assertEquals($name, $actualName, 'Queue declaration succeeded');

    return [$channel, $actualName];
  }

}
