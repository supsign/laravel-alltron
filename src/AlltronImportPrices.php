<?php

namespace Supsign\Alltron;

use Config;
use Illuminate\Support\Facades\Storage;

class AlltronImportPrices
{
	protected $sourceFile = null;

	public function __construct()
	{
		$this->sourceFile = Storage::path('imports/PreisdatenV2.XML');
	}
}
