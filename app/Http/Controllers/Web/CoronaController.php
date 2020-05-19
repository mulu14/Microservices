<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helper\curlHelper; 
use Exception; 
/**
 * 
 */
class CoronaController extends Controller
{
	private $helper; 

	
	public function __construct(curlHelper $helper)
    {
    	$this->helper = $helper; 
    }


	public function index(Request $request){
		
		return view('corona.coronastatistic'); 
	}


	public function summary(Request $request)
	{
		$this->helper->setUrl(config('corona.summary')); 
		$this->helper->setMethod('GET'); 

		try{

		  $this->helper->excuteCurl(); 

		} catch (Exception $e) {
			logger($e->getMessage()); 
			die(); 
		}

		$summary = json_decode($this->helper->getResult(), true); 
		$data = $summary['Global']; 
		return view('corona.summary', compact('data'));  
	}

}