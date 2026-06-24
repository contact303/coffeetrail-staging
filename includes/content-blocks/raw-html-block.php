<?php
namespace MyListing\Src\Listing_Types\Content_Blocks;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Raw_Html_Block extends Base_Block {

    public function props() {
        $this->props['type'] = 'raw-html';
        $this->props['title'] = 'Raw HTML';
        $this->props['icon'] = 'mi code';
        $this->props['show_field'] = '';
        $this->allowed_fields = [ 'textarea' ];
    }

    public function get_editor_options() {
        $this->getLabelField();
        $this->getSourceField();
    }
}