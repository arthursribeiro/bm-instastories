<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . "/../utils.php";
require_once __DIR__ . "/../exceptions.php";

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class InstastoriesCrawler {

    function __construct($username, $key) {
        $this->instastoriesClient = new \InstagramAPI\Instagram();
        $this->username = $username;
        $this->key = $key;
    }

    function loginToInstagramAccount() {
        try {
            $password = decryptBase64EncryptedPassword(stripcslashes($this->key));
    
            if(!$password) {
                throw new InstagramAPI\Exception\RequestException('Invalid key value');
            }
            $loginResponse = $this->instastoriesClient->login($this->username, $password);

            return true;
        } catch (InstagramAPI\Exception\RequestException $e) {
            $response_data = array(
                "STATUS" => "ERROR",
                "MESSAGE" => $e->getMessage()
            );

            throw new InstastoriesException($response_data);
        }
    }

    public function crawlInstagramUserStories($usersToMonitorList, $retries=3) {
        $total = 0;
        try {
            foreach($usersToMonitorList as $userData) {
                $user = $userData->user;
                $brands = $userData->brands;
                $userId = $this->instastoriesClient->people->getUserIdForName($user);
                $stories = $this->instastoriesClient->story->getUserReelMediaFeed($userId);
                $mediaList = $stories->items;

                self::addBrandDataToStories($brands, $mediaList);
                self::addFinalMediaToStories($mediaList);
                self::addLanguageToStories($mediaList);
                self::publishStoriesToIndexer($mediaList);

                $total = $total + count($mediaList);
                sleep(count($mediaList) * rand(5, 15) / 10 );
            }
        } catch (InstagramAPI\Exception\RequestException $e) {
            $response_data = array(
                "STATUS" => "ERROR",
                "MESSAGE" => $e->getMessage()
            );
            throw new InstastoriesException($response_data);
        }

        return $total;
    }

    private function publishStoriesToIndexer($stories) {
        $json_stories = json_encode($stories);

        $json_stories = gzcompress($json_stories);
        
        $connection = new AMQPStreamConnection('rabbitmq.buzzmonitor.com.br', 5673, 'instameter', '5B2NT7Xq269euK6Jx539XQ3SnC6hZ39b', 'instameter');
        $channel = $connection->channel();
        
        $channel->exchange_declare('instameter.stories.crawled', 'fanout', false, true, false);
        
        $msg = new AMQPMessage($json_stories);
        $channel->basic_publish($msg, 'instameter.stories.crawled');
        
        echo " [x] Sent ", count($stories), " to indexer.", "\n";
        
        $channel->close();
        $connection->close();
    }

    private function addBrandDataToStories($brandArray, &$stories) {
        foreach($stories as &$story) {
            $story = (object)(array) $story;
            $story->brands = $brandArray;
        }
    }

    private function addLanguageToStories(&$stories) {
        $texts = [];
        foreach($stories as &$story) {
            $story = (object)(array) $story;
            if($story->caption != null) {
                array_push($texts, array("text" => $story->caption->text));
            } else {
                array_push($texts, array("text" => ""));
            }
            
        }
        $languages = self::detectLanguages($texts);

        $index = 0;
        foreach($stories as &$story) {
            $story->language = $languages[$index];
            $index += 1;
        }
    }

    private function detectLanguages($textsList) {
        $username = "what_the_lang_BM";
        $password = "23982jlNkJNEKBIB8&H8hlKASlNAsish98Ha9HslAskBSAIUsb(*s";
        $ch = curl_init('http://what_lang.bm3.elife.com.br/detect');

        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json; version=1'
            ),
            CURLOPT_USERPWD => $username . ":" . $password,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => json_encode($textsList)
        ));

        $response = curl_exec($ch);

        $responseData = json_decode($response, TRUE);

        $languages = [];
        foreach($responseData as $language) {
            if(array_key_exists("error", $language)) {
                array_push($languages, "ol");
            } else {
                array_push($languages, $language["lang"]);
            }
        }

        return $languages;
    }

    private function addFinalMediaToStories(&$stories) {
        foreach($stories as &$story) {
            $story = (object)(array) $story;
            $rootUrl = null;
            if($story->media_type == 1) {
                $rootUrl = $story->image_versions2->candidates[0]->url;
            } elseif($story->media_type == 2) {
                $rootUrl = $story->video_versions[0]->url;
            }

            if($rootUrl != null) {
                $mediaUrl = self::storeMediaOnServer($rootUrl);
                $story->media_url = $mediaUrl;
            }
        }
    }

    private function storeMediaOnServer($mediaUrl) {
        $authToken = "ZWxpZmU6YW1hcmVsbw==";

        $postData = array(
            "instagramRootUrl" => $mediaUrl
        );

        $ch = curl_init('http://localhost:8080/BMInstastoriesFiles/addMedia');
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.$authToken,
                'Content-Type: application/json',
                'Accept: application/json; version=1'
            ),
            CURLOPT_POSTFIELDS => json_encode($postData)
        ));

        // Send the request
        $response = curl_exec($ch);

        // Check for errors
        if($response === FALSE){
            return $mediaUrl;
        }
        
        // Decode the response
        $responseData = json_decode($response, TRUE);
        return $responseData["mediaUrl"];
        
    }

    public function logoutInstagram() {
        try {
            $this->instastoriesClient->logout();
        } catch (InstagramAPI\Exception\RequestException $e) {
            $response_data = array(
                "STATUS" => "ERROR",
                "MESSAGE" => $e->getMessage()
            );
            throw new InstastoriesException($response_data);
        }
    }

}

?>