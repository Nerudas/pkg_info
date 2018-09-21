<?php
/**
 * @package    Information Component
 * @version    1.2.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

FormHelper::loadFieldClass('list');

class JFormFieldListItemLayout extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'listitemlayout';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since  1.0.0
	 */
	protected function getOptions()
	{
		$options = parent::getOptions();

		$path = '/layouts/components/com_info/listitems';

		$files = array();

		if (JFolder::exists(JPATH_ROOT . $path))
		{
			$files = array_merge($files, JFolder::files(JPATH_ROOT . $path, '.php', false));
		}

		$templates = JFolder::folders(JPATH_ROOT . '/templates');
		foreach ($templates as $template)
		{
			if (JFolder::exists(JPATH_ROOT . '/templates/' . $template . '/html/' . $path))
			{
				$files = array_merge($files, JFolder::files(JPATH_ROOT . '/templates/' . $template . '/html/' . $path,
					'.php', false));
			}
		}

		$files = array_unique($files);

		foreach ($files as $file)
		{
			$name          = str_replace('.php', '', $file);
			$option        = new stdClass();
			$option->value = $name;
			$option->text  = $name;
			$options[]     = $option;
		}

		return $options;
	}
}