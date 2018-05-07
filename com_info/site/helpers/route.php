<?php
/**
 * @package    Information Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\RouteHelper;

class InfoHelperRoute extends RouteHelper
{
	/**
	 * Fetches the item route
	 *
	 * @param  int $id Item ID
	 *
	 * @return  string
	 *
	 * @since 1.0.0
	 */
	public static function getItemRoute($id = null)
	{
		return 'index.php?option=com_info&view=item&tag_id=1&id=' . $id;
	}

	/**
	 * Fetches the list route
	 *
	 * @param  int $tag_id Item ID
	 *
	 * @return  string
	 *
	 * @since 1.0.0
	 */
	public static function getListRoute($tag_id = 1)
	{
		return 'index.php?option=com_info&view=list&tag_id=' . $tag_id;
	}
}