<?php

require_once 'lib/campfire.php';

define('ROOM_ID', 12345);
define('BOT_USER_ID', 56789);

$campfire = new Campfire('https://mycampfireinstallation.campfirenow.com', 'mybotsapitoken');
$campfire->set_room(ROOM_ID);
$campfire->join();

$graceful_shutdown = false;
if ($stream = fopen($campfire->get_stream_url(), 'r')) {
  stream_set_blocking($stream, 0);
  echo "ready...\n";
  while (1) {
    $contents = stream_get_contents($stream, -1);
    $contents = trim($contents);
    if (!empty($contents)) {
      $contents = explode("\n", $contents);
      foreach ($contents as $data) {
        $data = json_decode($data, true);
        if (
          (isset($data['body']) && !empty($data['body'])) &&
          (isset($data['type']) && !empty($data['type']) && $data['type'] == 'TextMessage') &&
          $data['user_id'] != BOT_USER_ID
        ) {
          $body = $data['body'];
          $body = trim($body);
          if (!empty($body)) {
            if ($body == '/shutdown') {
              $graceful_shutdown = true;
              echo "shutdown initiated\n";
              break 2;
            }

            if ($body == '/somecommand') {
              $campfire->say('You just ran some command.');
            }
          }
        }
      }
    }

    if (!$stream) {
      break;
    }
  }
}

if (!$graceful_shutdown) {
  echo "error: stream unexpectly quit.\n";
}

fclose($stream);

$campfire->leave();
