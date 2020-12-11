<?php

namespace Supsign\Alltron;

use App\Product;
use App\ProductDescription;
use App\ProductSupplier;
use Config;
use Illuminate\Support\Facades\Storage;
use Supsign\LaravelXmlReader\XmlReader;
use Supsign\LaravelMfSoap\MyFactorySoapApi;

class AlltronImportProducts extends XmlReader
{
	protected 
		$dataKey = 'product',
		$productData = null,
		$soap = null,
		$sourceFile = 'StandardV2_DE.xml';

	public function __construct()
	{
		$this->soap = new MyFactorySoapApi;
	}

	protected function getProductDataValue($key)
	{
		if (!isset($this->productData->$key) OR is_object($this->productData->$key)) {
			return null;
		}

		return $this->productData->$key;
	}

	protected function getProductsWeight()
	{
		switch ($this->getProductDataValue('ProductShippingWeightUnit')) {
			case 'kg': return $this->getProductDataValue('ProductShippingWeight');
			case 'g': return $this->getProductDataValue('ProductShippingWeight') / 1000;
		}
	}

	public function import() 
	{
		$i = 0;

		foreach ($this->getData() AS $this->productData) {
			if (!$ean = $this->getProductDataValue('EAN')) {
				continue;
			}

			$product = Product::updateOrCreate(
				['EAN' => $ean],
				[
					'warranty' => $this->getProductDataValue('Warranty'),
					'height' => $this->getProductDataValue('height'),
					'width' => $this->getProductsWeight('width'),
					'length' => $this->getProductDataValue('length'),
					'weight_brutto' => $this->getProductsWeight(),
				]
			);


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

			if ($i === 100)
				break;
		}

		var_dump($i);
	}
}
