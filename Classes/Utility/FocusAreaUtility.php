<?php

namespace SomehowDigital\Typo3\MediaProcessing\Utility;

use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;

class FocusAreaUtility
{
	public static function calculateCenter(float $position, float $size)
	{
		return max(($position + ($size / 2)), 0);
	}
}
