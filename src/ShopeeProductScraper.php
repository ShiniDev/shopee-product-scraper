<?php

namespace Shinidev;

use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;

class ShopeeProductScraper
{
    const pageClass = [
        'root' => 'div.shopee-search-item-result__item a',
        'image' => 'img.vc8g9F',
        'name' => 'div.Cve6sh',
        'price' => 'span.ZEgDH9',
        'currency' => 'span.recFju',
        'original_price' => 'div.d5DWld',
        'location' => 'div.zGGwiV',
        'sold' => 'div.r6HknA',
        'ad' => 'div.F7xq8U',
    ];

    const baseUrl = "https://shopee.ph/";
    const failMsg = "Failed to get value";

    private $client;
    private $crawler;

    private static $verbose = false;
    private static $hasSales = false;

    private $shopeeProducts = [];
    private $page = 0;
    private $keyword = '';

    public function __construct(string $keyword)
    {
        $this->keyword = $keyword;
        $this->logMsg("Reading existing keyword file...");
        $this->readFile();
        $this->logMsg("Initializing client...");
        $this->client = Client::createChromeClient(null, [
            "--headless",
            "--disable-gpu"
        ]);
        $this->logMsg("Initializing crawler...");
        $this->crawler = $this->client->request('GET', self::baseUrl . "search?keyword=" . urlencode($this->keyword) . "&page={$this->page}");
    }

    public function logMsg($str)
    {
        if (self::$verbose) {
            echo $str . "\n";
        }
    }

    public static function parseArgument(array $args)
    {
        $keyword = "";
        for ($i = 1, $len = count($args); $i < $len; ++$i) {
            if (substr($args[$i], 0, 2) == '--') {
                $option = substr($args[$i], 2, strlen($args[$i]) - 1);
                switch ($option) {
                    case 'verbose':
                        self::$verbose = true;
                        break;
                    case 'has-sold':
                        self::$hasSales = true;
                        break;
                    default:
                        break;
                }
                continue;
            }
            $keyword .= $args[$i];
            if ($i < $len - 1) {
                $keyword .= " ";
            }
        }
        $keyword = trim($keyword);
        return $keyword;
    }

    private function readFile()
    {
        $file = file_get_contents(FILE_ROOT . 'data_scraped/' . str_replace(" ", "_", $this->keyword) . '.json');
        if (!$file) {
            return false;
        }
        $this->shopeeProducts = json_decode($file, true);
    }

    private function crawl()
    {
        $this->logMsg("Scraping products...");
        $this->crawler->filter(self::pageClass['root'])->each(function ($node) {
            $children = $node->children();
            $ad = $children->filter(self::pageClass['ad'])->count() > 0;
            $sold = $children->filter(self::pageClass['sold'])->text(self::failMsg);
            if ($ad) {
                return;
            }
            if (self::$hasSales && $sold == "") {
                return;
            }
            $shopeeProduct = new ShopeeProduct($children->filter(self::pageClass['name'])->text(self::failMsg));
            $shopeeProduct->url = self::baseUrl . $node->attr('href');
            $shopeeProduct->location = $children->filter(self::pageClass['location'])->text(self::failMsg);
            $shopeeProduct->sold = $sold;
            $shopeeProduct->currency = $children->filter(self::pageClass['currency'])->eq(0)->text(self::failMsg);
            $shopeeProduct->price = $children->filter(self::pageClass['price'])->eq(0)->text(self::failMsg);
            if ($children->filter(self::pageClass['price'])->count() > 1) {
                $shopeeProduct->minPrice = $shopeeProduct->price;
                $shopeeProduct->maxPrice = $children->filter(self::pageClass['price'])->eq(1)->text(self::failMsg);
            }
            if ($children->filter(self::pageClass['original_price'])->count() > 0) {
                $shopeeProduct->originalPrice = $children->filter(self::pageClass['original_price'])->text(self::failMsg);
            }
            try {
                $shopeeProduct->imageUrl = $children->filter(self::pageClass['image'])->attr('src');
            } catch (\Exception $e) {
            }
            $this->shopeeProducts[$shopeeProduct->name] = $shopeeProduct->toArray();
        });
    }

    private function scrollToBottom()
    {
        $this->logMsg("Scrolling to bottom...");
        $this->client->executeScript('window.scrollTo({top: document.body.scrollHeight, behavior: "smooth"})');
        $this->crawler = $this->client->getCrawler();
    }

    private function waitToLoad()
    {
        try {
            $this->logMsg("Waiting for items to load...");
            $this->client->waitFor(".shopee-search-item-result__items", 5, 100);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function nextPage()
    {
        $this->page++;
        $this->crawler = $this->client->request('GET', self::baseUrl . "search?keyword=" . urlencode($this->keyword) . "&page={$this->page}");
        $this->logMsg("Page " . ($this->page + 1) . " loaded");
    }

    private function outputJson()
    {
        $filename = str_replace(' ', '_', $this->keyword) . '.json';
        $json = json_encode($this->shopeeProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents(FILE_ROOT . "data_scraped/{$filename}", $json);
        $this->logMsg("File data_scraped/{$filename} created");
    }

    public function scrape()
    {
        while (true) {
            $this->scrollToBottom();
            if ($this->waitToLoad()) {
                $this->scrollToBottom();
                $this->crawl();
                $this->outputJson();
                $this->nextPage();
                $this->logMsg("Products Scraped: " . count($this->shopeeProducts) . "\n");
            } else {
                break;
            }
        }
    }
}
