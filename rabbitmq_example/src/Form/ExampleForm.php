<?php

namespace Drupal\rabbitmq_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class ExampleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rabbitmq_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Send an email address to the queue.'),
    ];
    $form['show'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the data you want to send to the queue.
    $data = $form_state->getValue('email');

    // Get the queue config and send it to the data to the queue.
    $queueName = 'rabbitmq_example_queue';
    $queueFactory = \Drupal::service('queue.rabbitmq.default');
    /* @var \Drupal\rabbitmq\Queue\Queue $queue */
    $queue = $queueFactory->get($queueName);
    $queue->createItem($data);

    // Send some feedback.
    $this->messenger()->addMessage(
      $this->t('You sent the following data: @email to queue: @queue', [
        '@queue' => $queueName,
        '@email' => $form_state->getValue('email'),
      ])
    );
  }

}
