<?php

require 'vendor/autoload.php';
require 'config.php';
require 'utils.php';

use League\Csv\Reader,
    League\Csv\Writer;

use League\Flysystem\Filesystem,
    League\Flysystem\Adapter\Local as Adapter;

// Merge config with command lines
$args = CommandLine::parseArgs($_SERVER['argv']);

$baseUrl = !empty($args['b']) ? $args['b'] : $baseUrl;
$imageUrl = !empty($args['i']) ? $args['i'] : $imageUrl;
$csvDir = !empty($args['f']) ? $args['f'] : $csvDir;
$csvSource = !empty($args['s']) ? $args['s'] : $csvSource;
$csvDest = !empty($args['d']) ?$args['d'] : $csvDest;

// Spread bundle & simple products w/ relevant fields
$csvFile = Reader::createFromPath($csvSource);
$csvRows = $csvFile->setOffset(1)->fetchAll();

// Separate bundle from simple products, to later find & merge data from bundles in simple products that do not display individually
foreach($csvRows as $row) {
    // filter out unnecessary rows
    if ((!is_array($row)) || (empty($row[0])) || (($row[3] !== 'simple') && ($row[3] !== 'bundle'))) { continue; }
    // blacklist
    if(in_array(getEmptyOrFormat(strrchr($row[4], "/"), substr(strrchr($row[4], "/"), 1)), $brandBlacklist)) { continue; }

    if ($row[3] === 'bundle') {
        $bundle[] = [
            'brand' => getEmptyOrFormat(strrchr($row[4], "/"), substr(strrchr($row[4], "/"), 1)),
            'category' => getEmptyOrFormat(strripos($row[4], '/'), substr($row[4], 0, strripos($row[4], '/'))),
            'name' => getEmptyOrFormat($row[33]),
            'description' => $row[40] . '<br><br>' . $row[15],
            'image' => getEmptyOrFormat($row[21], $imageUrl . $row[21]),
            'url_merchant' => getEmptyOrFormat($row[52], $baseUrl . '/' . strtolower(str_ireplace(' ', '-',$row[4])) . '/' . $row[52])
        ];
    }
    elseif ($row[3] === 'simple') {
        $simple[] = [
            'sku' => $row[0],
            'brand' => getEmptyOrFormat(strrchr($row[4], "/"), substr(strrchr($row[4], "/"), 1)),
            'category' => substr($row[4], 0, strripos($row[4], '/')),
            'name' => getEmptyOrFormat($row[33]),
            'product_name' => substr($row[33], 0, (strripos($row[33], '-') - 1)),
            'description' => $row[40] . '<br><br>' . $row[15],
            'image' => getEmptyOrFormat($row[21], $imageUrl . $row[21]),
            'url_merchant' => getEmptyOrFormat($row[52], $baseUrl . '/' . strtolower(str_ireplace(' ', '-',$row[4])) . '/' . $row[52]),
            'price_exl' => ($row[38] * 100 / ($vat + 100)),
            'price_incl' => $row[38],
            'weight' => getEmptyOrFormat($row[54])
        ];
    }
}

echo 'Total lines : ' . count($csvRows) . PHP_EOL;
echo 'Bundle found : ' . count($bundle) . PHP_EOL;
echo 'Simple found : ' . count($simple) . PHP_EOL;

unset($csvFile, $csvRows, $row);

// Create the CSV
$csvNew = Writer::createFromFileObject(new SplTempFileObject);

$csvNew ->setNullHandlingMode(Writer::NULL_AS_EMPTY)
        ->setOutputBOM(Reader::BOM_UTF8)
        ->setNewline("\r\n");

// Put header
$csvNew->insertOne(
    [
        'sku',
        'name',
        'brand',
        'category',
        'price',
        'price_ttc',
        'description',
        'description_is_html',
        'image_urls',
        'merchant_url',
        'weight'
    ]
);

// Construct products & put them in the CSV
$count = 0;
foreach($simple as $sku => $product) {

    // merge bundle data in product if one found
    $count++;
    $bundleKey = searchForId($product['product_name'], $bundle);
    $productToExport = is_null($bundleKey) ? $product : array_merge($product, $bundle[$bundleKey]);
    $csvNew->insertOne(
        [
            $productToExport['sku'],
            $productToExport['name'],
            $productToExport['brand'],
            $productToExport['category'],
            $productToExport['price_exl'],
            $productToExport['price_incl'],
            $productToExport['description'],
            /* description is html */ 1,
            $productToExport['image'],
            $productToExport['url_merchant'],
            $productToExport['weight']
        ]
    );
}

echo 'Products exported : ' . $count . PHP_EOL;

// Write the CSV
@unlink($csvDir . $csvDest);
$filesystem = new Filesystem(new Adapter($csvDir));
$filesystem->write($csvDest, $csvNew->__toString());

exit('Export done');