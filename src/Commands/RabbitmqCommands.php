<?php

namespace Drupal\rabbitmq\Commands;

use Drupal\rabbitmq\ConnectionFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Yaml\Yaml;
use Drush\Commands\DrushCommands;
use Drupal\rabbitmq\Exception\InvalidWorkerException;
use Drupal\rabbitmq\Exception\InvalidArgumentException as RabbitMqInvalidArgumentException;
use Drupal\rabbitmq\Exception\Exception as RabbitMqException;
use Drupal\rabbitmq\Consumer;

/**
 * Class RabbitmqCommands.
 */
class RabbitmqCommands extends DrushCommands {

  /**
   * The consumer.
   *
   * @var \Drupal\rabbitmq\Consumer
   */
  protected $consumer;

  /**
   * The connection factory.
   *
   * @var \Drupal\rabbitmq\ConnectionFactory
   */
  protected $connection;

  /**
   * RabbitmqCommands constructor.
   *
   * @param \Drupal\rabbitmq\Consumer $consumer
   *   Consumer service.
   * @param \Drupal\rabbitmq\ConnectionFactory $connectionFactory
   *   RabbitMQ Connection factory.
   */
  public function __construct(Consumer $consumer, ConnectionFactory $connectionFactory) {
    parent::__construct();
    $this->consumer = $consumer;
    $this->connection = $connectionFactory;
  }

  /**
   * Remove all files unused.
   *
   * @param string $queueName
   *   The name of the queue to process and the queue worker plugin.
   * @param array $option
   *   Consumer service options.
   *
   * @return bool
   *
   * @throws \Exception
   *
   * @command rabbitmq:worker
   *
   * @option memory_limit Set the max amount of memory the worker may use before exiting. Given in megabytes.
   * @option max_iterations Number of iterations to process before exiting. If not present, exit criteria will not evaluate the amount of iterations processed.
   * @option rabbitmq_timeout Number of seconds before the script ends up when waiting on RabbitMQ. Requires the PCNTL extension.
   *
   * @usage drush rqwk
   *   To delete unused files in directory files
   * @aliases rqwk,rabbitmq-worker
   * @validate-queue queueName
   */
  public function rabbitmqWorker(
    $queueName,
    array $option = [
      'rabbitmq_timeout' => self::REQ,
      'memory_limit' => self::REQ,
      'max_iterations' => self::REQ,
    ]
  ) {

    // Service might be called from a non-Drush environment
    // so drush_get_option() may not be available to it.
    $this->consumer->setOptionGetter(function (string $name) {
      return (int) drush_get_option($name, Consumer::OPTIONS[$name]);
    });

    $queueArgs = ['@name' => $queueName];

    $consumer = $this->consumer;
    drupal_register_shutdown_function(function () use ($consumer, $queueName) {
      $consumer->shutdownQueue($queueName);
    });
    try {
      $consumer->logStart();
      $consumer->consumeQueueApi($queueName);
    }
    catch (InvalidWorkerException $e) {
      return drush_set_error(dt("Worker for queue @name does not implement the worker interface.", $queueArgs));
    }
    catch (RabbitMqInvalidArgumentException $e) {
      return drush_set_error($e->getMessage());
    }
    catch (RabbitMqException $e) {
      return drush_set_error(dt("Could not obtain channel for queue.", $queueArgs));
    }

    return TRUE;
  }

  /**
   * Remove all files unused.
   *
   * @param string $queueName
   *   The name of the queue to process and the queue worker plugin.
   *
   * @command rabbitmq:queue-info
   * @usage drush rqqi
   *   To delete unused files in directory files
   * @aliases rqqi,rabbitmq-queue-info
   */
  public function rabbitmqQueueInfo($queueName = NULL) {
    if (empty($queueName)) {
      return;
    }

    /* @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = (new \Drupal())->service('queue');

    $queue = $queueFactory->get($queueName);
    $count = $queue->numberOfItems();
    echo (new Yaml())->dump([$queueName => $count]);
  }

  /**
   * Remove all files unused.
   *
   * @command rabbitmq:test-producer
   * @usage drush rqtp
   *   To delete unused files in directory files
   * @aliases rqtp,rabbitmq-test-producer
   */
  public function rabbitmqTestProducer() {
    $connection = new AMQPStreamConnection(
      $this->connection::DEFAULT_HOST,
      $this->connection::DEFAULT_PORT,
      $this->connection::DEFAULT_USER,
      $this->connection::DEFAULT_PASS
    );

    $channel = $connection->channel();
    $routingKey = $queueName = 'hello';
    $channel->queue_declare($queueName, FALSE, FALSE, FALSE, FALSE);
    $message = new AMQPMessage('Hello World!');
    $channel->basic_publish($message, '', $routingKey);
    echo " [x] Sent 'Hello World!'\n";
    $channel->close();
    $connection->close();
  }

  /**
   * Remove all files unused.
   *
   * @command rabbitmq:test-consummer
   * @usage drush rqtc
   *   To delete unused files in directory files
   * @aliases rqtc,rabbitmq-test-consummer
   */
  public function rabbitmqTestConsummer() {
    $connection = new AMQPStreamConnection(
      $this->connection::DEFAULT_HOST,
      $this->connection::DEFAULT_PORT,
      $this->connection::DEFAULT_USER,
      $this->connection::DEFAULT_PASS
    );

    $channel = $connection->channel();
    $queueName = 'hello';
    $channel->queue_declare($queueName, FALSE, FALSE, FALSE, FALSE);
    echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

    $callback = function ($msg) {
      echo " [x] Received ", $msg->body, "\n";
    };

    $channel->basic_consume($queueName, '', FALSE, TRUE, FALSE, FALSE, $callback);

    while (count($channel->callbacks)) {
      $channel->wait();
    }
    $channel->close();
    $connection->close();
  }

}
