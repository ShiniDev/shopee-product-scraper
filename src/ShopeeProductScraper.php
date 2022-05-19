<?php

namespace Shinidev;

use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;

class ShopeeProductScraper
{
    /**
     * The elements and classes used by shopee in their product page.
     * 
     * @var array
     */
    const pageClass = [
        ShopeeConstants::PRODUCT_ROOT => 'div.shopee-search-item-result__item a',
        ShopeeConstants::PRODUCT_IMAGE => 'img.vc8g9F',
        ShopeeConstants::PRODUCT_NAME => 'div.Cve6sh',
        ShopeeConstants::PRODUCT_PRICE => 'span.ZEgDH9',
        ShopeeConstants::PRODUCT_CURRENCY => 'span.recFju',
        ShopeeConstants::PRODUCT_ORIGINAL_PRICE => 'div.d5DWld',
        ShopeeConstants::PRODUCT_LOCATION => 'div.zGGwiV',
        ShopeeConstants::PRODUCT_SOLD => 'div.r6HknA',
        ShopeeConstants::PRODUCT_AD => 'div.F7xq8U',
    ];

    /**
     * The url of the shopee page.
     * 
     * @var string
     */
    const baseUrl = "https://shopee.ph/";

    /**
     * The default fail message.
     * 
     * @var string
     */
    const failMsg = "Failed to get value";

    /**
     * A flag wether to output verbose message.
     *
     * @var boolean
     */
    private static $verbose = false;

    /**
     * A flag wether to only scrape product with sales.
     *
     * @var boolean
     */
    private static $hasSales = false;

    /**
     * The browser client used to browse.
     *
     * @var Symfony\Component\Panther\Client
     */
    private $client;

    /**
     * The dom crawler used to scrape the page.
     *
     * @var Symfony\Component\Panther\DomCrawler\Crawler
     */
    private $crawler;

    /**
     * The array used to store scraped products.
     *
     * @var array
     */
    private $shopeeProducts = [];

    /**
     * The page number in the site.
     *
     * @var int
     */
    private $page = 0;

    /**
     * The product to search for.
     *
     * @var string
     */
    private $keyword = '';

    /**
     * Creates a shopee product scraper instance.
     *
     * @param string $keyword
     */
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

    /**
     * Parses the $args passed, sets options and returns the keyword.
     *
     * @param array $args
     * @return string
     */
    public static function parseArgument(array $args)
    {
        $keyword = "";
        for ($i = 1, $len = count($args); $i < $len; ++$i) {
            if (substr($args[$i], 0, 2) == '--') {
                $command = substr($args[$i], 2, strlen($args[$i]) - 1);
                switch ($command) {
                    case 'help':
                        echo "Usage: php scraper.php [OPTIONS] [KEYWORDS]\n\n";
                        echo "Options:\n";
                        echo "\t-verbose\t\t\tPrint verbose messages\n";
                        echo "\t-has-sold\t\t\tOnly scrape products with sales\n";
                        die();
                    default:
                        echo "Unknown command: " . $command . "\n";
                        break;
                }
            } else if (substr($args[$i], 0, 1) == '-') {
                $option = substr($args[$i], 1, strlen($args[$i]) - 1);
                switch ($option) {
                    case 'verbose':
                        self::$verbose = true;
                        break;
                    case 'has-sold':
                        self::$hasSales = true;
                        break;
                    default:
                        echo "Unknown option: " . $option . "\n";
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

    /**
     * Logs message and echoes them if verbose is true.
     *
     * @param $str
     * @return void
     */
    private function logMsg(string $str)
    {
        if (self::$verbose) {
            echo $str . "\n";
        }
    }

    /**
     * Initialize the shopee products properties, if the keyword has a json associated with.
     *
     * @return void
     */
    private function readFile()
    {
        $file = file_get_contents(FILE_ROOT . 'data_scraped/' . str_replace(" ", "_", $this->keyword) . '.json');
        if (!$file) {
            return false;
        }
        $this->shopeeProducts = json_decode($file, true);
    }

    /**
     * Scrapes the product page and stores the data in the shopeeProducts array.
     *
     * @return void
     */
    private function crawl()
    {
        $this->logMsg("Scraping products...");
        $this->crawler->filter(self::pageClass[ShopeeConstants::PRODUCT_ROOT])->each(function ($node) {
            $children = $node->children();
            $ad = $children->filter(self::pageClass[ShopeeConstants::PRODUCT_AD])->count() > 0;
            $sold = $children->filter(self::pageClass[ShopeeConstants::PRODUCT_SOLD])->text(self::failMsg);
            if ($ad) {
                return;
            }
            if (self::$hasSales && $sold == "") {
                return;
            }
            $shopeeProduct = new ShopeeProduct($children->filter(self::pageClass[ShopeeConstants::PRODUCT_NAME])->text(self::failMsg));
            $shopeeProduct->url = self::baseUrl . $node->attr('href');
            $shopeeProduct->location = $children->filter(self::pageClass[ShopeeConstants::PRODUCT_LOCATION])->text(self::failMsg);
            $shopeeProduct->sold = $sold;
            $shopeeProduct->currency = $children->filter(self::pageClass[ShopeeConstants::PRODUCT_CURRENCY])->eq(0)->text(self::failMsg);
            $shopeeProduct->price = $children->filter(self::pageClass[ShopeeConstants::PRODUCT_PRICE])->eq(0)->text(self::failMsg);
            if ($children->filter(self::pageClass[ShopeeConstants::PRODUCT_PRICE])->count() > 1) {
                $shopeeProduct->minPrice = $shopeeProduct->price;
                $shopeeProduct->maxPrice = $children->filter(self::pageClass[ShopeeConstants::PRODUCT_PRICE])->eq(1)->text(self::failMsg);
            }
            if ($children->filter(self::pageClass[ShopeeConstants::PRODUCT_ORIGINAL_PRICE])->count() > 0) {
                $shopeeProduct->originalPrice = $children->filter(self::pageClass[ShopeeConstants::PRODUCT_ORIGINAL_PRICE])->text(self::failMsg);
            }
            try {
                $shopeeProduct->imageUrl = $children->filter(self::pageClass[ShopeeConstants::PRODUCT_IMAGE])->attr('src');
            } catch (\Exception $e) {
            }
            $this->shopeeProducts[$shopeeProduct->name] = $shopeeProduct->toArray();
        });
    }

    /**
     * Executes a javascript code in the client, to scroll to the bottom.
     *
     * @return void
     */
    private function scrollToBottom()
    {
        $this->logMsg("Scrolling to bottom...");
        $this->client->executeScript('window.scrollTo({top: document.body.scrollHeight, behavior: "smooth"})');
        $this->crawler = $this->client->getCrawler();
    }

    /**
     * Waits for the products to load.
     *
     * @return bool
     */
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

    /**
     * Gets the next page.
     *
     * @return void
     */
    private function nextPage()
    {
        $this->page++;
        $this->crawler = $this->client->request('GET', self::baseUrl . "search?keyword=" . urlencode($this->keyword) . "&page={$this->page}");
        $this->logMsg("Page " . ($this->page + 1) . " loaded");
    }

    /**
     * Converts shopee products array to json and saves it to a file.
     *
     * @return void
     */
    private function outputJson()
    {
        $filename = str_replace(' ', '_', $this->keyword) . '.json';
        $json = json_encode($this->shopeeProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents(FILE_ROOT . "data_scraped/{$filename}", $json);
        $this->logMsg("File data_scraped/{$filename} created");
    }

    /**
     * The scraper lifecycle.
     *
     * @return void
     */
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
