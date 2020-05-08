## Example RabbitMQ module

This module shows an example implementation of the integration between
the RabbitMQ module and the form system.

### Install
Once this module is enabled, navigate to the following url to view the example form.

```
/admin/config/development/rabbitmq_example
```             

The form will use the `default` connection details defined as part of the RabbitMQ module set up.

Check your RabbitMQ instance is running on the `host` defined in the aforementioned connection details
and enter an email address into the form.

Submitting will send your data to the `rabbitmq_example_queue` queue.
