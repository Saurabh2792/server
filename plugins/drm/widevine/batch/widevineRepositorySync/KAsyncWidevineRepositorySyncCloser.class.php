<?php
/**
 * @package plugins.widevine
 * @subpackage Scheduler
 */
class KAsyncWidevineRepositorySyncCloser extends KJobCloserWorker
{

	/* (non-PHPdoc)
	 * @see KBatchBase::getType()
	 */
	public static function getType()
	{
		return KalturaBatchJobType::WIDEVINE_REPOSITORY_SYNC;
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
		return $this->closeWidevineSync($job, $job->data);
	}
	
	/**
	 * 
	 * Verify the modified data was applied in Widevine
	 * 
	 * @param KalturaBatchJob $job
	 * @param KalturaWidevineRepositorySyncJobData $data
	 */
	private function closeWidevineSync(KalturaBatchJob $job, KalturaWidevineRepositorySyncJobData $data)
	{
		KalturaLog::debug("fetchStatus($job->id)");
		
		if(($job->queueTime + $this->taskConfig->params->maxTimeBeforeFail) < time())
			return $this->closeJob($job, KalturaBatchJobErrorTypes::APP, KalturaBatchJobAppErrors::CLOSER_TIMEOUT, 'Timed out', KalturaBatchJobStatus::FAILED);

		$dataWrap = new WidevineRepositorySyncJobDataWrap($data);
		if($this->isModificationCompleted($job, $dataWrap))
		{
			$this->updateFlavorAssets($job, $dataWrap);
			return $this->closeJob($job, null, null, "Widevine update completed", KalturaBatchJobStatus::FINISHED, $data);
		}
		else		
		{
			return $this->closeJob($job, null, null, 'Waiting for widevine modification completion', KalturaBatchJobStatus::ALMOST_DONE, $data);
		}		
	}

	/**
	 * 
	 * Compare distribution dates in job data with the Widevine details of each one of the assets
	 * If the changes were applied to all the assets in Widevine return true
	 * Otherwise return false
	 * 
	 * @param KalturaBatchJob $job
	 * @param WidevineRepositorySyncJobDataWrap $dataWrap
	 */
	private function isModificationCompleted(KalturaBatchJob $job, WidevineRepositorySyncJobDataWrap $dataWrap)
	{
		KalturaLog::debug("Starting isModificationCompleted");
		
		$cgiUrl = $this->taskConfig->params->vodPackagerHost . WidevinePlugin::ASSET_NOTIFY_CGI;
		
		$widevineAssets = $dataWrap->getWidevineAssetIds();		
		$startDate = WidevineAssetNotifyRequest::normalizeLicenseStartDate($dataWrap->getLicenseStartDate());
		$endDate = WidevineAssetNotifyRequest::normalizeLicenseEndDate($dataWrap->getLicenseEndDate());	
		
		foreach ($widevineAssets as $assetId) 
		{
			KalturaLog::debug("Get asset [".$assetId."]");
			$getAssetXml = $this->prepareAssetNotifyGetRequestXml($assetId);
			$assetGetResponseXml = WidevineAssetNotifyRequest::sendPostRequest($cgiUrl, $getAssetXml);
			$assetGetResponse = WidevineAssetNotifyResponse::createWidevineAssetNotifyResponse($assetGetResponseXml);
			
			if(	!($startDate == $assetGetResponse->getLicenseStartDate()) || !($endDate == $assetGetResponse->getLicenseEndDate()))
				return false;
		}		
		return true;
	}
	
	private function prepareAssetNotifyGetRequestXml($assetId)
	{		
		$requestInput = new WidevineAssetNotifyRequest(WidevineAssetNotifyRequest::REQUEST_GET);
		
		$requestInput->setAssetId($assetId);
		$requestXml = $requestInput->createAssetNotifyRequestXml();
			
		KalturaLog::debug('Asset notify request: '.$requestXml);	
													  
		return $requestXml;
	}
	
	/**
	 * Update flavorAsset in Kaltura after the distribution dates apllied to Wideivne asset
	 * 
	 * @param KalturaBatchJob $job
	 * @param WidevineRepositorySyncJobDataWrap $dataWrap
	 */
	private function updateFlavorAssets(KalturaBatchJob $job, WidevineRepositorySyncJobDataWrap $dataWrap)
	{
		$this->impersonate($job->partnerId);
		
		$startDate = WidevineAssetNotifyRequest::normalizeLicenseStartDate($dataWrap->getLicenseStartDate());
		$endDate = WidevineAssetNotifyRequest::normalizeLicenseEndDate($dataWrap->getLicenseEndDate());	
		
		$filter = new KalturaAssetFilter();
		$filter->entryIdEqual = $job->entryId;
		$filter->tagsLike = 'widevine';
		$flavorAssetsList = $this->kClient->flavorAsset->listAction($filter, new KalturaFilterPager());
		
		foreach ($flavorAssetsList->objects as $flavorAsset) 
		{
			if($flavorAsset instanceof KalturaWidevineFlavorAsset && $dataWrap->hasAssetId($flavorAsset->widevineAssetId))
			{
				KalturaLog::debug('Updating flavor asset ['.$flavorAsset->id.']');	
				
				$updatedFlavorAsset = new KalturaWidevineFlavorAsset();
				$updatedFlavorAsset->widevineDistributionStartDate = $startDate;
				$updatedFlavorAsset->widevineDistributionEndDate = $endDate;
				$this->kClient->flavorAsset->update($flavorAsset->id, $updatedFlavorAsset);
			}		
		}		
		$this->unimpersonate();
	}
}
