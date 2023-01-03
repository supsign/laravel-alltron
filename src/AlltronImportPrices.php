<?php

namespace Supsign\Alltron;

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
		if (!$this->tracker->readyToRun()) {
			return $this;
		}

		$this->tracker->start();
		Storage::delete($this->logPath.$this->logFile);

		try {
			$this->importPrices();
		} catch (Exception $e) {
			$this->writeLog('Caught exception: '.$e->getMessage());
			$this->tracker->stop();

			return $this;
		}

		$this->tracker->complete();

		return $this;
	}

	public function importPrices() 
	{
		$this
			->writeLog('Start Downloading: '.$this->sourceFile)
			->downloadFile()
			->writeLog($this->sourceFile.' download complete');

		$i = 0;
		$this->tracker->parsing();
		$data = $this->getData();
		$this->tracker->setProgressTarget(count($data))->importing();

		foreach ($data AS $entry) {
			$this->tracker->progress();

			try {
				$productSupplier = ProductSupplier::where([
					'supplier_product_id' => $entry->LITM,
					'supplier_id' => 1
				])->first();

				if (!$productSupplier OR !$productSupplier->product->mf_product_id) {
					continue;
				}

				$this->writeLog('Starting to write Price for: '.$productSupplier->product->name.' - '.$productSupplier->supplier_product_id);

				$productSupplier->product->recommended_retail_price = (float)$entry->price->ECPR;
				$productSupplier->product->save();

				$vat = Vat::where('rate', $entry->price->VATR)->first();

				if (!$vat) {
					throw new Exception('Tax Rate "'.$entry->price->VATR.'" not found', 1);
				}

				$i++;

				if ($productSupplier->prices->last() && $productSupplier->prices->last()->amount == $entry->price->EXPR) {
					continue;
				}

				$productSupplier->prices()->create([
					'amount' => $entry->price->EXPR,
					'vat_id' => $vat->id,
				]);
			} catch (Exception $e) {
				$this->writeLog('Caught exception: '.$e->getMessage());
				$this->tracker->error();
			}
		}

		$this->writeLog($i.' prices imported or update');

		return $this;
	}
}
