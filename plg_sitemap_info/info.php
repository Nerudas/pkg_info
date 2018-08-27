<?php
/**
 * @package    Sitemap - Information Plugin
 * @version    1.1.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class plgSitemapInfo extends CMSPlugin
{

	/**
	 * Urls array
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $_urls = null;

	/**
	 * Method to get Links array
	 *
	 * @return array
	 *
	 * @since 1.1.1
	 */
	public function getUrls()
	{
		if ($this->_urls === null)
		{

			// Include route helper
			JLoader::register('InfoHelperRoute', JPATH_SITE . '/components/com_info/helpers/route.php');

			$db = Factory::getDbo();

			// Get items
			$query = $db->getQuery(true)
				->select(array('id', 'modified'))
				->from('#__info')
				->where('state = 1')
				->where('access IN (' . implode(',', Factory::getUser(0)->getAuthorisedViewLevels()) . ')')
				->order('modified DESC');

			$db->setQuery($query);
			$items = $db->loadObjectList('id');

			$item_changefreq = $this->params->def('item_changefreq', 'weekly');
			$item_priority   = $this->params->def('item_priority', '0.5');

			$items_urls = array();
			foreach ($items as $item)
			{
				$url             = new stdClass();
				$url->loc        = InfoHelperRoute::getItemRoute($item->id);
				$url->changefreq = $item_changefreq;
				$url->priority   = $item_priority;
				$url->lastmod    = $item->modified;

				$items_urls[] = $url;
			}

			// Get Tags
			$navtags        = ComponentHelper::getParams('com_info')->get('tags', array());
			$tag_changefreq = $this->params->def('tag_changefreq', 'weekly');
			$tag_priority   = $this->params->def('tag_priority', '0.5');

			$tags              = array();
			$tags[1]           = new stdClass();
			$tags[1]->id       = 1;
			$tags[1]->modified = array_shift($items)->modified;

			if (!empty($navtags))
			{
				$query = $db->getQuery(true)
					->select(array('tm.tag_id as id', 'max(tm.tag_date) as modified'))
					->from($db->quoteName('#__contentitem_tag_map', 'tm'))
					->join('LEFT', '#__tags AS t ON t.id = tm.tag_id')
					->where($db->quoteName('tm.type_alias') . ' = ' . $db->quote('com_info.item'))
					->where('tm.tag_id IN (' . implode(',', $navtags) . ')')
					->where('t.published = 1')
					->where('t.access IN (' . implode(',', Factory::getUser(0)->getAuthorisedViewLevels()) . ')')
					->group('t.id');
				$db->setQuery($query);

				$tags = $tags + $db->loadObjectList('id');
			}

			$tags_urls = array();
			foreach ($tags as $tag)
			{
				$url             = new stdClass();
				$url->loc        = InfoHelperRoute::getListRoute($tag->id);
				$url->changefreq = $tag_changefreq;
				$url->priority   = $tag_priority;
				$url->lastmod    = $tag->modified;

				$tags_urls[] = $url;
			}

			$this->_urls = $tags_urls + $items_urls;
		}

		return $this->_urls;

	}
}