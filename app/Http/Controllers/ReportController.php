<?php

namespace App\Http\Controllers;

use App\Services\DynamoDbService;
use App\Services\ReportService;
use Aws\DynamoDb\Marshaler;

class ReportController extends Controller
{
    /**
     * @var App\Services\DynamoDbService;
     */
    private $dynamoDbService;

    /**
     * @var App\Services\ReportService;
     */
    private $reportService;

    /**
     * Injecting dependencies
     * @param UserService $userService;
     */
    function __construct(DynamoDbService $dynamoDbService, ReportService $reportService, Marshaler $marshaler)
    {
        $this->dynamoDbService = $dynamoDbService;
        $this->reportService = $reportService;
        $this->marshaler = $marshaler;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view()
    {
        $data = $this->reportService->getData();
        return view("view")->with([
            'data' => $data
        ]);
    }

    public function create()
    {
        $params = [
            'TableName' => 'ntapp_user_content_report_daily',
            'ProjectionExpression' => 'content_id, user_id, viewTime, clicks, content_type, created_at, updated_at',
        ];

        $reports = [];

        $result = $this->dynamoDbService->selectData($params);
        $viewTime[] = 0;
        $count = 0;
        foreach ($result['Items'] as $i) {
            $count++;
            $report = $this->marshaler->unmarshalItem($i);
            if ($count < 100) {
                $reports[] = [
                    'content_id' => $report['content_id'],
                    'time' => $report['viewTime'],
                    'clicks' => $report['clicks'],
                    'updated_at' => $report['updated_at'],
                    'cteated_at' => $report['created_at'],
                    'content_type' => $report['content_type'],
                    'user_id' => $report['user_id'],
                    'content_type' => $report['content_type']
                ];
            } else {
                break;
            }
        }
        $awsData = $this->reportService->insertData($reports);
        return true
    }
}
