# shopee-product-scraper

`shopee-product-scraper` lets you scrape product infomation from shopee and outputs them in a json format.

Contents
========

* [Why?](#why)
* [Installation](#installation)
* [Usage](#usage)

### Why?

I made this to learn more about web scraping and at the same time use php as a script, not as a web page. 

### Installation
---
Install all the packages required.
```
composer install
```
Install chromedriver using bdi.
```
./vendor/bin/bdi driver:chromedriver
```
This will create a chromedriver file in the root project directory. Create a `drivers` directory in the root project directory and move the chromedriver file to the `drivers` directory.

### Usage
---
A simple usage would be.
```
php scraper.php mechanical keyboards
```
Which would output the retrieved data to ./data_scraped/mechanical_keyboards.json  
To enable verbose messages and only scrape products which has sales.
```
php scraper.php -verbose -has-sold mechanical keyboards
```
The program tries to read if the keyword has been searched before, and if it is will update the json file accordingly.  
See all commands and options.
```
php scraper.php --help
```