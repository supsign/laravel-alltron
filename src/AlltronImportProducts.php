<?php

namespace Supsign\Alltron;

use App\Category;
use App\CategoryProduct;
use App\Manufacturer;
use App\Product;
use App\ProductDescription;
use App\ProductSupplier;
use App\Supplier;
use Illuminate\Support\Facades\Storage;
use Pdp\TopLevelDomains;

class AlltronImportProducts extends AlltronImport
{
	protected 
		$dataKey = 'product',
		$productData = null,
		$sourceFile = 'StandardV2_DE.xml',
		$topLevelDomains = null;

	public function __construct()
	{
		$this->topLevelDomains = TopLevelDomains::fromPath('https://data.iana.org/TLD/tlds-alpha-by-domain.txt');

		return parent::__construct();
	}

	protected function getManufacturerName()
	{
		if (is_null($this->getProductDataValue('ManufacturerProductUrl'))) {
			return null;
		}

		$name = ucfirst($this
			->topLevelDomains->resolve(parse_url($this->getProductDataValue('ManufacturerProductUrl'), PHP_URL_HOST))
				->secondLevelDomain()
					->toString());

		return $name ?: null;
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
		if (is_null($this->getProductDataValue('ProductShippingWeight'))) {
			return null;
		}

		switch ($this->getProductDataValue('ProductShippingWeightUnit')) {
			case 'kg': return $this->getProductDataValue('ProductShippingWeight');
			case 'g': return $this->getProductDataValue('ProductShippingWeight') / 1000;
		}
	}

	public function import() 
	{
		return $this
			->importSuppliers()
			->downloadFile()
			->importProducts()
			->writeMfIds();
	}

	public function importSuppliers()
	{
        foreach ($this->soap->getSuppliers() AS $supplier) {
            Supplier::updateOrCreate(
                ['my_factory_id' => (int)filter_var($supplier->SupplierNumber, FILTER_SANITIZE_NUMBER_INT)],
                ['name' => $supplier->Matchcode]
            );
        }

        return $this;
	}

	public function importProducts() 
	{
		$catCount = Category::all()->count();

		$i = 0;

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

			$i++;

			// if ($i == 10)
			// 	break;

			// var_dump(
			// 	$this->productData
			// );
			// echo '<hr>';
			// continue;

			$productSupplier = ProductSupplier::firstOrNew([
				'supplier_product_id' => $this->getProductDataValue('ProductId'), 
				'supplier_id' => 1
			]);

			$productData = array(
				'ean' => $this->getProductDataValue('EAN'),
				'warranty' => $this->getProductDataValue('Warranty'),
				'height' => $this->getProductDataValue('height'),
				'width' => $this->getProductsWeight('width'),
				'length' => $this->getProductDataValue('length'),
				'weight_brutto' => $this->getProductsWeight(),
				'is_active' => $this->getProductDataValue('isSellOut') === 'false' ? 1 : 0,
				'manufacturer_number' => $this->getProductDataValue('MPN'),
				'manufacturer_url' => $this->getProductDataValue('ManufacturerProductUrl'),
			);

			if ($this->getManufacturerName()) {
				$productData['manufacturer_id'] = Manufacturer::firstOrCreate([
					'name' => $this->getManufacturerName()
				])->id;
			}

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
		}

		echo $i.' rows imported or updated'.PHP_EOL;

		return $this;
	}

	public function writeMfIds() 
	{
		$i = 0;

        foreach ($this->soap->getProductSupplierInformation(['SupplierID' => 1]) AS $productSupplierInformation) {
            if (!$productSupplierInformation->ProductPurchaseNumber)
                continue;

            $productSupplier = ProductSupplier::where('supplier_product_id', $productSupplierInformation->ProductPurchaseNumber)
                ->with(['product'])
                ->first();

            if ($productSupplier) {
                $product = $productSupplier->product;
                $product->mf_product_id = $productSupplierInformation->ProductID;
                $product->save();
                $i++;
            }
        }

        echo $i.' rows found in MF'.PHP_EOL;

        return $this;
	}
}
