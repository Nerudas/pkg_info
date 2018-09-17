<?php
/**
 * @package    Information Component
 * @version    1.2.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::register('FieldTypesFilesHelper', JPATH_PLUGINS . '/fieldtypes/files/helper.php');

class InfoModelItem extends AdminModel
{
	/**
	 * Images root path
	 *
	 * @var    string
	 *
	 * @since  1.2.0
	 */
	protected $images_root = 'images/info';

	/**
	 * Method to get a single record.
	 *
	 * @param   integer $pk The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Convert the metadata field to an array.
			$registry       = new Registry($item->metadata);
			$item->metadata = $registry->toArray();

			// Convert the attribs field to an array.
			$registry      = new Registry($item->attribs);
			$item->attribs = $registry->toArray();

			// Convert the related field to an array.
			$registry      = new Registry($item->related);
			$item->related = $registry->toArray();

			// Get Tags
			$item->tags = new TagsHelper;
			$item->tags->getTagIds($item->id, 'com_info.item');
		}

		return $item;
	}


	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 * @since  1.0.0
	 */
	public function getTable($type = 'Items', $prefix = 'InfoTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_info/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array   $data     Data for the form.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A JForm object on success, false on failure
	 *
	 * @since  1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$app  = Factory::getApplication();
		$form = $this->loadForm('com_info.item', 'item', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		/*
		 * The front end calls this model and uses a_id to avoid id clashes so we need to check for that first.
		 * The back end uses id so we use that the rest of the time and set it to 0 by default.
		 */
		$id   = ($this->getState('item.id')) ? $this->getState('item.id') : $app->input->get('id', 0);
		$user = Factory::getUser();

		// Check for existing item.
		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_info.item.' . (int) $id)))
		{
			// Disable fields for display.
			$form->setFieldAttribute('state', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is an item you can edit.
			$form->setFieldAttribute('state', 'filter', 'unset');
		}

		// Set Check alias link
		$form->setFieldAttribute('alias', 'checkurl',
			Uri::base(true) . '/index.php?option=com_info&task=item.checkAlias');

		// Set images folder root
		$form->setFieldAttribute('images_folder', 'root', $this->images_root);

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since  1.0.0
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_info.edit.item.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_info.item', $data);

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since 1.0.0
	 */
	public function save($data)
	{
		$app    = Factory::getApplication();
		$pk     = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$filter = InputFilter::getInstance();
		$table  = $this->getTable();
		$db     = Factory::getDbo();
		$isNew  = true;

		// Load the row if saving an existing type.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}

		$data['id']    = (!isset($data['id'])) ? 0 : $data['id'];
		$data['alias'] = (!isset($data['alias'])) ? '' : $data['alias'];

		// Check alias
		$alias = $this->checkAlias($data['id'], $data['alias']);
		if (!empty($alias->msg))
		{
			$app->enqueueMessage(Text::sprintf('COM_INFO_ERROR_ALIAS', $alias->msg),
				($alias->status == 'error') ? 'error' : 'warning');
		}
		$data['alias'] = $alias->data;

		if (empty($data['created']))
		{
			$data['created'] = Factory::getDate()->toSql();
		}

		$data['modified'] = Factory::getDate()->toSql();

		if (empty($data['publish_up']))
		{
			$data['publish_up'] = $data['created'];
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_location/models', 'LocationModel');
		$regionsModel = BaseDatabaseModel::getInstance('Regions', 'LocationModel', array('ignore_request' => false));
		if (empty($data['region']))
		{
			$data['region'] = $regionsModel->getDefaultRegion()->id;
		}
		$region = $regionsModel->getRegion($data['region']);

		if (isset($data['metadata']) && isset($data['metadata']['author']))
		{
			$data['metadata']['author'] = $filter->clean($data['metadata']['author'], 'TRIM');
		}

		if (isset($data['attribs']) && is_array($data['attribs']))
		{
			$registry        = new Registry($data['attribs']);
			$data['attribs'] = $registry->toString('json', array('bitmask' => JSON_UNESCAPED_UNICODE));
		}

		if (isset($data['metadata']) && is_array($data['metadata']))
		{
			$registry         = new Registry($data['metadata']);
			$data['metadata'] =$registry->toString('json', array('bitmask' => JSON_UNESCAPED_UNICODE));
		}


		if (isset($data['related']) && is_array($data['related']))
		{
			$registry        = new Registry($data['related']);
			$data['related'] = $registry->toString('json', array('bitmask' => JSON_UNESCAPED_UNICODE));
		}

		if (empty($data['created_by']))
		{
			$data['created_by'] = Factory::getUser()->id;
		}

		$data['tags'] = (is_array($data['tags'])) ? $data['tags'] : array();
		if ($region && !empty($region->items_tags))
		{
			$data['tags'] = array_unique(array_merge($data['tags'], explode(',', $region->items_tags)));
		}

		// Get tags search
		if (!empty($data['tags']))
		{
			$query = $db->getQuery(true)
				->select(array('id', 'title'))
				->from('#__tags')
				->where('id IN (' . implode(',', $data['tags']) . ')');
			$db->setQuery($query);
			$tags = $db->loadObjectList();

			$tags_search = array();
			$tags_map    = array();
			foreach ($tags as $tag)
			{
				$tags_search[$tag->id] = $tag->title;
				$tags_map[$tag->id]    = '[' . $tag->id . ']';
			}

			$data['tags_search'] = implode(', ', $tags_search);
			$data['tags_map']    = implode('', $tags_map);
		}
		else
		{
			$data['tags_search'] = '';
			$data['tags_map']    = '';
		}

		$registry       = new Registry($data['images']);
		$data['images'] = $registry->toString('json', array('bitmask' => JSON_UNESCAPED_UNICODE));

		if (parent::save($data))
		{
			$id = $this->getState($this->getName() . '.id');

			// Save images
			if ($isNew && !empty($data['images_folder']))
			{
				$filesHelper = new FieldTypesFilesHelper();
				$filesHelper->moveTemporaryFolder($data['images_folder'], $id, $this->images_root);
			}


			// Fix alias
			if ($data['alias'] == 'id0' || $data['alias'] == 'id')
			{
				$alias = $this->checkAlias($id, $data['alias'])->data;

				$update        = new stdClass();
				$update->id    = $id;
				$update->alias = $alias;
				$db->updateObject('#__info', $update, 'id');

				$update             = new stdClass();
				$update->core_alias = $alias;

				$query = $db->getQuery(true)
					->select('core_content_id')
					->from('#__ucm_content')
					->where($db->quoteName('core_type_alias') . ' = ' . $db->quote('com_info.item'))
					->where($db->quoteName('core_content_item_id') . ' = ' . $id);
				$db->setQuery($query);
				$update->core_content_id = $db->loadResult();
				if ($update->core_content_id)
				{
					$db->updateObject('#__ucm_content', $update, 'core_content_id');
				}
			}

			// Update discussion
			JLoader::register('DiscussionsHelperTopic', JPATH_SITE . '/components/com_discussions/helpers/topic.php');
			$topicData               = array();
			$topicData['context']    = 'com_info.item';
			$topicData['item_id']    = $id;
			$topicData['title']      = $data['title'];
			$topicData['text']       = '{info id="' . $id . '" layout="discussions"}';
			$topicData['state']      = $data['state'];
			$topicData['access']     = $data['access'];
			$topicData['created_by'] = $data['created_by'];
			$topicData['region']     = $data['region'];
			$topicData['tags']       = $data['tags'];

			DiscussionsHelperTopic::updateTopic($topicData);


			return $id;
		}

		return false;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since 1.0.0
	 */
	public function delete(&$pks)
	{
		if (parent::delete($pks))
		{
			$filesHelper = new FieldTypesFilesHelper();

			// Delete images
			foreach ($pks as $pk)
			{
				$filesHelper->deleteItemFolder($pk, $this->images_root);
			}

			return true;
		}

		return false;
	}

	/**
	 * Method to check alias
	 *
	 * @param  int    $id    Item Id
	 * @param  string $alias Item alias
	 *
	 * @return stdClass|string
	 *
	 * @since 1.0.0
	 */
	public function checkAlias($id = 0, $alias = null)
	{
		$response         = new stdClass();
		$response->status = 'success';
		$response->msg    = '';
		$response->data   = $alias;
		$default_alias    = 'id' . $id;
		if (empty($alias))
		{
			$response->data = $default_alias;

			return $response;
		}

		if ($alias == $default_alias)
		{
			$response->data = $default_alias;

			return $response;
		}

		// Check idXXX
		preg_match('/^id(.*)/', $alias, $matches);
		$idFromAlias = (!empty($matches[1])) ? $matches[1] : false;
		if ($idFromAlias && $id != $idFromAlias)
		{
			$response->status = 'error';
			$response->msg    = Text::_('COM_INFO_ERROR_ALIAS_ID');
			$response->data   = $default_alias;

			return $response;
		}

		// Check numeric
		if (is_numeric($alias))
		{
			$response->status = 'error';
			$response->msg    = Text::_('COM_INFO_ERROR_ALIAS_NUMBER');
			$response->data   = $default_alias;

			return $response;
		}

		// Check slug
		if (Factory::getConfig()->get('unicodeslugs') == 1)
		{
			$slug = OutputFilter::stringURLUnicodeSlug($alias);
		}
		else
		{
			$slug = OutputFilter::stringURLSafe($alias);
		}

		if ($alias != $slug)
		{
			$response->msg  = Text::_('COM_INFO_ERROR_ALIAS_SLUG');
			$response->data = $slug;

			$alias = $slug;

		}

		// Check count
		if (mb_strlen($alias) < 5)
		{
			$response->status = 'error';
			$response->msg    = Text::_('COM_INFO_ERROR_ALIAS_LENGTH');
			$response->data   = $default_alias;

			return $response;
		}

		$table = $this->getTable();
		$table->load(array('alias' => $alias));
		if (!empty($table->id) && ($table->id != $id))
		{
			$response->status = 'error';
			$response->msg    = Text::_('COM_INFO_ERROR_ALIAS_EXIST');
			$response->data   = $default_alias;

			return $response;
		}

		// Check tags
		$tags = (int) ComponentHelper::getParams('com_info')->get('tags');

		if (!empty($tags) && is_array($tags))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('t.alias')
				->from($db->quoteName('#__tags', 't'))
				->where($db->quoteName('t.alias') . ' <>' . $db->quote('root'))
				->where('t.id IN (' . implode(',', $tags) . ')');

			$db->setQuery($query);
			$tags = $db->loadColumn();
			if (in_array($alias, $tags))
			{
				$response->status = 'error';
				$response->msg    = Text::_('COM_INFO_ERROR_ALIAS_EXIST');
				$response->data   = $default_alias;

				return $response;
			}
		}

		return $response;
	}

	/**
	 * Method to set in_work to one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return true
	 *
	 * @since 1.0.0
	 */
	public function toWork($pks = array())
	{
		try
		{
			$db = $this->getDbo();
			foreach ($pks as $pk)
			{
				$update          = new stdClass();
				$update->id      = $pk;
				$update->in_work = 1;

				$db->updateObject('#__info', $update, 'id');
			}
		}
		catch (Exception $e)
		{
			$this->setError($e);

			return false;
		}

		return true;
	}

	/**
	 * Method to unset in_work to one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return true
	 *
	 * @since 1.0.0
	 */
	public function unWork($pks = array())
	{
		try
		{
			$db = $this->getDbo();
			foreach ($pks as $pk)
			{
				$update          = new stdClass();
				$update->id      = $pk;
				$update->in_work = 0;

				$db->updateObject('#__info', $update, 'id');
			}
		}
		catch (Exception $e)
		{
			$this->setError($e);

			return false;
		}

		return true;
	}
}