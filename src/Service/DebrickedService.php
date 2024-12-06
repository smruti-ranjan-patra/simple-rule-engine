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

    /**
     * Sends a POST request to the Debricked token generation API to obtain a JWT token.
     * Sets the received JWT token to the class property if successful.
     * Logs an error message if the token generation fails.
     *
     * @throws Exception if the response status code is not 200 or the token is not present in the response.
     */
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

    /**
     * Uploads a file to the Debricked API and starts a scan.
     *
     * @param UploadedFile $file The file to upload.
     * @param string $repositoryName The name of the repository.
     * @param string $commitName The commit name.
     * @return array An array containing the ciUploadId and the uploadProgramsFileId.
     * @throws Exception If the upload fails.
     */
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

    /**
     * Start the scan process using the ciUploadId returned by the uploadFile() method.
     *
     * @param string $uploadId The ciUploadId returned by the uploadFile() method.
     *
     * @throws Exception If the request fails.
     */
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

    /**
     * Gets the status of a scan using the ciUploadId returned by the uploadFile() method.
     *
     * @param string $ciUploadId The ciUploadId returned by the uploadFile() method.
     *
     * @return string The status of the scan, one of:
     *     - Done: Scan is done and the report is available.
     *     - Not-Started: The scan has not started yet.
     *     - In-Progress: The scan is in progress.
     *
     * @throws Exception If the request fails.
     */
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
