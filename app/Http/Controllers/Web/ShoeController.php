<?php

namespace App\Http\Controllers\Web;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar; 
use Carbon\Carbon; 


/**
 * 
 */
class ShoeController extends Controller
{
	
	private $jar; 
	private $crawler; 

	function __construct(CookieJar $jar, Crawler $crawler)
	{
		$this->jar = $jar; 
		$this->crawler = $crawler; 
	}



	public function index()
	{

	}

	public function listshoes()
	{
		$client = new Client(array(
            'cookies' => $this->jar
        ));

		$url = 'https://www.footway.se/skor/herr/hoga-sneakers/adidas-originals/svart/42'; 
		 $request = $client->request('POST', $url, [
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9', 
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'en-US,en;q=0.9,de;q=0.8,fr;q=0.7,sv;q=0.6',
                'Cache-Control' => 'max-age=0', 
                'Connection' => 'keep-alive', 
                'Upgrade-Insecure-Requests' => '1', 
                'Host' => 'www.footway.se',
                'User-Agent' => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Mobile Safari/537.36'

            ]
        ]); 

		 $res = (string)$request->getBody(); 
		 //logger($res); 
		 $from = new Carbon(); 
		 $to  = (new Carbon())->addDays(30); 
		 $fin = [
		 	"from" => $from->toDateString(), 
		 	"to" => $from->toDateString(),
		 ]; 
		 logger($fin); 
	}
}