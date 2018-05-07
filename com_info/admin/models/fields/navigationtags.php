<?php

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

class JFormFieldNavigationTags extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'navigationtags';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since  1.0.0
	 */
	protected function getOptions()
	{
		$parent = (int) ComponentHelper::getParams('com_info')->get('navigation_tags');

		// Get tags
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(array('t.id', 't.title'))
			->from($db->quoteName('#__tags', 't'))
			->where($db->quoteName('t.alias') . ' <>' . $db->quote('root'));
		if ($parent > 1)
		{
			$query->where('(t.id = ' . $parent . ' OR t.parent_id = ' . $parent . ')');
		}
		else
		{
			$query->where('t.parent_id = 1');
		}
		$query->order($db->escape('t.lft') . ' ' . $db->escape('asc'));

		$db->setQuery($query);
		$tags = $db->loadObjectList();
		if ($parent == 1)
		{
			$root        = new stdClass();
			$root->title = Text::_('JGLOBAL_ROOT');
			$root->id    = 1;
			array_unshift($tags, $root);
		}

		$options = parent::getOptions();

		foreach ($tags as $i => $tag)
		{
			$option        = new stdClass();
			$option->text  = $tag->title;
			$option->value = ($tag->id != $parent) ? $tag->id : 1;
			if ($option->value == $this->value)
			{
				$option->selected = true;
			}
			$options[] = $option;
		}

		return $options;
	}
}