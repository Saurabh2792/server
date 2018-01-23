<?php
/**
 * @package plugins.elasticSearch
 * @subpackage model.items
 */
abstract class ESearchNestedObjectItem extends ESearchItem
{

	const DEFAULT_INNER_HITS_SIZE = 10;
	const DEFAULT_GROUP_NAME = 'default_group';
	const QUERY_NAME_DELIMITER = '#DEL#';

	protected static function initializeInnerHitsSize($queryAttributes)
	{
		$overrideInnerHitsSize = $queryAttributes->getOverrideInnerHitsSize();
		if($overrideInnerHitsSize)
			return $overrideInnerHitsSize;

		$innerHitsConfig = kConf::get('innerHits', 'elastic');
		$innerHitsConfigKey = static::INNER_HITS_CONFIG_KEY;
		$innerHitsSize = isset($innerHitsConfig[$innerHitsConfigKey]) ? $innerHitsConfig[$innerHitsConfigKey] : self::DEFAULT_INNER_HITS_SIZE;

		return $innerHitsSize;
	}

	protected static function initializeNumOfFragments()
	{
		$highlightConfigKey = static::HIGHLIGHT_CONFIG_KEY;
		$numOfFragments = elasticSearchUtils::getNumOfFragmentsByConfigKey($highlightConfigKey);
		return $numOfFragments;
	}

	public static function createSearchQuery($eSearchItemsArr, $boolOperator, &$queryAttributes, $eSearchOperatorType = null)
	{
		return self::createQueryForItems($eSearchItemsArr, $boolOperator, $queryAttributes);
	}

	public static function createQueryForItems($eSearchItemsArr, $boolOperator, &$queryAttributes)
	{
		$innerHitsSize = self::initializeInnerHitsSize($queryAttributes);
		$allowedSearchTypes = static::getAllowedSearchTypesForField();
		$numOfFragments = self::initializeNumOfFragments();
		// must_not was already set in a higher level of the query inside ESearchOperator
		if($boolOperator == 'must_not')
			$boolOperator = 'must';

		if($queryAttributes->isNestedOperatorContext()) //nested operator
		{
			self::initNestedQueryParams($queryAttributes, $innerHitsSize, $numOfFragments);
			$boolQuery = new kESearchBoolQuery();
			foreach ($eSearchItemsArr as $eSearchItem)
			{
				$eSearchItem->createSingleItemSearchQuery($boolOperator, $boolQuery, $allowedSearchTypes, $queryAttributes);
			}
			if(!$queryAttributes->getNestedQueryName())//in case of parent-child nested operators we already set the name in the parent
			{
				$queryAttributes->setNestedQueryName($eSearchItem->getNestedQueryName($queryAttributes));
				$queryAttributes->incrementNestedQueryNameIndex();
			}
			$finalQuery[] = $boolQuery;
		}
		else//entry operator
		{
			if($boolOperator == kESearchBoolQuery::MUST_KEY)
			{
				//create single for each item with nested
				foreach ($eSearchItemsArr as $eSearchItem)
				{
					$boolQuery = new kESearchBoolQuery();
					self::initNestedQueryParams($queryAttributes, $innerHitsSize, $numOfFragments);
					$queryAttributes->setScopeToInner();
					$eSearchItem->createSingleItemSearchQuery($boolOperator, $boolQuery, $allowedSearchTypes, $queryAttributes);
					$nestedQuery = self::createNestedQuery($eSearchItem->getNestedQueryName($queryAttributes), $boolQuery, $queryAttributes);
					$finalQuery[] = $nestedQuery;
				}
			}
			else //in case of should operator we can group
			{
				$boolQuery = new kESearchBoolQuery();
				$queryAttributes->setScopeToInner();
				foreach ($eSearchItemsArr as $eSearchItem)
				{
					$eSearchItem->createSingleItemSearchQuery($boolOperator, $boolQuery, $allowedSearchTypes, $queryAttributes);
				}
				self::initNestedQueryParams($queryAttributes, $innerHitsSize, $numOfFragments);
				$nestedQuery = self::createNestedQuery($eSearchItem->getNestedQueryName($queryAttributes), $boolQuery, $queryAttributes);
				$finalQuery[] = $nestedQuery;
			}
		}
		return $finalQuery;
	}

	private static function initNestedQueryParams(&$queryAttributes, $innerHitsSize, $numOfFragments)
	{
		$queryAttributes->setNestedOperatorInnerHitsSize($innerHitsSize);
		$queryAttributes->setNestedOperatorNumOfFragments($numOfFragments);
		$queryAttributes->setNestedOperatorPath(static::NESTED_QUERY_PATH);
	}

	private static function createNestedQuery($queryName, &$boolQuery, &$queryAttributes)
	{
		$queryAttributes->setNestedQueryName($queryName);
		$nestedQuery = kESearchQueryManager::getNestedQuery($boolQuery, $queryAttributes);
		$queryAttributes->setNestedQueryName(null);
		$queryAttributes->incrementNestedQueryNameIndex();
		return $nestedQuery;
	}

	public abstract function getNestedQueryName(&$queryAttributes);

}
