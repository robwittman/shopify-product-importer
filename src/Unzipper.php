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
        '/(.*)Unisex Front Back/' => 'Front and Back',
        '/(.*)Unisex Tee/' => 'Tee',
        '/(.*)V-Neck Coverup/' => 'Coverup',
        '/(.*)Womens LS/' => 'Womens Long Sleeve',
        '/(.*)Womens Tank/' => 'Womens Tank template',
        '/(.*)Womens Tee/' => 'Teestuff',
        '/(.*)Womens V-Neck/' => 'VNECKS'
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
        var_dump($batch);
        $this->file_name = $batch->file_name;
        if ($this->local->has($this->file_name.'.zip')) {
            error_log("Deleting old file");
            $this->local->delete($this->file_name.'.zip');
        }
        $this->copyFileToLocal();
        $this->unzipFile();
        foreach ($this->local->listContents($this->file_name, true) as $file) {
            if ($file['extension'] === 'zip' && strpos($file['path'], 'MACOSX') === false) {
                $result = array();
                foreach ($this->templateMap as $pattern => $template) {
                    if (preg_match($pattern, $file['filename'], $result)) {
                        $match = trim($result[0]);
                        $queue = new Queue();
                        $queue->file_name = $batch->file_name;
                        $queue->title = $batch->title;
                        $queue->file = $batch->file;
                        $queue->template_id = 'wholesale_apparel';
                        $queue->sub_template_id = '';
                        $queue->shop_id = $batch->shop_id;
                        $queue->tags = $batch->tags;
                    }
                }
            }
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
