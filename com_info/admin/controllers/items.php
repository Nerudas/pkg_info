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

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

class InfoControllerItems extends AdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $text_prefix = 'COM_INFO';

	/**
	 *
	 * Proxy for getModel.
	 *
	 * @param   string $name   The model name. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config The array of possible config values. Optional.
	 *
	 * @return  JModelLegacy
	 *
	 * @since  1.0.0
	 */
	public function getModel($name = 'Item', $prefix = 'InfoModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Method to set in_work to one or more records.
	 *
	 * @return  void
	 *
	 * @since   1.0.1
	 */
	public function toWork()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = $this->input->get('cid', array(), 'array');

		if (empty($ids))
		{
			JError::raiseWarning(500, Text::_('COM_ITEMS_ERROR_NO_ITEM_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Change the state of the records.
			if (!$model->toWork($ids))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				$this->setMessage(Text::plural('COM_INFO_N_ITEMS_IN_WORK', count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_info&view=items');
	}

	/**
	 * Method to unset in_work to one or more records.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function unWork()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = $this->input->get('cid', array(), 'array');

		if (empty($ids))
		{
			JError::raiseWarning(500, Text::_('COM_INFO_ERROR_NO_ITEM_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Change the state of the records.
			if (!$model->unWork($ids))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				$this->setMessage(Text::plural('COM_INFO_N_ITEMS_UN_WORK', count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_info&view=items');
	}
}