<?php

namespace App\Tests\Service;

use App\Service\DebrickedService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;

class DebrickedServiceTest extends TestCase
{
    private $httpClientMock;
    private $loggerMock;
    private $debrickedService;

    protected function setUp(): void
    {
        // Create mock for HttpClientInterface
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->httpClientMock = $this->getMockBuilder(HttpClientInterface::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        // Create mock for LoggerInterface
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        // Cast mocks explicitly
        /** @var HttpClientInterface $httpClient */
        $httpClient = $this->httpClientMock;
        /** @var LoggerInterface $logger */
        $logger = $this->loggerMock;

        $this->debrickedService = new DebrickedService($httpClient, $logger);
    }


    public function testSetJwtTokenSuccess(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getContent')->willReturn(json_encode(['token' => 'test_jwt_token']));

        $this->httpClientMock->method('request')->willReturn($responseMock);

        $this->debrickedService->setJwtToken();

        // Use reflection to access the private jwtToken property
        $reflection = new \ReflectionClass($this->debrickedService);
        $property = $reflection->getProperty('jwtToken');
        $property->setAccessible(true);
        $jwtToken = $property->getValue($this->debrickedService);

        $this->assertEquals('test_jwt_token', $jwtToken);
    }

    public function testSetJwtTokenFailure(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(401);
        $responseMock->method('getContent')->willReturn('Unauthorized');

        $this->httpClientMock->method('request')->willReturn($responseMock);

        $this->loggerMock->expects($this->once())->method('error')->with($this->stringContains('Error while generating JWT token'));

        $this->expectException(\Exception::class);

        $this->debrickedService->setJwtToken();
    }

    public function testUploadFileSuccess(): void
    {
        $file = 'mock_file_content';
        $repositoryName = 'test-repo';
        $commitName = 'test-commit';

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('toArray')->willReturn([
            'ciUploadId' => '123',
            'uploadProgramsFileId' => '456',
        ]);

        $this->httpClientMock->method('request')->willReturn($responseMock);

        $result = $this->debrickedService->uploadFile($file, $repositoryName, $commitName);

        $this->assertEquals(['ciUploadId' => '123', 'uploadProgramsFileId' => '456'], $result);
    }

    public function testUploadFileFailure(): void
    {
        $file = 'mock_file_content';
        $repositoryName = 'test-repo';
        $commitName = 'test-commit';

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(500);
        $responseMock->method('getContent')->willReturn('Internal Server Error');

        $this->httpClientMock->method('request')->willReturn($responseMock);

        $this->loggerMock->expects($this->once())->method('error')->with($this->stringContains('Error while uploading file'));

        $this->expectException(\Exception::class);

        $this->debrickedService->uploadFile($file, $repositoryName, $commitName);
    }

    public function testStartScanSuccess(): void
    {
        $uploadId = '123';

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(204);

        $this->httpClientMock->method('request')->willReturn($responseMock);

        $this->debrickedService->startScan($uploadId);

        $this->addToAssertionCount(1); // Verify no exceptions are thrown
    }

    public function testStartScanFailure(): void
    {
        $uploadId = '123';

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(500);
        $responseMock->method('getContent')->willReturn('Internal Server Error');

        $this->httpClientMock->method('request')->willReturn($responseMock);

        $this->loggerMock->expects($this->once())->method('error')->with($this->stringContains('Error while intiating the queue for scan'));

        $this->expectException(\Exception::class);

        $this->debrickedService->startScan($uploadId);
    }

    public function testGetScanResult(): void
    {
        $ciUploadId = '123';

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);

        $this->httpClientMock->method('request')->willReturn($responseMock);

        $status = $this->debrickedService->getScanResult($ciUploadId);

        $this->assertEquals('Done', $status);
    }
}
