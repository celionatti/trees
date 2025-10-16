<?php

namespace App\Controllers;

use Trees\Base\BaseController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UploadController extends BaseController
{
    public function uploadFile(ServerRequestInterface $request): ResponseInterface
    {
        // Get uploaded file
        $file = $this->file($request, 'document');

        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No valid file uploaded'], 400);
        }

        try {
            // Upload with options
            $result = $this->upload($file, [
                'path' => public_path('uploads/documents'),
                'allowed' => ['pdf', 'doc', 'docx', 'txt'],
                'maxSize' => 5242880, // 5MB
            ]);

            return $this->json([
                'success' => true,
                'file' => $result
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function uploadAvatar(ServerRequestInterface $request): ResponseInterface
    {
        $file = $this->file($request, 'avatar');

        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No valid image uploaded'], 400);
        }

        if (!$file->isImage()) {
            return $this->json(['error' => 'File must be an image'], 400);
        }

        try {
            // Upload original
            $uploader = new \Trees\Upload\FileUploader(public_path('uploads/avatars'));
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'webp']);
            $uploader->setMaxSize(2097152); // 2MB

            $result = $uploader->upload($file);

            // Create thumbnail
            $image = new \Trees\Image\Image();
            $image->load($result['path'])
                ->fit(200, 200)
                ->quality(85)
                ->save(public_path('uploads/avatars/thumbs/' . $result['name']));

            return $this->json([
                'success' => true,
                'original' => $result['url'],
                'thumbnail' => '/uploads/avatars/thumbs/' . $result['name']
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function uploadGallery(ServerRequestInterface $request): ResponseInterface
    {
        $files = $request->getUploadedFiles()['images'] ?? [];

        if (empty($files)) {
            return $this->json(['error' => 'No files uploaded'], 400);
        }

        $uploader = new \Trees\Upload\FileUploader(public_path('uploads/gallery'));
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'webp']);
        $uploader->setMaxSize(5242880); // 5MB

        $uploaded = [];
        $errors = [];

        foreach ($files as $index => $fileData) {
            try {
                $file = new \Trees\Upload\UploadedFile($fileData);

                if ($file->isValid()) {
                    $result = $uploader->upload($file);

                    // Create thumbnail
                    $image = new \Trees\Image\Image();
                    $image->load($result['path'])
                        ->resize(800, 600)
                        ->quality(85)
                        ->save($result['path']);

                    $uploaded[] = $result;
                }
            } catch (\Exception $e) {
                $errors[] = "File {$index}: " . $e->getMessage();
            }
        }

        return $this->json([
            'uploaded' => count($uploaded),
            'files' => $uploaded,
            'errors' => $errors
        ]);
    }

    public function processImage(ServerRequestInterface $request): ResponseInterface
    {
        $file = $this->file($request, 'image');

        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No valid image'], 400);
        }

        try {
            $uploader = new \Trees\Upload\FileUploader(public_path('uploads/processed'));
            $result = $uploader->upload($file);

            $image = new \Trees\Image\Image();
            $image->load($result['path'])
                ->resize(1200, 800)
                ->sharpen()
                ->brightness(10)
                ->contrast(-10)
                ->watermark(public_path('images/watermark.png'), 'bottom-right', 20)
                ->quality(90)
                ->save($result['path']);

            return $this->json([
                'success' => true,
                'url' => $result['url']
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
