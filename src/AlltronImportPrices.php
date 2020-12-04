<?php

namespace Supsign\Alltron;

use Config;
use Illuminate\Support\Facades\Storage;
use Supsign\LaravelXmlReader\XmlReader;

class AlltronImportPrices extends XmlReader
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
