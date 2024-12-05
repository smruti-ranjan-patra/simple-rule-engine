<?php

namespace App\Service;

use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class DebrickedService
{
    private HttpClientInterface $client;
    private $jwtToken;

    public function __construct(HttpClientInterface $client, private LoggerInterface $logger)
    {
        $this->client = $client;
    }

    public function setJwtToken()
    {
        try {
            $response = $this->client->request('POST', getenv('DEBRICKED_TOKEN_GENERATION_API'), [
                'body' => [
                    '_username' => getenv('DEBRICKED_CREDS_USERNAME'),
                    '_password' => getenv('DEBRICKED_CREDS_PASSWORD'),
                ],
            ]);

            if ($response->getStatusCode() !== 200 || !isset(($response->getContent())['token'])) {
                throw new Exception($response->getContent());
            }

            $responseBody = $response->getContent();
            $this->jwtToken = $responseBody['token'];


        } catch (\Exception $e){
            $this->logger->error('Error while generating JWT token : ' . $e->getMessage());
        }
    }

    public function uploadFile($file, $repositoryName, $commitName): array
    {
        try {
            $response = $this->client->request('POST', getenv('DEBRICKED_UPLOAD_API'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                    'Content-Type' => 'multipart/form-data',
                ],
                'body' => [
                    'commitName' => $commitName,
                    'repositoryName' => $repositoryName,
                    'fileData' => $file
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception($response->getContent());
            }

            $responseBody = $response->toArray();

            return [
                'ciUploadId' => $responseBody['ciUploadId'],
                'uploadProgramsFileId' => $responseBody['uploadProgramsFileId'],
            ];


        } catch (\Exception $e){
            $this->logger->error('Error while uploading file : ' . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    public function startScan($uploadId)
    {
        try {
            $response = $this->client->request('POST', getenv('DEBRICKED_QUEUE_API'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                    'Content-Type' => 'multipart/form-data',
                ],
                'body' => [
                    'ciUploadId' => $uploadId
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if (! in_array($statusCode, [200, 204])) {
                throw new Exception($response->getContent());
            }

        } catch (\Exception $e){
            $this->logger->error('Error while intiating the queue for scan : ' . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    public function getScanResult($ciUploadId)
    {
        try {
            $response = $this->client->request('GET', getenv('DEBRICKED_STATUS_API'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                    'Content-Type' => 'multipart/form-data',
                ],
                'body' => [
                    'ciUploadId' => $ciUploadId
                ],
            ]);

            $statusCode = $response->getStatusCode();

            return match ($statusCode) {
                200 => 'Done',
                201 => 'Not-Started',
                202 => 'In-Progress',
                default => throw new Exception($response->getContent()),
            };

        } catch (\Exception $e){
            $this->logger->error('Error while intiating the queue for scan : ' . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
}
