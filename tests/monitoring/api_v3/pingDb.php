<?php
$client = null;
/* @var $client KalturaClient */
require_once __DIR__  . '/common.php';

$start = microtime(true);
$monitorResult = new KalturaMonitorResult();
try
{
	$res = $client->system->pingDatabase();
	$end = microtime(true);
	$monitorResult->executionTime = $end - $start;
	
	if($res)
	{
		$monitorResult->value = $monitorResult->executionTime;
		$monitorResult->description = "Database ping time: $monitorResult->value seconds";
	}
	else
	{
		$monitorResult->value = -1;
		$monitorResult->description = 'Database ping failed';
	}
}
catch(KalturaException $e)
{
	$end = microtime(true);
	$monitorResult->executionTime = $end - $start;
	
	$error = new KalturaMonitorError();
	$error->code = $e->getCode();
	$error->description = $e->getMessage();
	$error->level = KalturaMonitorError::ERR;
	
	$monitorResult->errors[] = $error;
	$monitorResult->description = "Exception: " . get_class($e) . ", Code: " . $e->getCode() . ", Message: " . $e->getMessage();
}
catch(KalturaClientException $ce)
{
	$end = microtime(true);
	$monitorResult->executionTime = $end - $start;
	
	$error = new KalturaMonitorError();
	$error->code = $ce->getCode();
	$error->description = $ce->getMessage();
	$error->level = KalturaMonitorError::CRIT;
	
	$monitorResult->errors[] = $error;
	$monitorResult->description = "Exception: " . get_class($ce) . ", Code: " . $ce->getCode() . ", Message: " . $ce->getMessage();
}

echo "$monitorResult";
exit(0);

