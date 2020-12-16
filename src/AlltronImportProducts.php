<?php

namespace Supsign\Alltron;

use App\Category;
use App\CategoryProduct;
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

	protected function writeMfIds() 
	{
        foreach ($this->soap->getProductSupplierInformation(['SupplierID' => 1]) AS $productSupplierInformation) {
            if (!$productSupplierInformation->ProductPurchaseNumber)
                continue;

            $productSupplier = ProductSupplier::where('supplier_product_id', $productSupplierInformation->ProductPurchaseNumber)
                ->with(['product'])
                ->first();

            if ($productSupplier) {
                $product = $productSupplier->product;
                $product->mf_product_number = $productSupplierInformation->ProductID;
                $product->save();
            }
        }
	}

	public function import() 
	{
		$catCount = Category::all()->count();

		$i = 1;

		foreach ($this->getData() AS $this->productData) {
			$ignore = false;

			if (isset($this->productData->Categories)) {
				$categoryId = null;

				foreach ($this->productData->Categories->Category AS $category) {
					$categoryData = ['name' => $category];

					if (isset($categoryId)) {
						$categoryData = array_merge($categoryData, ['parent_id' => $categoryId]);
					}

					$category = Category::firstOrCreate($categoryData);
					$categoryId = $category->id;

					if ($category->ignore) {
						$ignore = true;
					}
				}
			}

			if ($ignore OR $catCount === 0) {
				continue;
			}

			$productSupplier = ProductSupplier::firstOrNew(
				[
					'supplier_product_id' => $this->getProductDataValue('ProductId'), 
					'supplier_id' => 1
				]
			);

			$productData = array(
				'ean' => $this->getProductDataValue('EAN'),
				'warranty' => $this->getProductDataValue('Warranty'),
				'height' => $this->getProductDataValue('height'),
				'width' => $this->getProductsWeight('width'),
				'length' => $this->getProductDataValue('length'),
				'weight_brutto' => $this->getProductsWeight(),
				'is_active' => $this->getProductDataValue('isSellOut') === 'false' ? 1 : 0,
			);

			if (is_null($productSupplier->product_id)) {
				$product = Product::create($productData); 
			} else {
				$product = Product::find($productSupplier->product_id)->fill($productData);
				$product->save();
			}

			$productSupplier->product_id = $product->id;
			$productSupplier->stock = $this->getProductDataValue('Inventory');
			$productSupplier->last_seen = now();
			$productSupplier->save();

			$description = ProductDescription::updateOrCreate(
				['product_id' => $product->id],
				[
					'name' => $this->getProductDataValue('ProductName'),
					'subtitle' => $this->getProductDataValue('Productsubtitle'),
					'teaser' => $this->getProductDataValue('ProductLongDescription'),
				]
			);

			if (isset($categoryId)) {
				CategoryProduct::firstOrCreate([
					'product_id' => $product->id,
					'category_id' => $categoryId
				]);
			}

			$i++;
		}

		$this->writeMfIds();

		echo $i.' rows imported or updated';
	}
}
