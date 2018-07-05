<?php
/**
 * @package    Information Component
 * @version    1.0.4
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;

jimport('joomla.filesystem.file');

class InfoModelList extends ListModel
{
	/**
	 * Item layouts
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $_itemLayouts = array('default' => 'default');

	/**
	 * This tag
	 *
	 * @var    object
	 * @since  1.0.0
	 */
	protected $_tag = null;

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
				'introimage', 'i.introimage',
				'header', 'i.header',
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

		JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');
		$this->imageFolderHelper = new imageFolderHelper('images/info');
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
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		// Set id state
		$pk = $app->input->getInt('id', 1);
		$this->setState('tag.id', $pk);

		// Load the parameters. Merge Global and Menu Item params into new object
		$params     = $app->getParams();
		$menuParams = new Registry;
		$menu       = $app->getMenu()->getActive();
		if ($menu)
		{
			$menuParams->loadString($menu->getParams());
		}
		$mergedParams = clone $menuParams;
		$mergedParams->merge($params);
		$this->setState('params', $mergedParams);

		// Published state
		if ((!$user->authorise('core.manage', 'com_info')))
		{
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);
		}
		else
		{
			$this->setState('filter.published', array(0, 1));
		}

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$region = $this->getUserStateFromRequest($this->context . '.filter.region', 'filter_region', '');
		$this->setState('filter.region', $region);

		// List state information.
		parent::populateState($ordering, $direction);

		// Set limit & limitstart for query.
		$this->setState('list.limit', $params->get('items_limit', 10, 'uint'));
		$this->setState('list.start', $app->input->get('limitstart', 0, 'uint'));

		// Set ordering for query.
		$ordering  = empty($ordering) ? 'i.created' : $ordering;
		$direction = empty($direction) ? 'desc' : $direction;
		$this->setState('list.ordering', $ordering);
		$this->setState('list.direction', $direction);
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
		$id .= ':' . $this->getState('filter.region');
		$id .= ':' . serialize($this->getState('filter.published'));
		$id .= ':' . serialize($this->getState('filter.item_id'));
		$id .= ':' . $this->getState('filter.item_id.include');

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
		$user = Factory::getUser();

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('i.*')
			->from($db->quoteName('#__info', 'i'));

		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name AS region_name'))
			->join('LEFT', '#__regions AS r ON r.id = 
					(CASE i.region WHEN ' . $db->quote('*') . ' THEN 100 ELSE i.region END)');

		// Join over the discussions.
		$query->select('(CASE WHEN dt.id IS NOT NULL THEN dt.id ELSE 0 END) as discussions_topic_id')
			->join('LEFT', '#__discussions_topics AS dt ON dt.item_id = i.id AND ' .
				$db->quoteName('dt.context') . ' = ' . $db->quote('com_info.item'));

		// Filter by access level
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('i.access IN (' . $groups . ')');
		}

		// Filter by a single or group of items.
		$itemId = $this->getState('filter.item_id');
		if (is_numeric($itemId))
		{
			$type = $this->getState('filter.item_id.include', true) ? '= ' : '<> ';
			$query->where('i.id ' . $type . (int) $itemId);
		}
		elseif (is_array($itemId))
		{
			$itemId = ArrayHelper::toInteger($itemId);
			$itemId = implode(',', $itemId);
			$type   = $this->getState('filter.item_id.include', true) ? 'IN' : 'NOT IN';
			$query->where('i.id ' . $type . ' (' . $itemId . ')');
		}

		// Filter by published state.
		$published = $this->getState('filter.published');
		if (!empty($published))
		{
			if (is_numeric($published))
			{
				$query->where('( i.state = ' . (int) $published .
					' OR ( i.created_by = ' . $user->id . ' AND i.state IN (0,1)))');
				$query->where('(i.in_work = 0 OR i.created_by = ' . $user->id . ')');
			}
			elseif (is_array($published))
			{
				$query->where('i.state IN (' . implode(',', $published) . ')');
			}
		}

		// Filter by regions
		$region = $this->getState('filter.region');
		if (is_numeric($region))
		{
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_nerudas/models');
			$regionModel = JModelLegacy::getInstance('regions', 'NerudasModel');
			$regions     = $regionModel->getRegionsIds($region);
			$regions[]   = $db->quote('*');
			$regions[]   = $regionModel->getRegion($region)->parent;
			$regions     = array_unique($regions);
			$query->where($db->quoteName('i.region') . ' IN (' . implode(',', $regions) . ')');
		}

		// Filter by tag.
		$tag = (int) $this->getState('tag.id');
		if ($tag > 1)
		{
			$query->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap')
				. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('i.id')
				. ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_info.item'))
				->where($db->quoteName('tagmap.tag_id') . ' = ' . $tag);
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
				$text_columns = array('i.title', 'i.fulltext', 'i.introtext', 'i.tags_search', 'r.name');

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
			JLoader::register('DiscussionsHelperTopic', JPATH_SITE . '/components/com_discussions/helpers/topic.php');

			foreach ($items as &$item)
			{
				$item->introimage = (!empty($item->introimage) && JFile::exists(JPATH_ROOT . '/' . $item->introimage)) ?
					Uri::root(true) . '/' . $item->introimage : false;

				$item->link = Route::_(InfoHelperRoute::getItemRoute($item->id));

				// Convert the attribs field from json.
				$item->attribs = new Registry($item->attribs);

				$layout = $item->attribs->get('list_item_layout',
					ComponentHelper::getParams('com_info')->get('list_item_layout', 'default'));
				if ($layout != 'default')
				{
					$layout = $this->getItemLayout($layout);
				}
				$item->layout = 'components.com_info.listitems.' . $layout;

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

				$item->imageFolder = $this->imageFolderHelper->getItemImageFolder($item->id);

				// Shortcodes
				$item->introtext = str_replace('{id}', $item->id, $item->introtext);
				$item->introtext = str_replace('{title}', $item->title, $item->introtext);
				$item->introtext = str_replace('{imageFolder}', $item->imageFolder . '/content', $item->introtext);

				// Discussions posts count
				$item->commentsCount = DiscussionsHelperTopic::getPostsTotal($item->discussions_topic_id);

			}
		}

		return $items;
	}

	/**
	 * Get the filter form
	 *
	 * @param string $layout Layout name
	 *
	 * @return  string
	 *
	 * @since 1.0.0
	 */
	protected function getItemLayout($layout)
	{
		if (isset($this->_itemLayouts[$layout]))
		{
			return $this->_itemLayouts[$layout];
		}

		$path = '/layouts/components/com_info/listitems/' . $layout . '.php';
		if (JFile::exists(JPATH_ROOT . $path))
		{
			$this->_itemLayouts[$layout] = $layout;

			return $this->_itemLayouts[$layout];
		}

		$template = Factory::getApplication()->getTemplate();
		if (JFile::exists(JPATH_ROOT . '/templates/' . $template . '/html' . $path))
		{

			$this->_itemLayouts[$layout] = $layout;

			return $this->_itemLayouts[$layout];
		}

		$this->_itemLayouts[$layout] = 'default';

		return $this->_itemLayouts[$layout];

	}

	/**
	 * Get the filter form
	 *
	 * @param   array   $data     data
	 * @param   boolean $loadData load current data
	 *
	 * @return  Form|boolean  The Form object or false on error
	 *
	 * @since 1.0.0
	 */
	public function getFilterForm($data = array(), $loadData = true)
	{
		if ($form = parent::getFilterForm())
		{
			$params = $this->getState('params');
			if ($params->get('search_placeholder', ''))
			{
				$form->setFieldAttribute('search', 'hint', $params->get('search_placeholder'), 'filter');
			}

			$form->setValue('tag', 'filter', $this->getState('tag.id', 1));
		}

		return $form;
	}

	/**
	 * Gets the value of a user state variable and sets it in the session
	 *
	 * This is the same as the method in \JApplication except that this also can optionally
	 * force you back to the first page when a filter has changed
	 *
	 * @param   string  $key       The key of the user state variable.
	 * @param   string  $request   The name of the variable passed in a request.
	 * @param   string  $default   The default value for the variable if not found. Optional.
	 * @param   string  $type      Filter for the variable, for valid values see {@link \JFilterInput::clean()}. Optional.
	 * @param   boolean $resetPage If true, the limitstart in request is set to zero
	 *
	 * @return  mixed  The request user state.
	 *
	 * @since 1.0.0
	 */
	public function getUserStateFromRequest($key, $request, $default = null, $type = 'none', $resetPage = true)
	{
		$app       = Factory::getApplication();
		$set_state = $app->input->get($request, null, $type);
		$new_state = parent::getUserStateFromRequest($key, $request, $default, $type, $resetPage);
		if ($new_state == $set_state)
		{
			return $new_state;
		}
		$app->setUserState($key, $set_state);

		return $set_state;
	}

	/**
	 * Get the current tag
	 *
	 * @param null $pk
	 *
	 * @return object|false
	 *
	 * @since 1.0.0
	 */
	public function getTag($pk = null)
	{
		if (!is_object($this->_tag))
		{
			$app    = Factory::getApplication();
			$pk     = (!empty($pk)) ? (int) $pk : (int) $this->getState('tag.id', $app->input->get('id', 1));
			$tag_id = $pk;

			$root            = new stdClass();
			$root->title     = Text::_('JGLOBAL_ROOT');
			$root->id        = 1;
			$root->parent_id = 0;
			$root->link      = Route::_(InfoHelperRoute::getListRoute(1));

			if ($tag_id > 1)
			{
				$errorRedirect = Route::_(InfoHelperRoute::getListRoute(1));
				$errorMsg      = Text::_('COM_INFO_ERROR_TAG_NOT_FOUND');
				try
				{
					$db    = $this->getDbo();
					$query = $db->getQuery(true)
						->select(array('t.id', 't.parent_id', 't.title', 'pt.title as parent_title'))
						->from('#__tags AS t')
						->where('t.id = ' . (int) $tag_id)
						->join('LEFT', '#__tags AS pt ON pt.id = t.parent_id');

					$user = Factory::getUser();
					if (!$user->authorise('core.admin'))
					{
						$query->where('t.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
					}
					if (!$user->authorise('core.manage', 'com_tags'))
					{
						$query->where('t.published =  1');
					}

					$db->setQuery($query);
					$data = $db->loadObject();

					if (empty($data))
					{
						$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);

						return false;
					}

					$data->link = Route::_(InfoHelperRoute::getListRoute($data->id));

					$this->_tag = $data;
				}
				catch (Exception $e)
				{
					if ($e->getCode() == 404)
					{
						$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);
					}
					else
					{
						$this->setError($e);
						$this->_tag = false;
					}
				}
			}
			else
			{
				$this->_tag = $root;
			}
		}


		return $this->_tag;
	}

}