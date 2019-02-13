
# Logging to Papertrail

## Instructions for setting up logging

At https://papertrailapp.com/account/destinations add a destination. Take note
of the port and destination url. Each site should log to its own destination
by using a unique port.

Create `sites/default/monolog.services.yml` and add the following (replacing
!PORT! and !DESTINATION_URL! with the correct values for your destination):
```
parameters:
  monolog.channel_handlers:
    default: ['rsyslog', 'drupal.dblog']

services:
  monolog.handler.rsyslog:
    class: LastCall\DrupalLogger\PapertrailHandler
    arguments: ['!DESTINATION_URL!', !PORT!]
```

Enable monolog which is brought in with this package.
```
drush en -y monolog
```

Add the following to `settings.php`
```
  $settings['container_yamls'][] = 'sites/default/monolog.services.yml';
```
