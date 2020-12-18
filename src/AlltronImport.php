<?php

namespace Supsign\Alltron;

use Illuminate\Support\Facades\Storage;
use Supsign\LaravelMfSoap\MyFactorySoapApi;
use Supsign\LaravelXmlReader\XmlReader;

class AlltronImport extends XmlReader
{
	protected $soap = null;

	public function __construct()
	{
		$this->soap = new MyFactorySoapApi;
	}

	public function downloadFile()
	{
	    (new AlltronFTP)
	        ->setLocalFile(Storage::path('imports/'.$this->sourceFile))
	        ->setRemoteFile($this->sourceFile)
	        ->downloadFile();

	    return $this;
	}
}
