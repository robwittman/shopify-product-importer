<?php

namespace App\Template;

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

interface TemplateInterface
{
    public function __invoke(
        Queue $queue,
        Shop $shop,
        Template $template,
        Setting $setting = null
    );

    /**
     * Execute a queued job, and return a product ID
     * @return integer Id of the created product
     */
    public function execute();
}
