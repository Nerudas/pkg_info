<?php
/**
 * @package    Information Component
 * @version    1.2.3
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

class InfoModelItems extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @since 1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'i.id',
				'title', 'i.title',
				'alias', 'i.alias',
				'introtext', 'i.introtext',
				'fulltext', 'i.fulltext',
				'images', 'i.images',
				'related', 'i.related',
				'state', 'i.state',
				'created', 'i.created',
				'created_by', 'i.created_by',
				'modified', 'i.modified',
				'publish_up', 'i.publish_up',
				'publish_down', 'i.publish_down',
				'in_work', 'i.in_work',
				'attribs', 'i.attribs',
				'metakey', 'i.metakey',
				'metadesc', 'i.metadesc',
				'access', 'i.access',
				'hits', 'i.hits',
				'region', 'i.region',
				'metadata', 'i.metadata',
				'tags_search', 'i.tags_search',
				'tags_map', 'i.tags_map'
			);
		}
		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
		$this->setState('filter.access', $access);

		$region = $this->getUserStateFromRequest($this->context . '.filter.region', 'filter_region', '');
		$this->setState('filter.region', $region);

		$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
		$this->setState('filter.created_by', $created_by);

		$tags = $this->getUserStateFromRequest($this->context . '.filter.tags', 'filter_tags', '');
		$this->setState('filter.tags', $tags);

		// List state information.
		$ordering  = empty($ordering) ? 'i.created' : $ordering;
		$direction = empty($direction) ? 'desc' : $direction;

		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string $id A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since 1.0.0
	 */
	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.region');
		$id .= ':' . serialize($this->getState('filter.tags'));

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since 1.0.0
	 */
	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('i.*')
			->from($db->quoteName('#__info', 'i'));

		// Join over the users for the author.
		$query->select('ua.name AS author_name')
			->join('LEFT', '#__users AS ua ON ua.id = i.created_by');

		// Join over the asset groups.
		$query->select('ag.title AS access_level')
			->join('LEFT', '#__viewlevels AS ag ON ag.id = i.access');

		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name as region_name'))
			->join('LEFT', '#__location_regions AS r ON r.id = i.region');

		// Filter by access level.
		$access = $this->getState('filter.access');
		if (is_numeric($access))
		{
			$query->where('i.access = ' . (int) $access);
		}

		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('i.state = ' . (int) $published);
		}
		elseif ($published == 'in_work')
		{
			$query->where('i.in_work = ' . 1);
		}
		elseif ($published === '')
		{
			$query->where('(i.state = 0 OR i.state = 1)');
		}

		// Filter by region
		$region = $this->getState('filter.region');
		if (!empty($region))
		{
			$query->where($db->quoteName('i.region') . ' = ' . $db->quote($region));
		}

		// Filter by tags.
		$tags = $this->getState('filter.tags');
		if (is_array($tags))
		{
			$tags = ArrayHelper::toInteger($tags);
			$tags = implode(',', $tags);
			if (!empty($tags))
			{
				$query->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap')
					. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('i.id')
					. ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_info.item'))
					->where($db->quoteName('tagmap.tag_id') . ' IN (' . $tags . ')');
			}
		}

		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('i.id = ' . (int) substr($search, 3));
			}
			else
			{
				$text_columns = array('i.title', 'i.fulltext', 'i.introtext', 'i.tags_search', 'r.name', 'ua.name');

				$sql = array();
				foreach ($text_columns as $column)
				{
					$sql[] = $db->quoteName($column) . ' LIKE '
						. $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				}

				$query->where('(' . implode(' OR ', $sql) . ')');
			}
		}

		// Group by
		$query->group(array('i.id'));

		// Add the list ordering clause.
		$ordering  = $this->state->get('list.ordering', 'i.created');
		$direction = $this->state->get('list.direction', 'desc');
		$query->order($db->escape($ordering) . ' ' . $db->escape($direction));

		return $query;
	}

	/**
	 * Gets an array of objects from the results of database query.
	 *
	 * @param   string  $query      The query.
	 * @param   integer $limitstart Offset.
	 * @param   integer $limit      The number of records.
	 *
	 * @return  object[]  An array of results.
	 *
	 * @since 1.0.0
	 * @throws  \RuntimeException
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$this->getDbo()->setQuery($query, $limitstart, $limit);

		return $this->getDbo()->loadObjectList('id');
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		if (!empty($items))
		{
			$mainTags = ComponentHelper::getParams('com_info')->get('tags', array());

			foreach ($items as &$item)
			{
				// Get Tags
				$item->tags = new TagsHelper;
				$item->tags->getItemTags('com_info.item', $item->id);

				if (!empty($item->tags->itemTags))
				{

					foreach ($item->tags->itemTags as &$tag)
					{
						$tag->main = (in_array($tag->id, $mainTags));
					}

					$item->tags->itemTags = ArrayHelper::sortObjects($item->tags->itemTags, 'main', -1);
				}

				// Get region
				if ($item->region == '*')
				{
					$item->region_icon = false;
					$item->region_name = Text::_('JGLOBAL_FIELD_REGIONS_ALL');
				}
			}
		}

		return $items;
	}
}