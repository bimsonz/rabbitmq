<?php

namespace Drupal\Tests\rabbitmq\Kernel;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class RabbitMqServerTest.
 *
 * @group RabbitMQ
 */
class RabbitMqConnectionTest extends RabbitMqQueueBaseTest {

  /**
   * Test creating an item on an un-managed queue.
   */
  public function testCreateItemOnUnManagedQueue() {
    /* @var \PhpAmqplib\Channel\AMQPChannel $channel */
    list($channel,) = $this->initChannel($this->queueName);

    $count = 10;
    for ($i = 1; $i <= $count; $i++) {
      $payload = 'foo' . $i;
      $message = new AMQPMessage($payload);
      $channel->basic_publish($message, '', $this->routingKey);
    }

    $actual = FALSE;
    $received = 0;
    $callback = function (AMQPMessage $message) use (&$actual, &$received) {
      $actual = $message->body;
      $received++;
    };
    $channel->basic_consume($this->queueName,
      /* $consumer_tag = */ 'test',
      /* $no_local = */ FALSE,
      /* $no_ack = */ TRUE,
      /* $exclusive = */ FALSE,
      /* $nowait = */ FALSE,
      $callback
      // Defaulted args: ticket, arguments.
    );
    while (count($channel->callbacks) && $received < $count) {
      $channel->wait();
    }
    $this->assertEquals($actual, 'foo' . $count);
  }

}
