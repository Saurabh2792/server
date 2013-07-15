<?php
/**
 * Will perform a single deletion of external asset
 *
 * @package Scheduler
 * @subpackage Storage
 */
class KAsyncStorageDelete extends KJobHandlerWorker
{
	/* (non-PHPdoc)
	 * @see KBatchBase::getType()
	 */
	public static function getType()
	{
		return KalturaBatchJobType::STORAGE_DELETE;
	}
	
	/* (non-PHPdoc)
	 * @see KBatchBase::getJobType()
	 */
	public function getJobType()
	{
		return self::getType();
	}
	
	/* (non-PHPdoc)
	 * @see KJobHandlerWorker::exec()
	 */
	protected function exec(KalturaBatchJob $job)
	{
		return $this->delete($job, $job->data);
	}
	
	/* (non-PHPdoc)
	 * @see KJobHandlerWorker::getFilter()
	 */
	protected function getFilter()
	{
		$filter = parent::getFilter();
		
		if(KBatchBase::$taskConfig->params->minFileSize && is_numeric(KBatchBase::$taskConfig->params->minFileSize))
			$filter->fileSizeGreaterThan = KBatchBase::$taskConfig->params->minFileSize;
		
		if(KBatchBase::$taskConfig->params->maxFileSize && is_numeric(KBatchBase::$taskConfig->params->maxFileSize))
			$filter->fileSizeLessThan = KBatchBase::$taskConfig->params->maxFileSize;
			
		return $filter;
	}
	
	/**
	 * Will take a single KalturaBatchJob and delete the given file 
	 * 
	 * @param KalturaBatchJob $job
	 * @param KalturaStorageDeleteJobData $data
	 * @return KalturaBatchJob
	 */
	private function delete(KalturaBatchJob $job, KalturaStorageDeleteJobData $data)
	{
		KalturaLog::debug("delete($job->id)");
		
        $exportEngine = KExportEngine::getInstance($job->jobSubType, $job->partnerId, $data);
		$this->updateJob($job, "Deleting $srcFile to $destFile", KalturaBatchJobStatus::QUEUED);
        
        $exportEngine->delete();
		
		return $this->closeJob($job, null, null, null, KalturaBatchJobStatus::FINISHED);
	}
	
}
