<?php

namespace Supsign\Alltron;

use Supsign\LaravelFtpConnector\FtpConnector;

class AlltronFTP extends FtpConnector
{
    public function __construct() 
    {
        return parent::__construct('ALLTRON');
    }

    public function downloadFile()
    {
    	$ret = parent::downloadFile();

    	echo $this->remoteFile.' download complete'.PHP_EOL;

    	return $ret;
    }
}
