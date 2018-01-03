<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/instastories-crawler.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$exchange = 'instameter.stories.to_crawl';
$queue = 'instameter.stories.crawler.to_crawl';

$connection = new AMQPStreamConnection('rabbitmq.buzzmonitor.com.br', 5673, 'instameter', '5B2NT7Xq269euK6Jx539XQ3SnC6hZ39b', 'instameter');
$channel = $connection->channel();

$channel->exchange_declare($exchange, 'fanout', false, true, false);

list($queue_name, ,) = $channel->queue_declare($queue, false, false, false, false);

$channel->queue_bind($queue_name, $exchange);

echo ' [*] Instameter Crawler running. To exit press CTRL+C', "\n";

$callback = function($msg) {
  $manage = (array) json_decode($msg->body);
  echo " [x] Received ", $msg->body, "\n";

  if(array_key_exists("users_to_monitor", $manage) && array_key_exists("username", $manage) && array_key_exists("key", $manage)) {
    try {
      $crawler = new InstastoriesCrawler($manage["username"], $manage["key"]);
      $crawler->loginToInstagramAccount();
      $total = $crawler->crawlInstagramUserStories($manage["users_to_monitor"]);
      $crawler->logoutInstagram();
      echo " [x] Crawling using user ", $manage["username"], " collected stories number: ", $total,  "\n";
    } catch (InstastoriesException $e) {
      echo " [x] Problem connecting to instagram". "\n";
    }
  } else {
    echo " [x] Invalid array input!", "\n";
  }

};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

?>