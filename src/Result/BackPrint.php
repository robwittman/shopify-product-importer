<?php

namespace App\Result;

class BackPrint extends BaseResult
{
    public function export()
    {
        return array(
            $this->product_name,
            $this->garment_name,
            $this->product_fulfiller_code,
            $this->garment_color,
            $this->product_sku,
            $this->shopify_product_admin_url,
            $this->back_print_file_url,
            $this->integration_status,
            $this->date
        );
    }

    public function getSheetName()
    {
        return 'Back Print';
    }
}
