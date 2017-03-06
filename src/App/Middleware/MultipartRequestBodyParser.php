<?php

namespace App\Middleware;

use App\File\UploadedFile;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MultipartRequestBodyParser implements RequestMethodInterface
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        // Find content type
        $contentTypeParts = explode('; boundary=', $request->getHeaderLine('content-type'));
        if (count($contentTypeParts) === 1) {
            $contentTypeParts[] = '';
        }

        // Apply this middleware only to PUT and PATCH requests when content type is multipart/form-data
        list($contentType, $boundary) = $contentTypeParts;
        if ($contentType !== 'multipart/form-data'
            || ! in_array($request->getMethod(), [self::METHOD_PUT, self::METHOD_PATCH], true)
        ) {
            return $next($request, $response);
        }

        // Explode parts
        $parts = explode('--' . $boundary, (string) $request->getBody());
        // Discard first and last part, which are inconsistencies from previous explode
        $parts = array_slice($parts, 1, count($parts) - 2);

        $bodyParams = [];
        $files = [];
        foreach ($parts as $part) {
            $this->processPart($files, $bodyParams, $part);
        }

        return $next(
            $request->withUploadedFiles($files)
                    ->withParsedBody($bodyParams),
            $response
        );
    }

    protected function processPart(array &$files, array &$bodyParams, $part)
    {
        // Separate part headers from part body
        $part = ltrim($part, "\r\n");
        list($partRawHeaders, $partBody) = explode("\r\n\r\n", $part, 2);

        // Cast headers into associative array
        $partRawHeaders = explode("\r\n", $partRawHeaders);
        $partHeaders = array_reduce($partRawHeaders, function (array $headers, $header) {
            list($name, $value) = explode(':', $header);
            $headers[strtolower($name)] = ltrim($value, ' ');
            return $headers;
        }, []);

        // Ignore any part without content disposition
        if (! isset($partHeaders['content-disposition'])) {
            return;
        }

        // Parse content disposition, in order to find out the nature of each field
        $contentDisposition = $partHeaders['content-disposition'];
        preg_match(
            '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
            $contentDisposition,
            $matches
        );
        $name = $matches[2];
        $filename = isset($matches[4]) ? $matches[4] : null;

        // Check if current part is a properly uploaded file, a not uploaded file or another field
        if ($filename !== null) {
            // If file was correctly uploaded, write into temp dir and create an UploadedFile instance
            $tempFile = tempnam(ini_get('upload_tmp_dir'), 'php');
            file_put_contents($tempFile, $partBody);
            $this->addFile($files, $name, new UploadedFile(
                $tempFile,
                strlen($partBody),
                UPLOAD_ERR_OK,
                $filename,
                isset($partHeaders['content-type']) ? $partHeaders['content-type'] : null
            ));
        } elseif (strpos($contentDisposition, 'filename') !== false) {
            $this->addFile($files, $name, new UploadedFile(
                null,
                0,
                UPLOAD_ERR_NO_FILE
            ));
        } else {
            $bodyParams[$name] = substr($partBody, 0, -2);
        }
    }

    protected function addFile(array &$files, $name, UploadedFile $newFile)
    {
        $isArray = false;

        // If name has array notation, append it as array
        if (strpos($name, '[]') === strlen($name) - 2) {
            $name = substr($name, 0, -2);
            $isArray = true;
        }

        if (! isset($files[$name])) {
            $files[$name] = $isArray ? [$newFile] : $newFile;
            return;
        }

        $files[$name] = $isArray ? array_merge($files[$name], [$newFile]) : $newFile;
    }
}
