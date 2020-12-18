<?php

namespace Supsign\Alltron;

use Config;
use Illuminate\Support\Facades\Storage;

class AlltronImportPrices extends AlltronImport
{
	protected 
		$dataKey = 'item',
		$sourceFile = 'PreisdatenV2.XML';

	public function import() 
	{
		var_dump(
			count($this->getData())
		);

	}
}
