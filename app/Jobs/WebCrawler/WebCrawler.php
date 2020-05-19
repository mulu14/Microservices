<?php

namespace App\Jobs\WebCrawler;
use App\Traits\DispatchesJob;


class WebCrawler
{
     use DispatchesJob; 
     private $name;  
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($name)
    {
        $this->$name = $name; 
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return $this->name; 
    }
}
