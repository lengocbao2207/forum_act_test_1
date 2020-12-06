<?php

class Kid_Helper
{
	public static function kidexplode($string)
	{
		$ar = explode(', ',$string);
		return $ar[0];
	}
}