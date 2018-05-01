<?php

namespace App\Pipeline;

use League\Flysystem\Filesystem;

class ImageLoader
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Load all our images from the Filesystem
     * @param Payload $payload
     * @return Payload
     */
    public function __invoke(Payload $payload) : Payload
    {
        $images = $this->filesystem->listContents($payload->getQueue()->file_name);
        return $payload->setImages($images);
    }
}