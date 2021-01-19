<?php

namespace Supsign\Alltron;

use Illuminate\Support\Facades\Storage;
use Supsign\LaravelXmlReader\XmlReader;

class AlltronImport extends XmlReader
{
	protected $soap = null;

	public function __construct()
	{
		$this->soap = resolve('MyFactorySoapApi');
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
