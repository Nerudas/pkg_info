<?php
/**
 * @package    Information - Tags Module
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

class modInfoTagsHelper
{

	/**
	 * Method to get tags data.
	 *
	 * @param  \Joomla\Registry\Registry $params module params
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function getTags($params)
	{
		$app    = Factory::getApplication();
		$user   = Factory::getUser();
		$parent = (int) ComponentHelper::getParams('com_info')->get('tags');

		// Get tags
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(array('t.id', 't.title'))
			->from($db->quoteName('#__tags', 't'))
			->where($db->quoteName('t.alias') . ' <>' . $db->quote('root'));
		if ($parent > 1)
		{
			$query->where('(t.id = ' . $parent . ' OR t.parent_id = ' . $parent . ')');
		}
		else
		{
			$query->where('t.parent_id = 1');
		}


		if (!$user->authorise('core.admin'))
		{
			$query->where('t.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
		}
		if (!$user->authorise('core.manage', 'com_tags'))
		{
			$query->where('t.published =  1');
		}

		$query->order($db->escape('t.lft') . ' ' . $db->escape('asc'));

		$db->setQuery($query);
		$tags = $db->loadObjectList();
		if ($parent == 1)
		{
			$root        = new stdClass();
			$root->title = Text::_('JGLOBAL_ROOT');
			$root->id    = 1;
			array_unshift($tags, $root);
		}

		foreach ($tags as &$tag)
		{
			if ($tag->id == $parent)
			{
				$tag->id = 1;
			}
			$tag->link   = Route::_(InfoHelperRoute::getListRoute($tag->id));
			$tag->active = ($app->isSite() && $app->input->get('option') == 'com_info'
				&& $app->input->get('view') == 'list' && $app->input->get('id') == $tag->id);
		}
		return $tags;
	}
}