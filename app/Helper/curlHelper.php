<?php

namespace App\Helper; 



/**
 * 
 */
class curlHelper
{
	/**
	*
	*@var $url
	*/

	private $url; 

	/**
	*
	*@var  Array 
	*/
	private $header; 

	/**
	*
    *@var Array 
	*/

	private $postfieds;
	
   /**
   *
   *@var String 
   */

   private $method; 

   /**
   *
   *@var String 
   */
   private $httpsCode; 

   /**
   *
   *@var Array 
   */
   private $result;


   /**
   *
   *@var String  
   */
   private $erros; 


   /**
   *
   *@var String  
   */
   private $hasError; 



   /**
   * Set method for curl
   *@param String 
   */


   public function setMethod($method)
   {
   	  $this->method = $method; 
   }

   /**
   *Set curl header fields
   *@param $header 
   */
   public function setHeader(iterator $header)
   {
   	 $this->header = $header; 
   }

   /**
   *Set curl post  fields
   *@param Iterator
   */
   public function setPostFieds(iterator $header)
   {
   	 $this->header = $header; 
   }

   /**
   *Set curl post  fields
   *@param String
   */
   public function setUrl(String $url)
   {
      $this->url = $url; 
   }

   /**
   *Excute curl
   *@param void
   */

   public function excuteCurl()
   {
      $ch = curl_init(); 
      $options = array(
         CURLOPT_URL => $this->url,
         CURLOPT_CUSTOMREQUEST => $this->method,
         CURLOPT_RETURNTRANSFER => true,
      ); 

      if($this->method =='POST'){
         $options[CURLOPT_POSTFIELDS] = $this->postfieds; 
      }

      curl_setopt_array($ch, $options); 
      $this->result = curl_exec($ch); 
      $this->httpsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
      $this->errors = curl_error($ch); 
      curl_close($ch); 
   }

    /**
   *Fetch result
   *@param void
   *@return  Array
   */
   public function getResult()
   {
      return $this->result; 
   }

   /**
   *Fetch httpscode
   *@param void
   *@return String
   */
   public function getHttpscode()
   {
      return $this->httpsCode; 
   }


   /**
   *Return errors
   *@param void
   *@return mix
   */
   public function getErrors()
   {
      return $this->errors; 
   }

}