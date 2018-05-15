<?php
/**
 * @package    Information - Latest Module
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

// Include route helper
JLoader::register('InfoHelperRoute', JPATH_SITE . '/components/com_info/helpers/route.php');

// Initialize model
BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_info/models');
$model = BaseDatabaseModel::getInstance('List', 'InfoModel', array('ignore_request' => true));
$model->setState('list.limit', $params->get('limit', 5));
$model->setState('tag.id', $params->get('tag_id', 1));
if ((!Factory::getUser()->authorise('core.edit.state', 'com_info.item')) &&
	(!Factory::getUser()->authorise('core.edit', 'com_info.item')))
{
	$model->setState('filter.published', 1);
}
else
{
	$model->setState('filter.published', array(0, 1));
}

// Variables
$items    = $model->getItems();
$listLink = Route::_(InfoHelperRoute::getListRoute());

require ModuleHelper::getLayoutPath($module->module, $params->get('layout', 'default'));