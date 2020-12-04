<?php

namespace Supsign\Alltron;

use App\Product;
use App\ProductDescription;
use App\ProductSupplier;
use Config;
use Illuminate\Support\Facades\Storage;
use Supsign\LaravelXmlReader\XmlReader;

class AlltronImportProducts extends XmlReader
{
	protected 
		$dataKey = 'product',
		$productData = null,
		$sourceFile = 'StandardV2_DE.xml';

	protected function getProductDataValue($key)
	{
		if (!isset($this->productData->$key) OR is_object($this->productData->$key)) {
			return null;
		}

		return $this->productData->$key;
	}

	public function import() 
	{
		$i = 0;

		foreach ($this->getData() AS $this->productData) {
			var_dump($this->productData);

			if (!$ean = $this->getProductDataValue('EAN')) {
				continue;
			}

			$product = Product::updateOrCreate(['EAN' => $ean]);
			$description = ProductDescription::updateOrCreate(
				['product_id' => $product->id],
				[
					'name' => $this->getProductDataValue('ProductName'),
					'subtitle' => $this->getProductDataValue('Productsubtitle'),
					'description' => $this->getProductDataValue('ProductLongDescription'),
				]
			);

			$productSupplier = ProductSupplier::updateOrCreate(
				['product_id' => $product->id, 'supplier_id' => 1],
				[
					'supplier_product_id' => $this->getProductDataValue('ProductId'),
					'stock' => $this->getProductDataValue('Inventory'),
				]
			);

			$i++;

			if ($i === 30)
				break;
		}

		var_dump($i);
	}
}
