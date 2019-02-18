<?php
/**
 * @package    Information Component
 * @version    1.2.5
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;

class InfoHelper extends ContentHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string $vName The name of the active view.
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(Text::_('COM_INFO'),
			'index.php?option=com_info&view=items',
			$vName == 'items');
	}
}