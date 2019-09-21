## Example RabbitMQ module

This module shows an example implementation of the integration between the RabbitMQ module and the form system.

### Install
To enable RabbitMQ within the `ExampleForm` you need set the example queue to use the RabbitMQ queue factory service.

Adding the following code to your `settings.php` file

    $settings['queue_service_rabbitmq_example_queue'] = 'queue.rabbitmq';

will ensure that `rabbitmq_example_queue` uses a rabbit message queue rather than the 
default database queue.

Check your RabbitMQ instance is running on the host you defined as part of the set up in the RabbitMQ module.

Navigate to:

    /rabbitmq_example

and enter and email address into the form.

Submitting will send your data to the `rabbitmq_example_queue` queue.
