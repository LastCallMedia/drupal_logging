<?php

namespace LastCall\DrupalLogging;

use Monolog\Logger;
use Monolog\Handler\AbstractSyslogHandler;
use Monolog\Formatter\LineFormatter;

/**
 * @file
 * Integration of monolog with papertrail.
 */


/**
 * A Handler for logging to a remote syslogd server.
 */
class PapertrailHandler extends AbstractSyslogHandler {
  protected $socket;
  protected $ident;

  /**
   * @param string $host
   *   Syslog host.
   * @param int $port
   *   Syslog port.
   * @param mixed $facility
   *   Facility.
   * @param int $level
   *   The minimum logging level at which this handler will be triggered.
   * @param bool $bubble
   *   Whether the messages that are handled can bubble up the stack or not.
   * @param string $ident
   *   Program name or tag for each log message.
   */
  public function __construct($host, $port = 514, $facility = LOG_USER, $level = Logger::DEBUG, $bubble = TRUE, $ident = 'php') {
    // This is why we needed to clone this class entirely from SyslogUdpHandler.
    // Know a good way to call parent::parent::__construct?
    // https://stackoverflow.com/questions/1557608/how-do-i-get-a-php-class-constructor-to-call-its-parents-parents-constructor
    parent::__construct($facility, $level, $bubble);
    $this->ident = $ident;
    $this->socket = new LcmUdpSocket($host, $port ?: 514);

    // Setting the line formatter in a custom handler is recommended.
    // https://www.drupal.org/project/monolog/issues/2913400
    $line_format = "[%datetime%] [%extra.request_uri%] %channel%.%level_name%: %message%\n";

    if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
      $line_format = '[ENV:' . $_ENV['PANTHEON_ENVIRONMENT'] . "] $line_format";
    }

    $formatter = new LineFormatter($line_format);
    $this->setFormatter($formatter);
  }

  /**
   * Write the log message.
   */
  protected function write(array $record) {
    $lines = $this->splitMessageIntoLines($record['formatted']);
    $header = $this->makeCommonSyslogHeader($this->logLevels[$record['level']]);

    foreach ($lines as $line) {
      $this->socket->write($line, $header);
    }
  }

  /**
   * Close the syslog socket connection.
   */
  public function close() {
    $this->socket->close();
  }

  /**
   * Convert newlines into the right format.
   */
  private function splitMessageIntoLines($message) {
    if (is_array($message)) {
      $message = implode("\n", $message);
    }

    return preg_split('/$\R?^/m', $message, -1, PREG_SPLIT_NO_EMPTY);
  }

  /**
   * Make common syslog header (see rfc5424)
   */
  protected function makeCommonSyslogHeader($severity) {
    $priority = $severity + $this->facility;

    if (!$pid = getmypid()) {
      $pid = '-';
    }

    if (!$hostname = gethostname()) {
      $hostname = '-';
    }

    return "<$priority>1 " .
            $this->getDateTime() . " " .
            $hostname . " " .
            $this->ident . " " .
            $pid . " - - ";
  }

  /**
   * Get date in this format 2019-01-08T06:24:03-08:00.
   */
  protected function getDateTime() {
    return date(\DateTime::RFC3339);
  }

  /**
   * Inject your own socket, mainly used for testing.
   */
  public function setSocket($socket) {
    $this->socket = $socket;
  }

}
