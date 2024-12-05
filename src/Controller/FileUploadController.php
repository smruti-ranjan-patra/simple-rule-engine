<?php

namespace App\Controller;

use App\Service\DebrickedService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

class FileUploadController extends AbstractController
{
    private DebrickedService $myService;

    public function __construct(DebrickedService $myService, private LoggerInterface $logger)
    {
        $this->myService = $myService;
    }

    #[Route('/api/test', methods: ['GET'])]
    public function test(Request $request): void
    {
        dd('test');
    }

    #[Route('/api/upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        try {
            $files = $request->files->all();
            if (empty($files)) {
                return new JsonResponse(['error' => 'No files uploaded'], 400);
            }

            foreach ($files as $file) {
                try {
                    $commitName = '123'; // generate random string and store in the DB
                    $uploadedData = $this->myService->uploadFile($file, 'repoName', $commitName);
                    $this->myService->startScan($uploadedData['ciUploadId']);
                } catch (\Exception $e) {
                    $this->logger->error('Unable to process the ' . $file->getClientOriginalName() . ' due to ' . $e->getMessage());
                    // As per the exception, store the data in the DB like the uplaod failed, etc
                }
            }
            
        } catch (\Exception $e) {
            return new JsonResponse($e);
        }

        return new JsonResponse(['message' => 'Files uploaded successfully']);
    }
}

