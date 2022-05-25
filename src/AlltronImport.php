<?php

namespace Supsign\Alltron;

use App\CronTracker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Supsign\LaravelXmlReader\XmlReader;

class AlltronImport extends XmlReader
{
	protected 
		$logFile = 'AlltronLog.txt',
		$logPath = 'logs/',
		$downloadPath = 'imports/',
		$soap = null,
		$sourceFolder = 'dataexport',
		$tracker = null;

	public function __construct()
	{
		$this->soap = resolve('MyFactorySoapApi');
		$this->tracker = CronTracker::firstOrCreate(['class' => static::class]);
	}

	public function downloadFile()
	{
		$this->tracker->downloading();

	    (new AlltronFTP)
	        ->setLocalFile(Storage::path($this->downloadPath.$this->sourceFile))
	        ->setRemoteFile($this->sourceFile)
	        ->downloadFile();

	    return $this;
	}

	public function writeLog($data)
	{
		if (!is_array($data))
			$data = [$data];

		foreach ($data AS $line)
			$this->writeLogLine($line);

		return $this;
	}

	protected function writeLogLine(string $string)
	{	
		Storage::append($this->logPath.$this->logFile, '['.Carbon::now().'] '.$string);

		return $this;
	}
}
