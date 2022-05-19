<?php

namespace Shinidev;

class ShopeeProduct
{
    public $url;
    public $imageUrl;
    public $name;
    public $price;
    public $currency;
    public $originalPrice;
    public $minPrice;
    public $maxPrice;
    public $location;
    public $sold;

    public function __construct($name)
    {
        $this->name = $name;
        $this->url = '';
        $this->imageUrl = '';
        $this->price = '';
        $this->currency = '';
        $this->originalPrice = '';
        $this->minPrice = '';
        $this->maxPrice = '';
        $this->location = '';
        $this->sold = '';
    }

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
