<?php

use Shinidev\ShopeeProductScraper;

require_once "vendor/autoload.php";

define('FILE_ROOT', __DIR__ . '/');

if (!isset($argv[1])) {
    echo 'Please provide a product to scrape';
    die;
}

$scraper = new ShopeeProductScraper(ShopeeProductScraper::parseArgument($argv));
$scraper->scrape();
