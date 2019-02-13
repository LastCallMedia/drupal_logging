<?php

namespace LastCall\DrupalLogging;

use Monolog\Handler\SyslogUdp\UdpSocket;

/**
 * UDP Socket class overriding the DATAGRAM_MAX_LENGTH variable.
 */
class LcmUdpSocket extends UdpSocket {
  // The limit set in UdpSocket is much higher but in testing, these logs never
  // make it to papertail. I've found the limit to be 1444 but I'm setting it
  // lower just in case.
  // https://github.com/papertrail/remote_syslog_logger/issues/15
  const DATAGRAM_MAX_LENGTH = 900;

  /**
   * Trims the message to the appropriate length so it doesn't get lost.
   */
  protected function assembleMessage($line, $header) {
    $chunkSize = self::DATAGRAM_MAX_LENGTH - strlen($header);

    return $header . substr($line, 0, $chunkSize);
  }

}
