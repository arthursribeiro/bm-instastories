<?php
// Routes

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

$container = $app->getContainer();
$container['upload_directory'] = __DIR__ . '/uploads';
$container['cipher_key'] = "x1Fx9EzxACxA1npbxAFnxE86x17~x1Cx";
$container['cipher_iv'] = "x03x00ZxA9x91ixB";

$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->post('/getInstaStories', function (Slim\Http\Request $request, Slim\Http\Response $response) {
    try {
        $parsedBody = $request->getParsedBody();

        $encryted_password = base64_decode($parsedBody["key"]);
        $password = openssl_decrypt($encryted_password, 'AES-128-CBC', $this->get('cipher_key'), OPENSSL_RAW_DATA, $this->get('cipher_iv'));

        if(!$password) {
            throw new InstagramAPI\Exception\RequestException('Invalid key value');
        }

        $ig = new \InstagramAPI\Instagram();
        $ig->setUser($parsedBody["username"], $password);
        $loginResponse = $ig->login();
        $instagram_username = $parsedBody["user"];
        
        $userId = $ig->getUsernameId($instagram_username);
        $stories = $ig->getUserStoryFeed($userId);

        $ig->logout();

        $response_data = array(
            "STATUS" => "OK",
            "RESPONSE" => $stories->getFullResponse()
        );
        return $response->withJson($response_data);
    } catch (InstagramAPI\Exception\RequestException $e) {
        $response_data = array(
            "STATUS" => "ERROR",
            "MESSAGE" => $e->getMessage()
        );
        return $response->withJson($response_data, 400);
    }
});

$app->post('/postInstaStory', function ($request, $response, $args) {
    try {
        $directory = $this->get('upload_directory');
        var_dump($directory);
        $ig = new \InstagramAPI\Instagram();
        $ig->setUser("bm.insta.ponto", "entrando2123");
        $loginResponse = $ig->login();

        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['storyPicture'])) {
            throw new Exception('Expected a storyPicture');
        }

        $uploadedFile = $uploadedFiles['storyPicture'];
        $metadata = ['caption' => 'My awesome caption'];

        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($directory, $uploadedFile);
            $ig->uploadStoryPhoto("$directory/$filename", $metadata);
        }

        $ig->logout();

        $response_data = array(
            "STATUS" => "OK"
        );
        return $response->withJson($response_data);
    } catch (InstagramAPI\Exception\RequestException $e) {
        $response_data = array(
            "STATUS" => "ERROR",
            "MESSAGE" => $e->getMessage()
        );
        return $response->withJson($response_data, 400);
    }
});

function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); 
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}