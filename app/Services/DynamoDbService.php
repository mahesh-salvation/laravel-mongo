<?php

namespace App\Services;
putenv('HOME=E:/xampp/htdocs/projects/laravel-mongodb');
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

class DynamoDbService
{	
	/**
     * @var $sharedConfig
     */
    private $sharedConfig = [
        'endpoint'   => 'https://dynamodb.us-west-2.amazonaws.com',
        'profile' => 'project1',
        'version' => 'latest',
        'region' => 'us-west-2',
        'DynamoDb' => [
	 		'region' => 'us-west-2'
	 	]
    ];

    /**
     * @var $dynamodb
     */
    private $dynamodb;

    /**
     * Injecting dependencies
     */
    function __construct()
    {        
    // Create an SDK class used to share configuration across clients. 
        $sdk = new \Aws\Sdk($this->sharedConfig);

        $this->dynamodb = $sdk->createDynamoDb();

        $this->marshaler = new Marshaler();
    }

	/**
	 * list Tables
	 */
	public function listTables()
	{
		return $this->dynamodb->listTables()['TableNames'];
	}

	/**
	 * @param string $tableName
	 * @param string $tableList
	 * @param array $params
	 */
	public function createDynamoDBTable($tableName, $tableList, $params)
	{
		if (!in_array($tableName, $tableList)) {
			$this->dynamodb->createTable($params);
		}
		return true;
	}

	/**
	 * @param string $tableName
	 * @param string $tableList
	 * @param array $params
	 */
	public function insertIntoTable($tableName, $dailyReportInsertData)
	{
		$data = [
	 		'TableName' => $tableName,
	 		'Item' => $dailyReportInsertData
		];
		$this->dynamodb->putItem($data);
		return true;
	}

	/**
	 * @param string $tableName
	 * @param json $updateKey
	 * @param json $updateEav
	 */
	public function updateTable($tableName, $updateKey, $updateEav)
	{
		$params = [
	 		'TableName' => $tableName,
			'Key' => $updateKey,
			'UpdateExpression' => 'set viewTime = viewTime + :num',
			'ExpressionAttributeValues'=> $updateEav,
			'ReturnValues' => 'UPDATED_NEW'
		];
		$this->dynamodb->updateItem($params);
		return true;
	}

	/**
	 * @param string $tableName
	 * @param json $updateKey
	 * @param json $updateEav
	 */
	public function updateClickCount($tableName, $updateKey, $updateEav)
	{
		$params = [
	 		'TableName' => $tableName,
			'Key' => $updateKey,
			'UpdateExpression' => 'set clicks = clicks + :num',
			'ExpressionAttributeValues'=> $updateEav,
			'ReturnValues' => 'UPDATED_NEW'
		];
		$this->dynamodb->updateItem($params);
		return true;
	}

	/**
	 * @param string $tableName
	 * @param json $updateKey
	 * @param json $updateEav
	 */
	public function batchInsert($tableName, $data)
	{
		$this->dynamodb->batchWriteItem([
			"RequestItems" => [
				$tableName => $data
			]
		]);
		return true;
	}

	/**
	 * @param array $data
	 * @param Integer $user_id
	 */
 	public function insertOrUpdateReport($data, $user_id, $tableName)
 	{
 		foreach($data as $key => $value){
			foreach($value as $val){
				$uuid = 'daily-report-' . $user_id . '-' . $val->id . '-' . $key . '-' . date("Y-m-d");

				$selectEav = $this->marshaler->marshalJson('{
			 		":report_id": "' . $uuid . '"
			 	}');

				$selectParams = [
					'TableName' => $tableName,
				 	'KeyConditionExpression' => '#report_id = :report_id',
				 	'ExpressionAttributeNames'=> [ '#report_id' => 'report_id' ],
					'ExpressionAttributeValues'=> $selectEav
				];

				$result = $this->dynamodb->query($selectParams);

				if($result['Count'] > 0){
					$updateKey = $this->marshaler->marshalJson('{
			 			"report_id": "' . $uuid . '"
				 	}');
					$updateEav = $this->marshaler->marshalJson('{
					 	":num": ' . $val->viewTime . '
				 	}');

				 	$this->updateTable($tableName, $updateKey, $updateEav);

					if ($val->dateTime) {
						foreach($val->dateTime as $dateTime){
							$dateTimeData[] =  [
								"PutRequest" => [
									"Item" => [
										"usage_session_id" => ["S" => (string)uniqid ("daily-", TRUE)],
									 	"daily_report_id" => ["S" => $uuid],
									 	"start_time" => ["S" => date("Y-m-d H:i:s", $dateTime->startDate)],
									 	"end_time" => ["S" => date("Y-m-d H:i:s", $dateTime->endDate)],
									 	"created_at" => ["S" => date("Y-m-d H:i:s")],
									 	"updated_at" => ["S" => date("Y-m-d H:i:s")]
								 	]
							 	]
						 	];
						}
						$this->batchInsert('ntapp_daily_content_usage_sessions', $dateTimeData);					
						unset($dateTimeData);
					}
				} else {
					$dailyReportInsertData = $this->marshaler->marshalJson('{
													 	"report_id": "' . $uuid . '",
													 	"created_at": "' . date("Y-m-d") . '",
													 	"user_id": ' . $user_id . ',
													 	"content_id": ' . $val->id . ',
													 	"content_type": "' . $key . '",
													 	"viewTime": ' . $val->viewTime . ',
													 	"clicks": 0,
													 	"updated_at": "' . date("Y-m-d") . '"
												 	}');
					$this->insertIntoTable($tableName, $dailyReportInsertData);

					if ($val->dateTime) {
						foreach($val->dateTime as $dateTime){
							$dateTimeData[] =  [
								"PutRequest" => [
									"Item" => [
										"usage_session_id" => ["S" => (string)uniqid ("daily-", TRUE)],
									 	"daily_report_id" => ["S" => $uuid],
									 	"start_time" => ["S" => date("Y-m-d H:i:s", $dateTime->startDate)],
									 	"end_time" => ["S" => date("Y-m-d H:i:s", $dateTime->endDate)],
									 	"created_at" => ["S" => date("Y-m-d H:i:s")],
									 	"updated_at" => ["S" => date("Y-m-d H:i:s")]
								 	]
							 	]
						 	];
						}

						$this->batchInsert('ntapp_daily_content_usage_sessions', $dateTimeData);					
						unset($dateTimeData);
					}
				}

				$uuidMonthly = 'monthly-report-' . $user_id . '-' . $val->id . '-' . $key . '-' . date("Y-m");
				$selectEavMonthly = $this->marshaler->marshalJson('{
			 		":report_id": "' . $uuidMonthly . '"
			 	}');

				$selectParamsMonthly = [
					'TableName' => 'ntapp_user_content_report_monthly',
				 	'KeyConditionExpression' => '#report_id = :report_id',
				 	'ExpressionAttributeNames'=> [ '#report_id' => 'report_id' ],
					'ExpressionAttributeValues'=> $selectEavMonthly
				];

				$resultMonthly = $this->dynamodb->query($selectParamsMonthly);

				if($resultMonthly['Count'] > 0){
					$updateKeyMonthly = $this->marshaler->marshalJson('{
			 			"report_id": "' . $uuidMonthly . '"
				 	}');
					$updateEavMonthly = $this->marshaler->marshalJson('{
					 	":num": ' . $val->viewTime . '
				 	}');

				 	$this->updateClickCount('ntapp_user_content_report_monthly', $updateKeyMonthly, $updateEavMonthly);
				} else {
					$dailyReportInsertDataMonthly = $this->marshaler->marshalJson('{
													 	"report_id": "' . $uuidMonthly . '",
													 	"created_at": "' . date("Y-m-d") . '",
													 	"user_id": ' . $user_id . ',
													 	"content_id": ' . $val->id . ',
													 	"content_type": "' . $key . '",
													 	"viewTime": ' . $val->viewTime . ',
													 	"clicks": 0,
													 	"updated_at": "' . date("Y-m-d") . '"
												 	}');
					$this->insertIntoTable('ntapp_user_content_report_monthly', $dailyReportInsertDataMonthly);
				}
			}
		}

		return true;
 	}

 	/**
	 * @param array $selectParams
	 * @return boolean
	 */
	public function checkRow($selectParams)
	{
		$result = $this->dynamodb->query($selectParams);

		if ($result['Count'] == 0) {
			return false;
		}

		return true;
	}

	/**
	 * @param array $params
	 * @return boolean
	 */
	public function selectData($params)
	{
		return $this->dynamodb->scan($params);
	}
}
