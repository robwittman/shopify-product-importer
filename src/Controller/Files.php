<?php

namespace App\Controller;

class Files
{
    protected $s3;

    public function __construct($s3)
    {
        $this->s3 = $s3;
    }

    public function get($request, $response, $arguments)
    {
        $fileName = $arguments['fileName'];
        $objects = $this->s3->getIterator('ListObjects', array(
            "Bucket" => 'shopify-product-importer',
            "Prefix" => $fileName
        ));
        $res = array();
        foreach ($objects as $object) {
            $file = str_replace($fileName, '', $object['Key']);
            if (!$file || $file == '') {
                continue;
            }
            $res[] = array(
                'name' => $file,
            );
        }
        return $response->withJson($res);
    }

    public function create($request, $response)
    {
        $files = $request->getUploadedFiles();
        if (!isset($files['file'])) {
            return $response->withStatus(400)->withJson(array(
                'error' => "You have to select a file to upload"
            ));
        }
        $file = $files['file'];

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $response->withStatus(400)->withJson(array(
                'error' => 'There was an error uploading your .zip file'
            ));
        }
        $fileHash = hash('sha256', uniqid(true));

        $contents = $this->unpackZip($file, $fileHash);
        if ($contents === false) {
            return $response->withStatus(400)->withJson(array(
                'error' => 'We received your file, but there was an issue processing it'
            ));
        }
        foreach($contents as $name => $object) {
            $filePath = $object->getRealPath();
            if (is_dir($filePath)) {
                continue;
            }
            if (strpos($filePath, '__MACOSX') !== false || strpos($filePath, 'DS_Store') !== false) {
                continue;
            }
            $name = str_replace('/tmp/', '', $filePath);
            error_log($name);
            $res = $this->s3->putObject([
                'Bucket' => "shopify-product-importer",
                'SourceFile' => $object,
                'ACL' => "public-read",
                'Key' => str_replace(' ', '_', $name)
            ]);
        }

        return $response->withJson(array(
            'original_file_name' => $file->getClientFilename(),
            'uploaded_file_name' => $fileHash,
            'size' => $file->getSize(),
        ));
    }

    protected function unpackZip(\Slim\Http\UploadedFile $file, $hash = null)
    {
        if (is_null($hash)) {
            $hash = hash('sha256', uniqid(true));
        }
        $loc = "/tmp/{$hash}.zip";
        $contents = "/tmp/{$hash}";
        $file->moveTo($loc);
        $zip = new \ZipArchive();
        $zip->open($loc);
        $zip->extractTo($contents);

        // DO some clean up. Delete the MACOSX folder, as well as and .DS_Store files
        // if (is_dir($contents+"/__MACOSX")) {
        //     rmdir($contents+"/__MACOSX");
        // }
        // foreach (glob($contents+"/**/.DS_Store") as $file) {
        //     unlink($file);
        // }
        $directory = new \RecursiveDirectoryIterator($contents);
        $objects = new \RecursiveIteratorIterator($directory);
        return $objects;
    }
}
