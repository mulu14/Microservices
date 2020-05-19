<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar; 




class WebCrawlerController extends Controller
{
    private $request; 
    private $jar; 


    public function __construct(Request $request, CookieJar $jar)
    {
        $this->request = $request; 
        $this->jar = $jar; 

    }

  

    /**
     * Show company list
     *
     * @param  int  $id
     * @return View
     */
    public function index()
    { 
        logger($this->request->input('name')); 
        $companiesList = $this->getCompaniesList(); 
        return view('webcrawler.webcrawler', compact('companiesList')); 
    }
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return View
     */

   public function getCompaniesList(){

       // $jar = new CookieJar();

        $client = new Client(array(
            'cookies' => $this->jar
        ));

        $pageOne = $this->pageOne($client); 
        $numberOfPages = $pageOne['next']; 
        $iteratPages = array(); 
    
        for ($x = 0; $x < $numberOfPages; $x++) {
            array_push($iteratPages, $this->nextPage($client)); 
        }

        if ($numberOfPages >=1) {
            unset($pageOne['next']); 
            return array_merge($pageOne, array_merge(...$iteratPages)); 
        } else {
            unset($pageOne['next']); 
            return $pageOne; 
        }
    }
 
    public function pageOne($client){

        $name = $this->request->input('name'); 
        $url = "https://snr4.bolagsverket.se/snrgate/sok.do"; 
        $request = $client->request('POST', $url, [
            'headers' => [
                'Connection' => 'close', 
                'User-Agent' => 'Mozilla/5.0 (iPad; CPU OS 11_0 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) Version/11.0 Mobile/15A5341f Safari/604.1',
                'Cache-Control' => 'max-age=0', 
                'Referer' => 'https://snr4.bolagsverket.se', 
                'Upgrade-Insecure-Requests' => '1', 
                'Accept-Language' => 'en-US,en;q=0.9,de;q=0.8,fr;q=0.7,sv;q=0.6', 
                'Content-Type' => 'application/x-www-form-urlencoded', 

            ], 
           'form_params' => [
            'sokstrang' => $name,
            'valtSokalternativ' => '0',
            'method' => 'Sök'
        ]
        ]); 

        $res = (string)$request->getBody(); 
        $data = array(); 
        $crawler = new Crawler($res);

        /*filter the pages url*/
        $pagesList = $crawler->filter('.pages')->filter('li')->each(function($link, $i){
                return $link->filter('a')->each(function (Crawler $node, $i){
                    return $node->text(); 
                }); 
        }); 

        // check how many pages existed in the 
        $next = $this->iteratArray($pagesList); 


        /*filter */
        $table = $crawler->filter('tbody')->filter('tr')->each(function ($tr, $i) {
         return $tr->filter('td')->each(function (Crawler $node, $i) {
             return $node->text(); 
            });
        });
        
        foreach ($table as $row) 
        {
            $filterdData['organization_number'] = $row[0]; 
            $filterdData['company_name'] = $row[1]; 
            array_push($data, $filterdData); 
        }
        $data['next'] = $next; 
        return $data; 
    }


    public function nextPage($client){

        $url = 'https://snr4.bolagsverket.se/snrgate/sok.do?method=sokflikBladdra&forward=foretag&bladdra=nasta';  
        $request = $client->request('GET', $url, [
            'headers' => [
                'Accept' =>  'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Language' => 'en-US,en;q=0.9,de;q=0.8,fr;q=0.7,sv;q=0.6',
                'Connection' => 'close',
                'DNT' => '1',
                'Referer'  =>  'https://snr4.bolagsverket.se/snrgate/sok.do', 
                'Upgrade-Insecure-Requests' =>  '1',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36',
                'Sec-Fetch-Site' =>  'same-origin',
                'Sec-Fetch-Mode' =>  'navigate',
                'Sec-Fetch-User' =>  '?1'], 
            ]); 
        $res = (string)$request->getBody(); 
        $data = array(); 
        $crawler = new Crawler($res);
        $table = $crawler->filter('tbody')->filter('tr')->each(function ($tr, $i) {
         return $tr->filter('td')->each(function (Crawler $node, $i) {
             return $node->text(); 
            });
        });
        
        foreach ($table as $row) 
        {
            $filterdData['organization_number'] = $row[0]; 
            $filterdData['company_name'] = $row[1]; 
            array_push($data, $filterdData); 
        }
        return $data; 

    }

    private function iteratArray ($pagesList){
        $count = 0; 
        foreach($pagesList as $value) {
            foreach ($value as $key) {
                if(strpos($key, 'Sida') !== false){
                    $count++; 
                }
            }
        }
        return $count; 
    }


}


