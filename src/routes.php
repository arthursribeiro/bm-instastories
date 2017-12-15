<?php
// Routes

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

$container = $app->getContainer();
$container['upload_directory'] = __DIR__ . '/uploads';

$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->post('/getInstaStories', function (Slim\Http\Request $request, Slim\Http\Response $response) {
    try {
        $parsedBody = $request->getParsedBody();
        return $response->withJson(InstagramController::getInstaStoriesForUser($parsedBody["username"], $parsedBody["key"], $parsedBody["user"]));
    } catch (InstastoriesException $e) {
        return $response->withJson($e->getResponseData(), 400);
    }
});

$app->post('/postInstaStory', function ($request, $response, $args) {
    try {
        $username = $request->getParam('username');
        $key = $request->getParam('key');
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['storyPicture'])) {
            throw new InstagramAPI\Exception\RequestException('Expected a storyPicture');
        }

        $uploadedFile = $uploadedFiles['storyPicture'];

        return $response->withJson(InstagramController::postInstaStory($username, $key, $uploadedFile));
    } catch (InstagramAPI\Exception\RequestException $e) {
        $response_data = array(
            "STATUS" => "ERROR",
            "MESSAGE" => $e->getMessage()
        );
        return $response->withJson($response_data, 400);
    } catch (InstastoriesException $e) {
        return $response->withJson($e->getResponseData(), 400);
    } catch (InvalidArgumentException $e) {
        $response_data = array(
            "STATUS" => "ERROR",
            "MESSAGE" => $e->getMessage()
        );
        return $response->withJson($response_data, 400);
    }
});

$app->post('/postInstagramMedia', function ($request, $response, $args) {
    try {
        $username = $request->getParam('username');
        $key = $request->getParam('key');
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['instagramMedia'])) {
            throw new InstagramAPI\Exception\RequestException('Expected a instagramMedia');
        }

        $uploadedFile = $uploadedFiles['instagramMedia'];

        return $response->withJson(InstagramController::postInstagramMedia($username, $key, $uploadedFile, $request->getParam('caption')));
    } catch (InstagramAPI\Exception\RequestException $e) {
        $response_data = array(
            "STATUS" => "ERROR",
            "MESSAGE" => $e->getMessage()
        );
        return $response->withJson($response_data, 400);
    } catch (InstagramException $e) {
        return $response->withJson($e->getResponseData(), 400);
    } catch (InvalidArgumentException $e) {
        $response_data = array(
            "STATUS" => "ERROR",
            "MESSAGE" => $e->getMessage()
        );
        return $response->withJson($response_data, 400);
    }
});

$app->post('/instagramAuthenticate', function ($request, $response, $args) {
    try {
        $username = $request->getParam('username');
        $key = $request->getParam('key');

        if (!$username || !$key) {
            throw new InstagramAPI\Exception\RequestException('Expected a username and key');
        }

        return $response->withJson(InstagramController::instagramLogin($username, $key));
    } catch (InstagramAPI\Exception\RequestException $e) {
        $response_data = array(
            "STATUS" => "ERROR",
            "MESSAGE" => $e->getMessage()
        );
        return $response->withJson($response_data, 400);
    } catch (InstagramException $e) {
        return $response->withJson($e->getResponseData(), 400);
    } catch (InvalidArgumentException $e) {
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