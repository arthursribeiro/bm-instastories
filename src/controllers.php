<?php

class InstastoriesController {
    private static $cipher_key = "x1Fx9EzxACxA1npbxAFnxE86x17~x1Cx";
    private static $cipher_iv = "x03x00ZxA9x91ixB";
    private static $upload_directory = __DIR__ . '/uploads';

    public static function getInstaStoriesForUser($loggedInUsername, $loggedInEncryptedPassword, $userToQuery, $retry=false) {
        try {
            $password = self::decryptBase64EncryptedPassword($loggedInEncryptedPassword);
    
            if(!$password) {
                throw new InstagramAPI\Exception\RequestException('Invalid key value');
            }
    
            $ig = new \InstagramAPI\Instagram();
            $loginResponse = $ig->login($loggedInUsername, $password);
            $instagram_username = $userToQuery;
            
            $userId = $ig->people->getUserIdForName($instagram_username);
            $stories = $ig->story->getUserReelMediaFeed($userId);
    
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

    public static function postInstaStory($loggedInUsername, $loggedInEncryptedPassword, $fileToPost) {
        try {
            $password = self::decryptBase64EncryptedPassword(stripcslashes($loggedInEncryptedPassword));
            
            if(!$password) {
                throw new InstagramAPI\Exception\RequestException('Invalid key value');
            }
    
            $ig = new \InstagramAPI\Instagram();
            $loginResponse = $ig->login($loggedInUsername, $password);

            $directory = self::$upload_directory;
    
            if ($fileToPost->getError() === UPLOAD_ERR_OK) {
                $filename = moveUploadedFile($directory, $fileToPost);
                $ig->story->uploadPhoto("$directory/$filename", []);
            }
    
            $ig->logout();
    
            $response_data = array(
                "STATUS" => "OK"
            );
            return $response_data;
        } catch (InstagramAPI\Exception\RequestException $e) {
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