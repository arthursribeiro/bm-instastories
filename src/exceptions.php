<?php

class InstastoriesException extends Exception {

    private $responseData;

    function __construct($response) {
        $this->responseData = $response;
    }
    
    public function setResponseData($responseData) {
      $this->responseData = $responseData;
    }
    
    public function getResponseData() {
      return $this->responseData;
    }

}

?>