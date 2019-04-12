<?php

namespace App\Services;
use App\Report;
use DB;

class ReportService
{	

    /**
     * @var $dynamodb
     */
    private $report;

    /**
     * Injecting dependencies
     */
    function __construct(Report $report)
    {        
        $this->report = $report;
    }

    public function insertData($data)
    {
    	$insertData = DB::collection('reports')->insert($data);
	    if($insertData){
	        return true;
	    }
    }

    public function getData()
    {
    	return $this->report->get();
    }
}
