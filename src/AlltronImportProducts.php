<?php

namespace Supsign\Alltron;

use App\Category;
use App\CategoryProduct;
use App\Manufacturer;
use App\Product;
use App\ProductDescription;
use App\ProductSupplier;
use App\Supplier;
use Exception;
use Illuminate\Support\Facades\Storage;
use Pdp\TopLevelDomains;

class AlltronImportProducts extends AlltronImport
{
	protected 
		$logFile = 'AlltronProductLog.txt',
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
		return get_object_vars($this->productData->$key)[0] ?? null;
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
		Storage::delete($this->logPath.$this->logFile);

		try {
			$this
				->importSuppliers()
				->writeLog('Start Downloading: '.$this->sourceFile)
				->downloadFile()
				->writeLog($this->sourceFile.' download complete');
		} catch (Exception $e) {
			$this->writeLog('Caught exception: '.$e->getMessage());
			return $this;
		}

		$filePath = Storage::path($this->sourceFolder.'/'.$this->sourceFile);
		$data = file_get_contents($filePath);
		file_put_contents($filePath, str_replace('<?xml version="1.1" encoding="UTF-8" standalone="no"?>', '<?xml version="1.0" encoding="UTF-8" standalone="no"?>', $data));

		try {
			$this
				->importProducts()
				->writeLog('Alltron Product Import finished');
		} catch (Exception $e) {
			$this->writeLog('Caught exception: '.$e->getMessage());
			return $this;
		}

		return $this;
	}

	public function importSuppliers()
	{
        foreach ($this->soap->getSuppliers() AS $supplier) {
            Supplier::updateOrCreate(
                ['my_factory_id' => (int)filter_var($supplier->SupplierNumber, FILTER_SANITIZE_NUMBER_INT)],
                ['name' => $supplier->Matchcode]
            );
        }

        return $this->writeLog('Lieferanten importiert.');
	}

	public function importProducts() 
	{
		$catCount = Category::all()->count();
		$data = $this->getData();
		$i = 0;

		foreach ($data AS $this->productData) {
			try {
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

				$this->writeLog('Starting to write Product: '.$this->getProductDataValue('ProductName').' - '.$this->getProductDataValue('MPN'));

				$i++;

				$productSupplier = ProductSupplier::firstOrNew([
					'supplier_product_id' => $this->getProductDataValue('ProductId'), 
					'supplier_id' => 1
				]);

				if (!$productSupplier->id)
					$productSupplier->main_supplier = 1;

				$productData = array(
					'ean' => $this->getProductDataValue('EAN'),
					'warranty' => $this->getProductDataValue('Warranty'),
					'height' => $this->getProductDataValue('height'),
					'width' => $this->getProductsWeight('width'),
					'length' => $this->getProductDataValue('length'),
					'weight_brutto' => $this->getProductsWeight(),
					'is_active' => $this->getProductDataValue('isSellOut') === 'false' ? 1 : 0,
					'manufacturer_number' => $this->getProductDataValue('MPN'),
					'manufacturer_url' => substr($this->getProductDataValue('ManufacturerProductUrl'), 0, 599),
				);

				if ($this->getManufacturerName()) {
					$productData['manufacturer_id'] = Manufacturer::firstOrCreate([
						'alltron_name' => $this->getManufacturerName()
					])->id;
				}

				if (is_null($productSupplier->product_id)) {
					$product = Product::create($productData); 
				} else {
					$product = Product::find($productSupplier->product_id)->fill($productData);
					$product->save();
				}

				$productSupplier->product_id = $product->id;
				// $productSupplier->stock = $this->getProductDataValue('Inventory');
				$productSupplier->save();
				$productSupplier->setLastSeen();

				ProductDescription::updateOrCreate(
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
			} catch (Exception $e) {
				$this->writeLog('Caught exception: '.$e->getMessage());
			}
		}

		return $this->writeLog($i.' rows imported or updated');
	}
}
