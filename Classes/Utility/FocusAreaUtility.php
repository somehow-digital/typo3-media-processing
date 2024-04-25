<?php

namespace SomehowDigital\Typo3\MediaProcessing\Utility;

use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;

class FocusAreaUtility
{

	/**
	 * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area $area
	 *
	 * @return float|int
	 */
	public static function calculateCenter(float $position, float $size)
	{
		// The max method ensures that only positive floats are returned
		return max(($position + ($size / 2)), 0);
	}
}
