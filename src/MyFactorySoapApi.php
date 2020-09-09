<?php

namespace Supsign\LaravelMfSoap;

use Config;

class MyFactorySoapApi
{
    protected
        $client      = null,
        $request     = array(),
        $response    = null;

	public function __construct() {
		$this->request['UserName'] = env('MF_API_LOGIN');
		$this->request['Password'] = env('MF_API_PASSWORD');

		$this->client = new \SoapClient(env('MF_API_WSDL'));

		return $this;
	}

    public function getResponse() {
        return $this->response;
    }

    public function test() {
		$this->response = $this->client->GetCustomers($this->request);

    	var_dump($this->client->__getLastRequest()  );

    	return $this;
    }
}