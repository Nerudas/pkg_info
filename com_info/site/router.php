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

use Joomla\CMS\Factory;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Component\ComponentHelper;

class InfoRouter extends RouterView
{
	/**
	 * Router constructor
	 *
	 * @param   JApplicationCms $app  The application object
	 * @param   JMenu           $menu The menu object to work with
	 *
	 * @since 1.0.0
	 */
	public function __construct($app = null, $menu = null)
	{
		// List route
		$list = new RouterViewConfiguration('list');
		$list->setKey('id')->setNestable();
		$this->registerView($list);

		// Item route
		$item = new RouterViewConfiguration('item');
		$item->setKey('id')->setParent($list, 'tag_id');
		$this->registerView($item);

		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	/**
	 * Method to get the segment(s) for list view
	 *
	 * @param   string $id    ID of the item to retrieve the segments for
	 * @param   array  $query The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 *
	 * @since 1.0.0
	 */
	public function getListSegment($id, $query)
	{
		$path = array();

		$db      = Factory::getDbo();
		$dbquery = $db->getQuery(true)
			->select(array('id', 'alias', 'parent_id'))
			->from('#__tags')
			->where('id =' . $id);
		$db->setQuery($dbquery);
		$tag = $db->loadObject();

		if ($tag)
		{
			$path[$tag->id] = $tag->alias;
		}

		$path[1] = 'root';


		return $path;
	}

	/**
	 * Method to get the segment(s) for item view
	 *
	 * @param   string $id    ID of the item to retrieve the segments for
	 * @param   array  $query The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 *
	 * @since 1.0.0
	 */
	public function getItemSegment($id, $query)
	{
		if (!strpos($id, ':'))
		{
			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true)
				->select('alias')
				->from('#__info')
				->where('id = ' . (int) $id);
			$db->setQuery($dbquery);
			$alias = $db->loadResult();

			return array($id => $alias);
		}

		return false;
	}

	/**
	 * Method to get the id for a list view
	 *
	 * @param   string $segment Segment to retrieve the ID for
	 * @param   array  $query   The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 *
	 * @since 1.0.0
	 */
	public function getListId($segment, $query)
	{
		if (isset($query['id']))
		{
			$parent = (int) ComponentHelper::getParams('com_info')->get('tags', 1);

			// Get tags
			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true)
				->select(array('t.id', 't.alias'))
				->from($db->quoteName('#__tags', 't'))
				->where($db->quoteName('t.alias') . ' <>' . $db->quote('root'))
				->where('t.parent_id = ' . $parent);
			$db->setQuery($dbquery);
			$tags = $db->loadObjectList();

			foreach ($tags as $tag)
			{
				if ($tag->alias == $segment)
				{
					return $tag->id;
				}
			}
		}

		return false;
	}

	/**
	 * Method to get the id for a item view
	 *
	 * @param   string $segment Segment to retrieve the ID for
	 * @param   array  $query   The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 *
	 * @since 1.0.0
	 */
	public function getItemId($segment, $query)
	{
		if (!empty($segment))
		{
			preg_match('/^id(.*)/', $segment, $matches);
			$id = (!empty($matches[1])) ? (int) $matches[1] : 0;
			if (!empty($id))
			{
				return $id;
			}

			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true)
				->select('id')
				->from('#__info')
				->where($db->quoteName('alias') . ' = ' . $db->quote($segment));
			$db->setQuery($dbquery);

			return (int) $db->loadResult();
		}

		return false;
	}
}

function infoBuildRoute(&$query)
{
	$app    = Factory::getApplication();
	$router = new InfoRouter($app, $app->getMenu());

	return $router->build($query);
}

function infoParseRoute($segments)
{
	$app    = Factory::getApplication();
	$router = new InfoRouter($app, $app->getMenu());

	return $router->parse($segments);
}