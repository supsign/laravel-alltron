<?php

namespace Supsign\Alltron;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Supsign\LaravelXmlReader\XmlReader;

class AlltronImport extends XmlReader
{
	protected 
		$logFile = 'AlltronLog.txt',
		$logPath = 'logs/',
		$downloadPath = 'imports/',
		$soap = null;

	public function __construct()
	{
		$this->soap = resolve('MyFactorySoapApi');
	}

	public function downloadFile()
	{
	    (new AlltronFTP)
	        ->setLocalFile(Storage::path($this->downloadPath.$this->sourceFile))
	        ->setRemoteDirectory('dataexport')
	        ->setRemoteFile($this->sourceFile)
	        ->downloadFile();

	    return $this;
	}

	public function writeLog($data)
	{
		if (!is_array($data)) {
			$data = [$data];
		}

		foreach ($data AS $line) {
			$this->writeLogLine($line);
		}

		return $this;
	}

	protected function writeLogLine(string $string)
	{	
		Storage::append($this->logPath.$this->logFile, '['.Carbon::now().'] '.$string);

		return $this;
	}
}
