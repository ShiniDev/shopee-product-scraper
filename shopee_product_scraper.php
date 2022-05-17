<?php

use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;

require_once "vendor/autoload.php";

if (!isset($argv[1])) {
    echo 'Please provide a product to scrape';
    die;
}
$product_name = "";
for ($i = 1, $len = count($argv); $i < $len; ++$i) {
    $product_name .= $argv[$i];
    if ($i < $len - 1) {
        $product_name .= " ";
    }
}

$keyword = urlencode($product_name);
$file = str_replace(" ", "_", $product_name);
$products = [];
$products_index = 0;
$index = 0;
$top = "div.shopee-search-item-result__item a";
$name = "div.Cve6sh";

while (true) {
    $client = Client::createFirefoxClient();
    $url = "https://shopee.ph/search?keyword={$keyword}&page={$index}";
    echo $url;
    $client->request('GET', $url);
    $client->executeScript('window.scrollTo({top: document.body.scrollHeight, behavior: "smooth"})');
    try {
        $client->waitFor(".shopee-search-item-result__items", 2, 100);
    } catch (Exception $e) {
        break;
    }
    $client->executeScript('window.scrollTo({top: document.body.scrollHeight, behavior: "smooth"})');
    $crawler = $client->getCrawler();
    $crawler->filter("{$top}")->each(function ($node) use (&$products, &$products_index, $name) {
        $children = $node->children();
        $str = "No Value";
        $products[] = [
            'url_link' => "https://shopee.ph" . $node->attr('href'),
            'name' => $children->filter("{$name}")->text($str),
        ];
        ++$products_index;
    });
    // $crawler->filter('div.shopee-search-item-result__item a div.d5DWld')->each(function ($node) use (&$products, &$products_index) {
    //     $products[$products_index]['original_price'] = $node->text();
    //     ++$products_index;
    // });
    ++$index;
}

$file = str_replace(" ", "_", $product_name);
$json = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo 'Product Count: ' . count($products) . "\n";
file_put_contents("products/{$file}.json", $json);
