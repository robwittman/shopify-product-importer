<?php

namespace App\Controller;

use App\Model\User;
use App\Model\Queue;
use App\Model\Shop;
use App\Model\BatchUpload;
use App\Model\Template;
use PhpAmqpLib\Message\AMQPMessage;
use League\Flysystem\Filesystem;
use Aws\Sqs\SqsClient;

class Products
{
    protected $view;
    protected $flash;
    protected $rabbit;
    protected $s3;
    protected $filesystem;
    protected $queue;

    public function __construct($view, $flash, $rabbit, Filesystem $filesystem, SqsClient $queue)
    {
        $this->view = $view;
        $this->flash = $flash;
        $this->rabbit = $rabbit;
        $this->filesystem = $filesystem;
        $this->queue = $queue;
    }

    public function show_form($request, $response, $arguments)
    {
        $user = User::find($request->getAttribute('user')->id);
        $shops = $user->shops;
        $templates = Template::with('sub_templates')->get();

        return $this->view->render($response, 'product.html', array(
            'user' => $user,
            'shops' => $shops,
            'templates' => $templates
        ));
    }

    public function queue($request, $response, $arguments)
    {
        $queue = Queue::orderBy('created_at', 'desc')->take(50)->get();
        foreach ($queue as $record) {
            // TODO: Move shop_id to table column
            $data = $record->data;
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

        $this->createSingleProduct($request, $response, $arguments);

        $this->flash->addMessage("message", "Product successfully added to queue.");
        return $response->withRedirect('/products');
    }

    protected function createSingleProduct($request, $response, $arguments)
    {
        $post = $request->getParsedBody();
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
            $this->filesystem->write(str_replace(' ','_',$name), $object);
        }
        $shopId = $post['shop'];
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
        $queue->data = $data;
        $queue->status = Queue::PENDING;
        $queue->shop_id = $shopId;
        $queue->file_name = $data['file'];
        $queue->template_id = $data['post']['template'];
        $queue->log_to_google = (int) $data['post']['log_to_google'];
        $queue->vendor = $post['vendor'];
        $queue->product_type = $post['product_type'];
        $queue->title = $post['product_title'];
        $queue->file = $data['file_name'];
        $queue->tags = $post['tags'];
        $queue->showcase_color = $post['default_color'];
        $queue->showcase_product = $post['default_product'];
        $queue->print_type = $post['print_type'];
        $queue->sub_template_id = $post['sub_template'];
        $queue->save();

        $elapsed_time = time() - $start;
        return;
    }

    public function restart_queue($request, $response, $args)
    {
        $post = $request->getParsedBody();
        $queue_id = $post['queue_id'];
        $queue = Queue::find($queue_id);
        $queue->status = Queue::PENDING;
        $queue->save();
        $this->flash->addMessage("message", "Queued product successfully restarted");
        return $response->withRedirect('/queue');
    }

    public function batch($request, $response, $args)
    {
        $user = User::find($request->getAttribute('user')->id);
        $shops = $user->shops;
        $templates = Template::with('sub_templates')->get();

        if ($request->isPost()) {
            $post = $request->getParsedBody();
            $file = $_FILES['file'];
            $hash = hash('sha256', uniqid(true));
            $this->filesystem->put($hash, file_get_contents($file['tmp_name']));
            $batch = new BatchUpload();
            $batch->file_name = $hash;
            $batch->file = $file['name'];
            $batch->title = $post['product_title'];
            $batch->shop_id = $post['shop'];
            $batch->template_id = $post['template'];
            $batch->tags = $post['tags'];
            $batch->showcase_color = $post['showcase_color'];
            $batch->showcase_product = $post['showcase_product'];
            $batch->save();
            $params = [
                'DelaySeconds' => 10,
                'MessageBody' => json_encode($batch),
                'QueueUrl' => getenv('UPLOAD_QUEUE_URL')
            ];
            $result = $this->queue->sendMessage($params);
        }
        return $this->view->render($response, 'batch.html', array(
            'user' => $user,
            'shops' => $shops,
            'templates' => $templates
        ));
    }
}
