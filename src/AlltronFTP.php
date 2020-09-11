<?php

namespace Supsign\Alltron;

class AlltronFTP
{
    protected
        $ftp = null;

    public function __construct() 
    {
        $this->ftp = new \FtpClient\FtpClient();
        $this->ftp->connect(env('ALLTRON_FTP_HOST'));
        $this->ftp->login(env('ALLTRON_FTP_LOGIN'), env('ALLTRON_FTP_PASSWORD'));

        return $this;
    }

    protected function download($file)
    {
        file_put_contents(storage_path().'/'.$file, $this->ftp->getContent($file));

        return $this;
    }

    public function downloadPriceData()
    {
        return $this->download('PreisdatenV2.XML');
    }

    public function downloadProductData()
    {
        return $this->download('ArtikeldatenV2.xml');
    }


    protected function importPrices()
    {

    }

    protected function importPrice()
    {

    }

    public function test() 
    {

    }
}
