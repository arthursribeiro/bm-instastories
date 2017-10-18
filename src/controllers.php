<?php

class InstastoriesController {
    private static $cipher_key = "x1Fx9EzxACxA1npbxAFnxE86x17~x1Cx";
    private static $cipher_iv = "x03x00ZxA9x91ixB";

    public static function getInstaStoriesForUser($loggedInUsername, $loggedInEncryptedPassword, $userToQuery, $retry=false) {
        try {
            $password = self::decryptBase64EncryptedPassword($loggedInEncryptedPassword);
    
            if(!$password) {
                throw new InstagramAPI\Exception\RequestException('Invalid key value');
            }
    
            $ig = new \InstagramAPI\Instagram();
            $ig->setUser($loggedInUsername, $password);
            $loginResponse = $ig->login();
            $instagram_username = $userToQuery;
            
            $userId = $ig->getUsernameId($instagram_username);
            $stories = $ig->getUserStoryFeed($userId);
    
            $ig->logout();
    
            $response_data = array(
                "STATUS" => "OK",
                "RESPONSE" => $stories->getFullResponse()
            );
            return $response_data;
        } catch (InstagramAPI\Exception\RequestException $e) {
            // if(!$retry) {
            //     sleep(6);
            //     return self::getInstaStoriesForUser($loggedInUsername, $loggedInEncryptedPassword, $userToQuery, true);
            // } 

            $response_data = array(
                "STATUS" => "ERROR",
                "MESSAGE" => $e->getMessage()
            );

            throw new InstastoriesException($response_data);
        }
    }

    private static function decryptBase64EncryptedPassword($base64EncodedEncryptedPassword) {
        $encryted_password = base64_decode($base64EncodedEncryptedPassword);
        $password = openssl_decrypt($encryted_password, 'AES-128-CBC', self::$cipher_key, OPENSSL_RAW_DATA, self::$cipher_iv);

        return $password;
    }
}

?>