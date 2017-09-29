<?php

namespace App\Result;

abstract class BaseResult
{
    protected $product_name;

    protected $garment_name;

    protected $product_fulfiller_code;

    protected $garment_color;

    protected $product_sku;

    protected $shopify_product_admin_url;

    protected $front_print_file_url;

    protected $integration_status = null;

    protected $date;

    protected $back_print_url;

    public function __construct(array $data = array())
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            } else {
                throw new \Exception("Undefined property ".get_called_class()."::".$key);
            }
        }
    }

    abstract public function export();

    abstract public function getSheetName();
}
