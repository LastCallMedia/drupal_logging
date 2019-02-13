
# Logging to Papertrail

## Instructions for setting up logging

At https://papertrailapp.com/account/destinations add a destination. Take note
of the port and destination url. Each site should log to its own destination
by using a unique port.

In your Drupal site's repo, run:
```
composer require lastcall/drupal_logging
```

Create `sites/default/monolog.services.yml` and add the following (replacing
!PORT! and !DESTINATION_URL! with the correct values for your destination):
```
parameters:
  monolog.channel_handlers:
    default: ['rsyslog', 'drupal.dblog']

services:
  monolog.handler.rsyslog:
    class: LastCall\DrupalLogging\PapertrailHandler
    arguments: ['!DESTINATION_URL!', !PORT!]
```

Enable monolog which is brought in with this package.
```
drush en -y monolog
```

Add the following to `settings.php`
```
  // If you have installed drupal_logger and Drupal isn't finding your class, you may
  // have stale class info in the APC cache. This is a safeguard to avoid whitescreens
  // in that case.
  $handler_exists = class_exists('LastCall\\DrupalLogging\\PapertrailHandler');
  if ($handler_exists) {
    $settings['container_yamls'][] = 'sites/default/monolog.services.yml';
  }
```

If you're using Pantheon and you don't want local machines logging add:
```
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  $settings['container_yamls'][] = 'sites/default/monolog.services.yml';
}
```
