<?php

namespace SomehowDigital\Typo3\MediaProcessing\Utility;

class FocusAreaUtility
{
	public static function calculateCenter(float $position, float $size)
	{
		return max(($position + ($size / 2)), 0);
	}
}
