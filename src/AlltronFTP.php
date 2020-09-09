<?php

namespace Supsign\Alltron;

use Config;

class AlltronFTP
{
// Noch Auslagern in .env und Config
   

  protected
  $ftp = null;

    public function __construct() {
      
           $host = 'ftp.competec.ch';
           $login = 'A273237';
           $password = '4KbddyPN';
       
        $this->ftp = new \FtpClient\FtpClient();
        $this->ftp->connect($host);
        $this->ftp->login($login, $password);

        return $this;
    }


        public function setFile($file){
            return $this;
        }


    public function importPrices(){

    }

    protected function importPrice(){

    }




 
}
