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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\SiteApplication;

class InfoViewItem extends HtmlView
{
	/**
	 * The Form object
	 *
	 * @var  Form
	 *
	 * @since  1.0.0
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var  object
	 *
	 * @since  1.0.0
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var  object
	 *
	 * @since  1.0.0
	 */
	protected $state;

	/**
	 * The actions the user is authorised to perform
	 *
	 * @var  JObject
	 *
	 * @since  1.0.0
	 */
	protected $canDo;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed A string if successful, otherwise an Error object.
	 *
	 * @throws Exception
	 * @since  1.0.0
	 */
	public function display($tpl = null)
	{
		$this->form  = $this->get('Form');
		$this->item  = $this->get('Item');
		$this->state = $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		return parent::display($tpl);
	}

	/**
	 * Add the type title and toolbar.
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$isNew = ($this->item->id == 0);
		$canDo = InfoHelper::getActions('com_info', 'item', $this->item->id);

		if ($isNew)
		{
			// Add title
			JToolBarHelper::title(
				TEXT::_('COM_INFO') . ': ' . TEXT::_('COM_INFO_ITEM_ADD'), 'info-circle'
			);
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				JToolbarHelper::apply('item.apply');
				JToolbarHelper::save('item.save');
			}
		}
		// Edit
		else
		{
			// Add title
			JToolBarHelper::title(
				TEXT::_('COM_INFO') . ': ' . TEXT::_('COM_INFO_ITEM_EDIT'), 'info-circle'
			);
			// Can't save the record if it's and editable
			if ($canDo->get('core.edit'))
			{
				JToolbarHelper::apply('item.apply');
				JToolbarHelper::save('item.save');
			}

			// Go to page
			JLoader::register('InfoHelperRoute', JPATH_SITE . '/components/com_info/helpers/route.php');
			$siteRouter = SiteApplication::getRouter();
			$itemLink   = $siteRouter->build(InfoHelperRoute::getItemRoute($this->item->id))->toString();
			$itemLink   = str_replace('administrator/', '', $itemLink);
			$toolbar    = JToolBar::getInstance('toolbar');
			$toolbar->appendButton('Custom', '<a href="' . $itemLink . '" class="btn btn-small btn-primary"
					target="_blank">' . Text::_('COM_INFO_ITEM_GO_TO') . '</a>', 'goTo');

		}
		// For all records, check the create permission.
		if ($canDo->get('core.create'))
		{
			JToolbarHelper::save2new('item.save2new');
		}

		JToolbarHelper::cancel('item.cancel', 'JTOOLBAR_CLOSE');
		JToolbarHelper::divider();
	}
}