<?php

// Edit & rename to config.php

/**
 * @todo use a proper config component
 */

$baseUrl = 'http://www.whatever.com';

$imageUrl = $baseUrl . '/media/catalog/product';

$csvDir = __DIR__ . '/csv/';

$csvSource = $csvDir . 'catalog_product_xxx.csv';

$csvDest = 'export_flat_catalog.csv'; // only the filename

$vat = 20;

$brandBlacklist = [''];