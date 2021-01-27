<?php

namespace Supsign\Alltron;

use App\Price;
use App\ProductSupplier;
use App\Vat;
use Exception;
use Illuminate\Support\Facades\Storage;

class AlltronImportPrices extends AlltronImport
{
	protected 
		$logFile = 'AlltronPriceLog.txt',
		$dataKey = 'item',
		$sourceFile = 'PreisdatenV2.XML';

	public function import()
	{
		try {
			$this->importPrices();
		} catch (Exception $e) {
			$this->tracker->stop();
		}

		return $this;
	}

	public function importPrices() 
	{
		$this->tracker->start();
		$this
			->writeLog('Start Downloading: '.$this->sourceFile)
			// ->downloadFile()
			->writeLog($this->sourceFile.' download complete');

		$i = 0;

		foreach ($this->getData() AS $entry) {
			try {
				$productSupplier = ProductSupplier::where('supplier_product_id', $entry->LITM)->first();

				if (!$productSupplier)
					continue;

				$this->writeLog('Starting to write Price for: '.$productSupplier->product->name.' - '.$productSupplier->supplier_product_id);

				$productSupplier->product->recommended_retail_price = $entry->price->ECPR;
				$productSupplier->product->save();

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
			} catch (Exception $e) {
				$this->writeLog('Caught exception: '.$e->getMessage());
			}
		}

		$this->writeLog($i.' prices imported or update');
		$this->tracker->complete();
	}
}
