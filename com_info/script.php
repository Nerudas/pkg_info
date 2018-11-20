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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class com_InfoInstallerScript
{
	/**
	 * Runs right after any installation action is preformed on the component.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	function postflight()
	{
		$path = '/components/com_info';

		$this->fixTables($path);
		$this->tagsIntegration();
		$this->createImageFolder();
		$this->moveLayouts($path);

		return true;
	}

	/**
	 * Create or image folders
	 *
	 * @since 1.0.0
	 */
	protected function createImageFolder()
	{
		$folder = JPATH_ROOT . '/images/info';
		if (!JFolder::exists($folder))
		{
			JFolder::create($folder);
			JFile::write($folder . '/index.html', '<!DOCTYPE html><title></title>');
		}
		$folder = JPATH_ROOT . '/images/info/tags';
		if (!JFolder::exists($folder))
		{
			JFolder::create($folder);
			JFile::write($folder . '/index.html', '<!DOCTYPE html><title></title>');
		}
	}

	/**
	 * Create or update tags integration
	 *
	 * @since 1.0.0
	 */
	protected function tagsIntegration()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('type_id')
			->from($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_info.item'));
		$db->setQuery($query);
		$current_id = $db->loadResult();

		$item                                               = new stdClass();
		$item->type_id                                      = (!empty($current_id)) ? $current_id : '';
		$item->type_title                                   = 'Info item';
		$item->type_alias                                   = 'com_info.item';
		$item->table                                        = new stdClass();
		$item->table->special                               = new stdClass();
		$item->table->special->dbtable                      = '#__info';
		$item->table->special->key                          = 'id';
		$item->table->special->type                         = 'Item';
		$item->table->special->prefix                       = 'InfoTable';
		$item->table->special->config                       = 'array()';
		$item->table->common                                = new stdClass();
		$item->table->common->dbtable                       = '#__ucm_content';
		$item->table->common->key                           = 'ucm_id';
		$item->table->common->type                          = 'Corecontent';
		$item->table->common->prefix                        = 'JTable';
		$item->table->common->config                        = 'array()';
		$item->table                                        = json_encode($item->table);
		$item->rules                                        = '';
		$item->field_mappings                               = new stdClass();
		$item->field_mappings->common                       = new stdClass();
		$item->field_mappings->common->core_content_item_id = 'id';
		$item->field_mappings->common->core_title           = 'title';
		$item->field_mappings->common->core_state           = 'state';
		$item->field_mappings->common->core_alias           = 'alias';
		$item->field_mappings->common->core_created_time    = 'created';
		$item->field_mappings->common->core_modified_time   = 'modified';
		$item->field_mappings->common->core_body            = 'fulltext';
		$item->field_mappings->common->core_hits            = 'hits';
		$item->field_mappings->common->core_publish_up      = 'publish_up';
		$item->field_mappings->common->core_publish_down    = 'publish_down';
		$item->field_mappings->common->core_access          = 'access';
		$item->field_mappings->common->core_params          = 'null';
		$item->field_mappings->common->core_featured        = 'null';
		$item->field_mappings->common->core_metadata        = 'metadata';
		$item->field_mappings->common->core_language        = 'null';
		$item->field_mappings->common->core_images          = 'null';
		$item->field_mappings->common->core_urls            = 'null';
		$item->field_mappings->common->core_version         = 'null';
		$item->field_mappings->common->core_ordering        = 'publish_up';
		$item->field_mappings->common->core_metakey         = 'metakey';
		$item->field_mappings->common->core_metadesc        = 'metadesc';
		$item->field_mappings->common->core_catid           = 'null';
		$item->field_mappings->common->core_xreference      = 'null';
		$item->field_mappings->common->asset_id             = 'null';
		$item->field_mappings->special                      = new stdClass();
		$item->field_mappings->special->region              = 'region';
		$item->field_mappings                               = json_encode($item->field_mappings);
		$item->router                                       = 'InfoHelperRoute::getItemRoute';
		$item->content_history_options                      = '';

		(!empty($current_id)) ? $db->updateObject('#__content_types', $item, 'type_id')
			: $db->insertObject('#__content_types', $item);
	}

	/**
	 * Move layouts folder
	 *
	 * @param string $path path to files
	 *
	 * @since 1.0.0
	 */
	protected function moveLayouts($path)
	{
		$component = JPATH_ADMINISTRATOR . $path . '/layouts';
		$layouts   = JPATH_ROOT . '/layouts' . $path;
		if (!JFolder::exists(JPATH_ROOT . '/layouts/components'))
		{
			JFolder::create(JPATH_ROOT . '/layouts/components');
		}
		if (JFolder::exists($layouts))
		{
			JFolder::delete($layouts);
		}
		JFolder::move($component, $layouts);
	}

	/**
	 *
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance $adapter The object responsible for running this script
	 *
	 * @since 1.0.0
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		// Remove content_type
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_info.item'));
		$db->setQuery($query)->execute();

		// Remove tag_map
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__contentitem_tag_map'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_info.item'));
		$db->setQuery($query)->execute();

		// Remove ucm_content
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__ucm_content'))
			->where($db->quoteName('core_type_alias') . ' = ' . $db->quote('com_info.item'));
		$db->setQuery($query)->execute();

		// Remove images
		JFolder::delete(JPATH_ROOT . '/images/info');

		// Remove layouts
		JFolder::delete(JPATH_ROOT . '/layouts/components/com_info');
	}

	/**
	 * Method to fix tables
	 *
	 * @param string $path path to component directory
	 *
	 * @since 1.0.0
	 */
	protected function fixTables($path)
	{
		$file = JPATH_ADMINISTRATOR . $path . '/sql/install.mysql.utf8.sql';
		if (!empty($file))
		{
			$sql = JFile::read($file);

			if (!empty($sql))
			{
				$db      = Factory::getDbo();
				$queries = $db->splitSql($sql);
				foreach ($queries as $query)
				{
					$db->setQuery($db->convertUtf8mb4QueryToUtf8($query));
					try
					{
						$db->execute();
					}
					catch (JDataBaseExceptionExecuting $e)
					{
						JLog::add(Text::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $e->getMessage()),
							JLog::WARNING, 'jerror');
					}
				}
			}
		}
	}
}