<?php

namespace App;

use League\Flysystem\MountManager;
use App\Model\BatchUpload;

class Unzipper
{
    protected $mountManager;

    protected $s3;

    protected $local;

    protected $templateMap = array(

    );

    protected $file_name;

    public function __construct(MountManager $mountManager)
    {
        $this->mountManager = $mountManager;
        $this->local = $this->mountManager->getFilesystem('local');
        $this->s3 = $this->mountManager->getFilesystem('s3');
    }

    public function process(BatchUpload $batch)
    {
        $this->file_name = $batch->file_name;
        if ($this->local->has($this->file_name.'.zip')) {
            error_log("Deleting old file");
            $this->local->delete($this->file_name.'.zip');
        }
        $this->copyFileToLocal();
        $this->unzipFile();
        foreach ($this->local->listContents($this->file_name) as $file) {
            var_dump($file);
        }
        return true;
    }

    public function copyFileToLocal()
    {
        error_log("Copying file");
        $this->mountManager->copy("s3://{$this->file_name}", "local://{$this->file_name}.zip");
        error_log("File copied");
    }

    public function unzipFile()
    {
        $zip = new \ZipArchive();
        $zip->open(DIR."/uploads/{$this->file_name}.zip");
        $zip->extractTo(DIR."/uploads/{$this->file_name}");
    }
}
