<?php

namespace App\Queue;

use App\Model\Queue;
use Google_Client;
use App\Template as Templates;
use App\Model\Template;
use App\Model\Shop;
use App\Model\Setting;
use Psr\Log\LoggerInterface;
use League\Flysystem\Filesystem;

class QueueProcessor
{
    private $client;

    private $filesystem;

    private $logger;

    private $functionMap = [
        'wholesale_apparel'     => Templates\WholesaleApparel::class,
        'wholesale_tumbler'     => Templates\WholesaleTumbler::class,
        'hats'                  => Templates\Hats::class,
        'stemless'              => Templates\Stemless::class,
        'single_product'        => Templates\SingleProduct::class,
        'drinkware'             => Templates\Drinkware::class,
        'uv_drinkware'          => Templates\UvDrinkware::class,
        'donation_uv_tumbler'   => Templates\DonationUvTumbler::class,
        'flasks'                => Templates\Flasks::class,
        'baby_body_suit'        => Templates\BabyBodySuit::class,
        'raglans'               => Templates\Raglans::class,
        'front_back_pocket'     => Templates\FrontBackPocket::class,
        'christmas'             => Templates\Christmas::class,
        'hats_masculine'        => Templates\MasculineHats::class,
        'grey_collection'       => Templates\GreyCollection::class
    ];

    public function __construct(Filesystem $filesystem, Google_Client $client, LoggerInterface $logger)
    {
        $this->filesystem = $filesystem;
        $this->client = $client;
        $this->logger = $logger;
        $this->logger->debug("Queue Processor loaded");
    }

    public function process(Queue $queue)
    {
        // $queue->start();

        try {
            $template = Template::where('handle', $queue->template_id)->firstOrFail();
            $shop = Shop::find($queue->shop_id);
            $setting = Setting::where(array(
                'template_id' => $template->id,
                'shop_id' => $shop->id
            ))->first();
            $class = $this->getTemplateHandler($queue->template_id);
            $this->logger->debug("Launching {$queue->template_id} using ".get_class($class));
            if ($class->requiresMatrix()) {
                if ($class->isWholesale()) {
                    $class->setMatrix(
                        $this->loadWholesaleMatrix()
                    );
                } else {
                    $class->setMatrix(
                        $this->loadMatrix()
                    );
                }
            }
            $res = $class($queue, $shop, $template, $setting);

            $queue->finish($res);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            if ($message = json_decode($e->getMessage())) {
                $queue->fail($message->error->message);
            } else {
                $queue->fail($e->getMessage());
            }
        }
    }

    protected function getTemplateHandler($template)
    {
        if (!array_key_exists($template, $this->functionMap)) {
            throw new \Exception("Invalid template {$template} provided");
        }
        $class = new $this->functionMap[$template](
            $this->filesystem,
            $this->client,
            $this->logger
        );
        return $class;
    }

    public function loadMatrix()
    {
        return json_decode(file_get_contents(DIR.'/src/matrix.json'), true);
    }

    public function loadWholesaleMatrix()
    {
        return  json_decode(file_get_contents(DIR.'/src/wholesale.json'), true);
    }
}
