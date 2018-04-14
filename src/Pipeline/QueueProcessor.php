<?php

namespace App\Pipeline;

use App\Model\Shop;
use App\Model\Queue;
use App\Model\Template;
use App\Model\Setting;
use League\Pipeline\Pipeline;
use App\Pipeline\Product;
use App\Pipeline\Strategy;
use App\Pipeline\AddSku;
use App\Pipeline\ProductMetaContainer;
use Shopify\PrivateApi;
use Shopify\Service\ProductService;
use Aws\S3\Client;

class QueueProcessor
{
    protected $queue;

    protected $s3;

    public function __construct(Queue $queue, Client $s3)
    {
        $this->s3 = $s3;
        $this->queue = $queue;
    }

    public function process()
    {
        $template = Template::where('handle', $this->queue->template)->firstOrFail();
        $shop = Shop::find($queue->shop);
        $setting = Setting::where(array(
            'template_id' => $template->id,
            'shop_id' => $queue->shop
        ))->first();

        $images = $this->getImages($queue->file_name);

        $strategy = $this->getStrategy($this->queue);
        $metaContainer = new ProductMetaContainer();
        $metaContainer
            ->setShop($shop)
            ->setTemplate($template)
            ->setSettings($setting)
            ->setPostData(json_decode($queue->data, true))
            ->setFile()
            ->setImages($images)
            ->setQueue($queue);

        $api = new PrivateApi(array(
            'api_key' => $shop->api_key,
            'password' => $shop->password,
            'shared_secret' => $shop->shared_secret,
            'myshopify_domain' => $shop->myshopify_domain
        ));
        $service = new ProductService($api);
        $pipeline = (new Pipeline)
            ->pipe(new Product\SetVendor)
            ->pipe(new Product\SetBodyHtml)
            ->pipe(new Product\SetTags)
            ->pipe(new Product\SetTitle)
            ->pipe(new Product\SetProductType)
            ->pipe(new Product\SetVariants($strategy))
            ->pipe(new Product\SetDefaultVariant)
            ->pipe(new AddSku)
            ->pipe(new Product\CreateProduct($service)
            ->pipe(new Product\MapImages($service);

        try {
            $res = $pipeline->process($metaContainer);
        } catch (\Exception $e) {
            $queue->fail($e->getMessage());
            return false;
        }

        $queue->finish($res);

        // if (logToGoogle) {
        //     queueIt
        // }
    }

    protected function getImages($fileName)
    {
        $objects = $this->s3->getIterator('ListObjects', array(
            "Bucket" => "shopify-product-importer",
            "Prefix" => $fileName
        ));
        $res = array();
        foreach ($objects as $object) {
            $key = $object["Key"];
            if (strpos($key, "MACOSX") || strpos($key, "Icon^M")) {
                continue;
            }
            if (!in_array(pathinfo($key, PATHINFO_EXTENSION), array(
                'jpg',
                'png',
                'jpeg'
            ))) {
                continue;
            }
            $res[] = $object;
        }
        return array_map(function($object) {
            return $object["Key"];
        }, $res);
    }
}
