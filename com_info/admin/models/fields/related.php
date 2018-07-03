<?php
/**
 * @package    Information Component
 * @version    1.0.3
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;

FormHelper::loadFieldClass('list');

class JFormFieldRelated extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'related';

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

		$current = $this->form->getValue('id');

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(array('id', 'title'))
			->from('#__info');
		$db->setQuery($query);
		$items = $db->loadObjectList('id');

		$this->value = (!empty($this->value) && $this->multiple && !is_array($this->value)) ?
			explode(',', $this->value) : $this->value;

		foreach ($items as $item)
		{
			$text            = '[' . $item->id . '] ' . $item->title;
			$option          = new stdClass();
			$option->value   = $item->id;
			$option->text    = $text;
			$option->disable = ($item->id == $current);
			$options[]       = $option;
		}

		return $options;
	}
}