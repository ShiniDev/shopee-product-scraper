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
        'ad' => 'div.Sh+UIZ',
    ];

    const baseUrl = "https://shopee.ph/";
    const failMsg = "Failed to get value";

    private $client;
    private $crawler;

    private static $verbose = false;

    private $shopeeProducts = [];
    private $page = 0;
    private $keyword = '';

    public function __construct(string $keyword)
    {
        $this->keyword = $keyword;
        if (self::$verbose) {
            echo "Reading existing keyword file...\n";
        }
        $this->readFile();
        if (self::$verbose) {
            echo "Initializing client...\n";
        }
        $this->client = Client::createFirefoxClient();
        if (self::$verbose) {
            echo "Initializing crawler...\n";
        }
        $this->crawler = $this->client->request('GET', self::baseUrl . "search?keyword={$keyword}&page={$this->page}");
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
                    default:
                        break;
                }
                break;
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
        $this->crawler->filter(self::pageClass['root'])->each(function ($node) {
            $children = $node->children();
            $shopeeProduct = new ShopeeProduct($children->filter(self::pageClass['name'])->text(self::failMsg));
            $shopeeProduct->url = self::baseUrl . $node->attr('href');
            try {
                $shopeeProduct->imageUrl = $children->filter(self::pageClass['image'])->attr('src');
            } catch (\Exception $e) {
            }
            $shopeeProduct->location = $children->filter(self::pageClass['location'])->text(self::failMsg);
            $shopeeProduct->sold = $children->filter(self::pageClass['sold'])->text(self::failMsg);
            if ($shopeeProduct->sold == "") {
                return;
            }
            $this->shopeeProducts[$shopeeProduct->name] = $shopeeProduct->toArray();
        });
    }

    private function scrollToBottom()
    {
        if (self::$verbose) {
            echo "Scrolling to bottom...\n";
        }
        $this->client->executeScript('window.scrollTo({top: document.body.scrollHeight, behavior: "smooth"})');
        $this->crawler = $this->client->getCrawler();
    }

    private function waitToLoad()
    {
        try {
            $this->client->waitFor(".shopee-search-item-result__items", 2, 100);
            $this->client->waitFor(".vc8g9F", 1, 1);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function nextPage()
    {
        $this->page++;
        $this->crawler = $this->client->request('GET', self::baseUrl . "search?keyword={$this->keyword}&page={$this->page}");
        if (self::$verbose) {
            echo "Page " . ($this->page + 1) . " loaded\n";
        }
    }

    private function outputJson()
    {
        $filename = str_replace(' ', '_', $this->keyword) . '.json';
        $json = json_encode($this->shopeeProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents(FILE_ROOT . "data_scraped/{$filename}", $json);
        if (self::$verbose) {
            echo "File data_scraped/{$filename} created\n";
        }
    }

    public function scrape()
    {
        while (true) {
            $this->scrollToBottom();
            if ($this->waitToLoad()) {
                $this->scrollToBottom();
                $this->crawl();
                $this->nextPage();
                if (self::$verbose) {
                    echo "Products Scraped: " . count($this->shopeeProducts) . "\n";
                }
            } else {
                break;
            }
        }
        $this->outputJson();
    }
}
