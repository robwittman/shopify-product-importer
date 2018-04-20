<?php

namespace App\Template;

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;
use Google_Client;
use League\Flysystem\Filesystem;
use App\Model\Sku;
use Psr\Log\LoggerInterface;

abstract class AbstractTemplate
{
    protected $queue;

    protected $shop;

    protected $template;

    protected $setting;

    protected $client;

    protected $filesystem;

    protected $logger;

    protected $matrix;

    protected $requiresMatrix = false;

    protected $isWholesale = false;

    public function __construct(Filesystem $filesystem, Google_Client $client, LoggerInterface $logger)
    {
        $this->filesystem = $filesystem;
        $this->client = $client;
        $this->logger = $logger;
    }

    public function __invoke(
        Queue $queue,
        Shop $shop,
        Template $template,
        Setting $setting = null
    ) {
        $this->queue = $queue;
        $this->shop = $shop;
        $this->template = $template;
        $this->setting = $setting;

        $this->execute();
    }

    public function execute()
    {
        throw new \Exception(
            "Template has not been setup correctly. Please implement execute()"
        );
    }

    public function setQueue(Queue $queue)
    {
        $this->queue = $queue;
        return $this;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function setShop(Shop $shop)
    {
        $this->shop = $shop;
        return $this;
    }

    public function getShop()
    {
        return $this->shop;
    }

    public function setTemplate(Template $template)
    {
        $this->template = $template;
        return $this;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function setSetting(Setting $setting)
    {
        $this->setting = $setting;
        return $this;
    }

    public function getSetting()
    {
        return $this->setting;
    }

    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        return $this;
    }

    public function getFilesystem()
    {
        return $this->filesystem;
    }

    public function setClient(Google_Client $client)
    {
        $this->client = $client;
        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get the list of images from Filesystem
     * @param  string $prefix
     * @return array
     */
    public function getImages($prefix)
    {
        $contents = $this->filesystem->listContents($prefix, true);
        $files = array_map(function($file) {
            return $file['path'];
        }, array_filter($contents, function($content) {
            return $content['type'] === 'file';
        }));
        $this->logger->debug("Found ".count($files)." files for {$prefix}");
        return $files;
    }

    public function generateSku()
    {
        $shopChunks = explode('-', explode('.', $this->shop->myshopify_domain)[0]);
        $skuStart = strtoupper(implode('', array_map(function($chunk) {
            return $chunk[0];
        }, $shopChunks)));
        $words = preg_split("/\s+/", $title);
        $pt = '';
        foreach ($words as $word) {
            $pt .= $word[0];
        }
        $its = 0;
        $originalSku = strtolower(str_replace(array(' ', ','), '', $title));
        do {
            if ($its > 0) {
                $check = $originalSku.$its;
            } else {
                $check = $originalSku;
            }

            $its++;
        } while ($res = $this->skuExists($check));

        return $check;
    }

    public function skuExists($sku)
    {
        try {
            $sku = Sku::where('sku', '=', $sku)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $obj = new Sku();
            $obj->sku = $sku;
            $obj->save();
            return false;
        }
        return true;
    }

    public function getProductSettings()
    {
        $tags = implode(',', array_merge(
            str_getcsv($this->queue->tags),
            str_getcsv($this->template->tags),
            str_getcsv($this->setting->tags)
        ));
        return array(
            'title' => $this->queue->title,
            'body_html' => $this->queue->description ?: $this->setting->description ?: $this->shop->description ?: $this->template->description,
            // 'tags' => $tags,
            'product_type' => $this->queue->product_type ?: $this->setting->product_type ?: $this->template->product_Type,
            'vendor' => $this->queue->vendor ?: $this->setting->vendor ?: $this->template->vendor,
            'variants' => array(),
            'images' => array()
        );
    }

    public function generateLiquidSku($skuTemplate, $product, $variant)
    {
        $template = new \Liquid\Template();
        $template->parse($skuTemplate);
        $sku = $template->render(array(
            'product' => $product,
            'shop' => $this->shop,
            'variant' => $variant,
            'file' => str_replace('.zip', '', $queue->file),
            // 'data' => $post,
            'queue' => $this->queue
        ));
        return trim($sku);
    }

    public function getSkuTemplate()
    {
        return $this->queue->sku ?:
            $this->setting->sku_template ?:
            $this->template->sku_template;
    }

    public function getVariantSku($sku, $garment, $color) {
        switch (strtolower($garment)) {
            case 'tank':
            case 'tee':
            case 'hoodie':
                break;
            case 'long sleeve':
                $garment = 'ls';
                break;
        }
        $color = str_replace(' ', '', $color);
        return strtolower($sku.$garment.$color);
    }

    public function getSku($size)
    {
        switch ($size) {
            case 'Small':
                return 'S';
            case 'Medium':
                return 'M';
            case 'Large':
                return 'L';
        }
        return $size;
    }

    public function callShopify($url, $method, $params)
    {
        $base = $this->generateUrl();
        $c = curl_init();
        if ($method == "GET") {
            $url = $url . "?" . http_build_query($params);
        } elseif ($method == "POST") {
            curl_setopt($c, CURLOPT_POST, 1);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($params));
        } else {
            curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($params));
        }
        curl_setopt($c, CURLOPT_URL, $base.$url);
        error_log($base.$url);
        curl_setopt($c, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
        ));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($c);
        $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        if(!in_array($code, [200,201])) {
            throw new \Exception("Shopify API response error. [$code] [$res]");
        }
        return json_decode($res);
    }

    public function generateUrl()
    {
        $key = $this->shop->api_key;
        $pass = $this->shop->password;
        $domain = $this->shop->myshopify_domain;
        return sprintf("https://%s:%s@%s", $key, $pass, $domain);
    }

    public function requiresMatrix()
    {
        return $this->requires_matrix;
    }

    public function setMatrix($matrix)
    {
        $this->matrix = $matrix;
    }

    public function getMatrix()
    {
        return $this->matrix;
    }

    public function isWholesale()
    {
        return $this->isWholesale;
    }
}
