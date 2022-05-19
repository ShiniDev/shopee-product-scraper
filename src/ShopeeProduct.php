<?php

namespace Shinidev;

class ShopeeProduct
{
    /**
     * The url of the product.
     *
     * @var string
     */
    public $url = "";

    /**
     * The image url of the product.
     *
     * @var string
     */
    public $imageUrl = "";

    /**
     * The name of the product.
     *
     * @var string
     */
    public $name;

    /**
     * The product price.
     *
     * @var int
     */
    public $price = 0;

    /**
     * The product currency.
     *
     * @var string
     */
    public $currency = "";

    /**
     * The original price of a discounted product.
     *
     * @var string
     */
    public $originalPrice = "";

    /**
     * The minimum price of the product.
     *
     * @var string
     */
    public $minPrice = "";

    /**
     * The maximum price of the product.
     *
     * @var string
     */
    public $maxPrice = "";

    /**
     * The store location of the product.
     *
     * @var string
     */
    public $location = "";

    /**
     * The number of sold of the product.
     *
     * @var string
     */
    public $sold = "";

    /**
     * Create a new shopee product instance.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Returns an array representation of the product.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'url' => $this->url,
            'imageUrl' => $this->imageUrl,
            'name' => $this->name,
            'currency' => $this->currency,
            'price' => $this->price,
            'originalPrice' => $this->originalPrice,
            'minPrice' => $this->minPrice,
            'maxPrice' => $this->maxPrice,
            'location' => $this->location,
            'sold' => $this->sold,
        ];
    }
}
