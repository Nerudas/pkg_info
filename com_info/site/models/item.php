<?php
/**
 * @package    Information Component
 * @version    1.1.2
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.filesystem.file');

class InfoModelItem extends ItemModel
{
	/**
	 * Model context string.
	 *
	 * @var        string
	 *
	 * @since 1.0.0
	 */
	protected $_context = 'com_info.item';

	/**
	 * Related items
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $_related = array();

	/**
	 * Comments
	 *
	 * @var    object
	 * @since  1.0.0
	 */
	protected $_comments = array();

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @see     AdminModel
	 *
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');
		$this->imageFolderHelper = new imageFolderHelper('images/info');

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('site');

		// Load state from the request.
		$pk = $app->input->getInt('id');
		$this->setState('item.id', $pk);

		$offset = $app->input->getUInt('limitstart');
		$this->setState('list.offset', $offset);

		$user = Factory::getUser();

		// Published state
		if ((!$user->authorise('core.manage', 'com_item')))
		{
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);
		}
		else
		{
			$this->setState('filter.published', array(0, 1));
		}

		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);

	}

	/**
	 * Method to get type data for the current type
	 *
	 * @param   integer $pk The id of the type.
	 *
	 * @return  mixed object|false
	 *
	 * @since 1.0.0
	 */
	public function getItem($pk = null)
	{
		$app = Factory::getApplication();
		$pk  = (!empty($pk)) ? $pk : (int) $this->getState('item.id');

		if (!isset($this->_item[$pk]))
		{
			$errorRedirect = Route::_(InfoHelperRoute::getListRoute(1));
			$errorMsg      = Text::_('COM_INFO_ERROR_ITEM_NOT_FOUND');
			try
			{
				$db   = $this->getDbo();
				$user = Factory::getUser();

				$query = $db->getQuery(true)
					->select('i.*')
					->from('#__info AS i')
					->where('i.id = ' . (int) $pk);

				// Join over the discussions.
				$query->select('(CASE WHEN dt.id IS NOT NULL THEN dt.id ELSE 0 END) as discussions_topic_id')
					->join('LEFT', '#__discussions_topics AS dt ON dt.item_id = i.id AND ' .
						$db->quoteName('dt.context') . ' = ' . $db->quote('com_info.item'));

				// Join over the regions.
				$query->select(array('r.id as region_id', 'r.name as region_name', 'r.icon as region_icon'))
					->join('LEFT', '#__location_regions AS r ON r.id = i.region');

				// Filter by published state.
				$published = $this->getState('filter.published');
				if (!empty($published))
				{
					if (is_numeric($published))
					{
						$query->where('( i.state = ' . (int) $published .
							' OR ( i.created_by = ' . $user->id . ' AND i.state IN (0,1)))');
					}
					elseif (is_array($published))
					{
						$query->where('i.state IN (' . implode(',', $published) . ')');
					}
				}

				$db->setQuery($query);
				$data = $db->loadObject();

				if (empty($data))
				{
					$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);

					return false;
				}

				// Link
				$data->link = Route::_(InfoHelperRoute::getItemRoute($data->id));

				$data->imageFolder = $this->imageFolderHelper->getItemImageFolder($data->id);

				// Shortcodes
				$data->fulltext = str_replace('{id}', $data->id, $data->fulltext);
				$data->fulltext = str_replace('{title}', $data->title, $data->fulltext);
				$data->fulltext = str_replace('{imageFolder}', $data->imageFolder . '/content', $data->fulltext);

				// Convert the images field to an array.
				$registry     = new Registry($data->images);
				$data->images = $registry->toArray();

				$data->header = (!empty($data->header) && JFile::exists(JPATH_ROOT . '/' . $data->header)) ?
					Uri::root(true) . '/' . $data->header : false;

				// Convert the metadata field
				$data->metadata = new Registry($data->metadata);

				// Get Tags
				$mainTag = ComponentHelper::getParams('com_info')->get('tags');

				$data->tags = new TagsHelper;
				$data->tags->getItemTags('com_info.item', $data->id);
				if (!empty($data->tags->itemTags))
				{
					foreach ($data->tags->itemTags as &$tag)
					{
						$tag->main = ($tag->parent_id == $mainTag);
					}

					$data->tags->itemTags = ArrayHelper::sortObjects($data->tags->itemTags, 'main', -1);
				}

				// Get region
				$data->region_icon = (!empty($data->region_icon) && JFile::exists(JPATH_ROOT . '/' . $data->region_icon)) ?
					Uri::root(true) . $data->region_icon : false;
				if ($data->region == '*')
				{
					$data->region_icon = false;
					$data->region_name = Text::_('JGLOBAL_FIELD_REGIONS_ALL');
				}

				// Convert parameter fields to objects.
				$registry     = new Registry($data->attribs);
				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				// If no access, the layout takes some responsibility for display of limited information.
				$data->params->set('access-view', in_array($data->access, $user->getAuthorisedViewLevels()));

				$this->_item[$pk] = $data;
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
					$this->_item[$pk] = false;
				}
			}
		}

		return $this->_item[$pk];
	}

	/**
	 * Increment the hit counter for the article.
	 *
	 * @param   integer $pk Optional primary key of the article to increment.
	 *
	 * @return  boolean  True if successful; false otherwise and internal error set.
	 *
	 * @since 1.0.0
	 */
	public function hit($pk = 0)
	{
		$app      = Factory::getApplication();
		$hitcount = $app->input->getInt('hitcount', 1);

		if ($hitcount)
		{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('item.id');

			$table = Table::getInstance('Items', 'InfoTable');
			$table->load($pk);
			$table->hit($pk);
		}

		return true;
	}


	/**
	 * Method to get Related items
	 *
	 * @param int $pk Item ID
	 *
	 * @return  array
	 *
	 * @since 1.0.0
	 */
	public function getRelated($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('item.id');

		if (!isset($this->_related[$pk]))
		{
			$item     = $this->getItem($pk);
			$registry = new Registry($item->related);
			$array    = $registry->toArray();

			$related = array();
			foreach ($array as $object)
			{
				$object['id'] = (isset($object['item_id'])) ? $object['item_id'] : 0;
				unset($object['item_id']);
				if (!empty($object['id']) && $object['id'] !== $item->id)
				{
					$related[$object['id']] = new Registry($object);
				}
			}

			if (!empty($related))
			{
				$itemIds = implode(',', array_keys($related));
				$user    = Factory::getUser();

				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select(array('id', 'title as item_title', 'introimage as image'))
					->from($db->quoteName('#__info', 'i'))
					->where('i.id IN (' . $itemIds . ')')
					->group(array('i.id'));

				// Filter by access level
				if (!$user->authorise('core.admin'))
				{
					$groups = implode(',', $user->getAuthorisedViewLevels());
					$query->where('i.access IN (' . $groups . ')');
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
				};

				$db->setQuery($query);
				$objects = $db->loadObjectList('id');
				foreach ($objects as $id => $object)
				{
					$relatedObject = &$related[$id];
					$image         = (!empty($object->image) && JFile::exists(JPATH_ROOT . '/' . $object->image)) ?
						Uri::root(true) . '/' . $object->image : false;

					$relatedObject->set('image', $image);
					$relatedObject->set('item_title', $object->item_title);
					$relatedObject->set('link', Route::_(InfoHelperRoute::getItemRoute($object->id)));
				}
			}

			$this->_related[$pk] = $related;
		}

		return $this->_related[$pk];
	}

	/**
	 * Method to get Related items
	 *
	 * @param int $pk Item ID
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getComments($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('item.id');
		if (!isset($this->_comments[$pk]))
		{
			$item = $this->getItem($pk);

			JLoader::register('DiscussionsHelperTopic', JPATH_SITE . '/components/com_discussions/helpers/topic.php');
			$data             = array();
			$data['context']  = 'com_info.item';
			$data['item_id']  = $item->id;
			$data['topic_id'] = $item->discussions_topic_id;

			$data['create_topic'] = array(
				'context'    => 'com_info.item',
				'item_id'    => $item->id,
				'title'      => $item->title,
				'text'       => '{info id="' . $item->id . '" layout="discussions"}',
				'state'      => $item->state,
				'access'     => $item->access,
				'created_by' => $item->created_by,
				'region'     => $item->region,
				'tags'       => (!empty($item->tags->itemTags)) ?
					implode(',', ArrayHelper::getColumn($item->tags->itemTags, 'tag_id')) : ''
			);

			$comments             = DiscussionsHelperTopic::getIntegration($data);
			$this->_comments[$pk] = $comments;

		}

		return $this->_comments[$pk];
	}

}