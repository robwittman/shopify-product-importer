<?php

namespace App\Controller;

use App\Model\User;
use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use PhpAmqpLib\Message\AMQPMessage;

class Products
{
    protected $view;
    protected $flash;
    protected $rabbit;
    protected $s3;

    public function __construct($view, $flash, $rabbit)
    {
        $this->view = $view;
        $this->flash = $flash;
        $this->rabbit = $rabbit;
        $credentials = new \Aws\Credentials\Credentials(getenv("AWS_ACCESS_KEY"),getenv("AWS_ACCESS_SECRET"));
        $this->s3 = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'credentials' => $credentials
        ]);
    }

    public function show_form($request, $response, $arguments)
    {
        $user = User::find($request->getAttribute('user')->id);
        $shops = $user->shops;
        $templates = Template::all();

        return $this->view->render($response, 'product.html', array(
            'shops' => $shops,
            'templates' => $templates->toArray()
        ));
    }

    public function queue($request, $response, $arguments)
    {
        $queue = Queue::orderBy('created_at', 'desc')->take(50)->get();
        foreach ($queue as $record) {
            // TODO: Move shop_id to table column
            $data = json_decode($record->data, true);
            $shop = Shop::find($record->shop);
            $record->shop = $shop;
        }
        return $this->view->render($response, 'queue.html', array(
            'queue' => $queue
        ));
    }

    public function create($request, $response, $arguments)
    {
        $body = $request->getParsedBody();
        if (empty($_FILES['zip_file'])) {
            $this->flash->addMessage('error', 'You have to upload a .zip file!');
            return $response->withRedirect('/products');
        }

        $file = $_FILES['zip_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flash->addMessage('error', "There was an error uploading your .zip file. Code {$file['error']}");
            return $response->withRedirect('/products');
        }
        error_log(json_encode($body));
        if ($body['template'] == 'staple_wholesale_apparel') {
            $this->createBatchProduct($request, $response, $arguments);
        } else {
            $this->createSingleProduct($request, $response, $arguments);
        }
    }

    protected function createBatchProduct($request, $response, $arguments)
    {
        error_log("Batch creating");
        $file = $_FILES['zip_file'];
        $passedFileName = $file['name'];
        $hash = hash('sha256', uniqid(true));
        $path = "/tmp/{$hash}";

        $zip = new \ZipArchive();
        $zip->open($file['tmp_name']);
        $zip->extractTo($path);

        $shopId = reset($_POST['stores']);
        $shop = Shop::find($shopId);

        if (empty($shop)) {
            $this->flash->addMessage('error', "We couldnt find that shop");
            return $response->withRedirect('/products');
        }
        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $i = 0;
        foreach ($objects as $object) {
            error_log($object->getBaseName());
            if ($object->getExtension() === 'zip') {
                $i++;
                $zip = new \ZipArchive();
                $zip->open($object->getRealPath());
                $newPath = $path.'/'.$object->getBasename('zip');
                error_log($newPath);
                $zip->extractTo($newPath);
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($newPath));
                foreach ($files as $name => $file) {
                    if (!in_array($file->getExtension(), ['jpg', 'png'])) {
                        continue;
                    }
                    $name = str_replace('/tmp/', '', $name);
                    $this->s3->putObject([
                        'Bucket' => "shopify-product-importer",
                        'SourceFile' => $file,
                        'ACL' => 'public-read',
                        'Key' => str_replace(' ', '_', $name).'-'.$i,
                        'Content-Type' => 'application/zip_file'
                    ]);

                    $data = array(
                        'file' => $hash,
                        'post' => $request->getParsedBody(),
                        'file_name' => $passedFileName
                    );
                    $data['post']['shop'] = $shopId;
                    $queue = new Queue();
                    $queue->data = json_encode($data);
                    $queue->status = Queue::PENDING;
                    $queue->shop = $shopId;
                    $queue->file_name = $data['file'];
                    $queue->template = $data['post']['template'];
                    $queue->log_to_google = (int) $data['post']['log_to_google'];
                    $queue->save();
                }
            }
        }

        $this->flash->addMessage("message", "Product successfully added to queue.");
        return $response->withRedirect('products');
    }

    protected function createSingleProduct($request, $response, $arguments)
    {
        $start = time();
        $files = $request->getUploadedFiles();

        $hash = hash('sha256', uniqid(true));
        $path = "/tmp/{$hash}";

        $file = $_FILES['zip_file'];
        $passedFileName = $file['name'];
        $tmpName = $file['tmp_name'];
        $fileName = hash('sha256', uniqid(true));


        $zip = new \ZipArchive();
        $zip->open($tmpName);
        $zip->extractTo($path);

        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

        foreach($objects as $name => $object) {
            $name = str_replace('/tmp/','',$name);
            $this->s3->putObject([
                'Bucket' => "shopify-product-importer",
                'SourceFile' => $object,
                'ACL' => "public-read",
                'Key' => str_replace(' ', '_', $name),
                'Content-Type' => 'application/zip_file'
            ]);
        }
        $stores = $_POST['stores'];
        foreach ($stores as $shopId) {
            $shop = Shop::find($shopId);
            if (empty($shop)) {
                $this->flash->addMessage('error', "We couldnt find that shop");
                return $response->withRedirect('/products');
            }

            $data = array(
                'file' => $hash,
                'post' => $request->getParsedBody(),
                'file_name' => $passedFileName
            );
            $data['post']['shop'] = $shopId;
            $queue = new Queue();
            $queue->data = json_encode($data);
            $queue->status = Queue::PENDING;
            $queue->shop = $shopId;
            $queue->file_name = $data['file'];
            $queue->template = $data['post']['template'];
            $queue->log_to_google = (int) $data['post']['log_to_google'];
            $queue->save();
        }

        $elapsed_time = time() - $start;
        $this->flash->addMessage("message", "Product successfully added to queue. [Process took {$elapsed_time} seconds]");
        return $response->withRedirect('products');
    }

    public function restart_queue($request, $response, $args)
    {
        $post = $request->getParsedBody();
        $queue_id = $post['queue_id'];
        $queue = Queue::find($queue_id);
        $queue->status = Queue::PEDING;
        $queue->save();
        $this->flash->addMessage("message", "Queued product successfully restarted");
        return $response->withRedirect('queue');
    }
}
