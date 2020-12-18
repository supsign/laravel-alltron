<?php

namespace Supsign\Alltron;

use App\Price;
use App\ProductSupplier;
use App\Vat;
use Illuminate\Support\Facades\Storage;

class AlltronImportPrices extends AlltronImport
{
	protected 
		$dataKey = 'item',
		$sourceFile = 'PreisdatenV2.XML';

	public function import() 
	{
		$this->downloadFile();

		$i = 0;

		foreach ($this->getData() AS $entry) {

			$productSupplier = ProductSupplier::where('supplier_product_id', $entry->LITM)->first();

			if (!$productSupplier)
				continue;

			$vat = VAT::where('rate', $entry->price->VATR)->first();

			if (!$vat) {
				var_dump($vat, $entry->price->VATR);

				throw new \Exception('Tax Rate not found', 1);
			}

			$price = Price::firstOrCreate([
				'product_supplier_id' => $productSupplier->id,
				'amount' => $entry->price->EXPR,
				'vat_id' => $vat->id,
			]);

			$i++;

			// if ($i == 500)
			// 	break;	
		}

		echo $i.' prices imported or update'.PHP_EOL;
	}
}
