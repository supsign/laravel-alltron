<?php

namespace Supsign\AlltronPriceImport;

use Config;

class AlltronPriceImport
{
// Noch Auslagern in .env und Config
   

  protected
  $ftp;

    public function __construct() {
      
           $host = 'ftp.competec.ch';
           $login = 'A273237';
           $password = '4KbddyPN';
       
        $this->ftp = new \FtpClient\FtpClient();
        $this->ftp->connect($host);
        $this->ftp->login($login, $password);
        // $this->ftp->pasv(true);


        return $this;
    }


public function setFile($file){
    // $this->ftp->
    // return $this;
}


    public function importPrices(){
        // foreach($this as){
        //     $this->importPrice();
        // }
    }

    protected function importPrice(){

    }

    public function test(){
        return $this->ftp->count();
    }


 
}
