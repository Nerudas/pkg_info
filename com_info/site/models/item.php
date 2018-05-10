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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

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

				// Join over the regions.
				$query->select(array('r.id as region_id', 'r.name AS region_name', 'r.latitude as region_latitude',
					'r.longitude as region_longitude', 'r.zoom as region_zoom'))
					->join('LEFT', '#__regions AS r ON r.id = 
					(CASE i.region WHEN ' . $db->quote('*') . ' THEN 100 ELSE i.region END)');

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
	 * Method to get company employees
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
			$item  = $this->getItem($pk);
			$model = BaseDatabaseModel::getInstance('List', 'InfoModel', array('ignore_request' => true));
			$model->setState('tag.id', 1);
			$model->setState('filter.item_id', explode(',', $item->related));

			$this->_related[$pk] = $model->getItems();
		}

		return $this->_related[$pk];
	}

}