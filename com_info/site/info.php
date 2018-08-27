<?php
/**
 * @package    Information Component
 * @version    1.1.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

JLoader::register('InfoHelperRoute', JPATH_SITE . '/components/com_info/helpers/route.php');

$controller = BaseController::getInstance('Info');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();