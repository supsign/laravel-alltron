<?php

namespace Supsign\AlltronPriceImport;

use Config;

class AlltronPriceImport
{
// Noch Auslagern in .env und Config
   



    public function __construct() {
           
           $host = 'ftp.competec.ch';
           $login = 'A273237';
           $password = '4KbddyPN';
       
        $ftp = new \FtpClient\FtpClient();
        $ftp->connect($host);
        $ftp->login($login, $password);

        return $ftp;
    }
}
