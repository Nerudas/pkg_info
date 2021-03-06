<?php
/**
 * @package    Information - Latest Module
 * @version    1.2.5
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

if (!empty($items))
{
	foreach ($items as $item)
	{
		echo LayoutHelper::render($item->layout, $item);
	}
};