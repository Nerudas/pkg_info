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

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('formbehavior.chosen', 'select');

$filters = array_keys($this->filterForm->getGroup('filter'));
?>
<form action="<?php echo htmlspecialchars(Factory::getURI()->toString()); ?>" method="get" name="adminForm">
	<?php foreach ($filters as $filter): ?>
		<?php echo $this->filterForm->renderField(str_replace('filter_', '', $filter), 'filter'); ?>
	<?php endforeach; ?>
	<button type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
	<a href="<?php echo $this->link; ?>"><?php echo Text::_('JCLEAR'); ?></a>
</form>
<?php if (!empty($this->items))
{
	foreach ($this->items as $item)
	{
		echo LayoutHelper::render($item->layout, $item);
	}
}; ?>


<?php echo $this->pagination->getListFooter(); ?>


