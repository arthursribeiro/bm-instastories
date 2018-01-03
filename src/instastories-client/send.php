<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('rabbitmq.buzzmonitor.com.br', 5673, 'instameter', '5B2NT7Xq269euK6Jx539XQ3SnC6hZ39b', 'instameter');
$channel = $connection->channel();

$channel->exchange_declare('instameter.stories.to_crawl', 'fanout', false, true, false);

$msg = new AMQPMessage('{"users_to_monitor": [{"user": "ivetesangalo", "brands": ["arthurdesribeir_lacoste"]}], "username": "debugkenneth", "key": "D6vjn8cZdV95F0tuWdWxmQ=="}');
$channel->basic_publish($msg, 'instameter.stories.to_crawl');

echo " [x] Sent 'Hello World!'\n";

$channel->close();
$connection->close();

?>