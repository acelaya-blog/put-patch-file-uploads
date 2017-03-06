<?php

namespace App\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Zend\Diactoros\Response\HtmlResponse;

class UploadAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $uploadedFiles = $request->getUploadedFiles();

        foreach ($uploadedFiles as $name => $specificUploadedFiles) {
            if (! is_array($specificUploadedFiles)) {
                $specificUploadedFiles = [$specificUploadedFiles];
            }

            /**
             * @var array $specificUploadedFiles
             * @var UploadedFileInterface $uploadedFile
             */
            foreach ($specificUploadedFiles as $uploadedFile) {
                if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                    continue;
                }

                $uploadedFile->moveTo('data/files/' . $uploadedFile->getClientFilename());
            }
        }

        ob_start();
        dump($request->getParsedBody(), $uploadedFiles);
        return new HtmlResponse(ob_get_clean());
    }
}
