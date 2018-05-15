<?php
/**
 * @package    Information - Administrator Module
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$user    = Factory::getUser();
$columns = 5;
?>
<table class="table table-striped">
	<thead>
	<tr>
		<th style="min-width:100px" class="nowrap">
			<?php echo Text::_('JGLOBAL_TITLE'); ?>
		</th>
		<th width="10%" class="nowrap hidden-phone">
			<?php echo Text::_('JGRID_HEADING_REGION'); ?>
		</th>
		<th width="10%" class="nowrap hidden-phone">
			<?php echo Text::_('JGLOBAL_CREATED_DATE'); ?>
		</th>
		<th width="1%" class="nowrap hidden-phone">
			<?php echo Text::_('JGLOBAL_HITS'); ?>
		</th>
		<th width="1%" class="nowrap hidden-phone center">
			<?php echo Text::_('JGRID_HEADING_ID'); ?>
		</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="<?php echo $columns; ?>">
		</td>
	</tr>
	</tfoot>
	<tbody>
	<?php foreach ($items as $i => $item) :
		$canEdit = $user->authorise('core.edit', '#__info.' . $item->id);
		?>
		<tr>
			<td>
				<div>
					<?php if ($canEdit) : ?>
						<a class="hasTooltip" title="<?php echo Text::_('JACTION_EDIT'); ?>"
						   href="<?php echo Route::_('index.php?option=com_info&task=item.edit&id=' . $item->id); ?>">
							<?php echo $item->title; ?>
						</a>
					<?php else : ?>
						<?php echo $item->title; ?>
					<?php endif; ?>
					<?php if ($item->in_work): ?>
						<sup class="label label-info">
							<?php echo Text::_('COM_INFO_ITEM_IN_WORK'); ?>
						</sup>
					<?php endif; ?>
				</div>
				<div class="tags">
					<?php if (!empty($item->tags->itemTags)): ?>
						<?php foreach ($item->tags->itemTags as $tag): ?>
							<span class="label label-<?php echo ($tag->main) ? 'success' : 'inverse' ?>">
								<?php echo $tag->title; ?>
							</span>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</td>
			<td class="small hidden-phone nowrap">
				<?php echo ($item->region !== '*') ? $item->region_name :
					Text::_('JGLOBAL_FIELD_REGIONS_ALL'); ?>
			</td>
			<td class="nowrap small hidden-phone">
				<?php echo $item->created > 0 ? HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC2')) : '-' ?>
			</td>
			<td class="hidden-phone center">
				<span class="badge badge-info">
					<?php echo (int) $item->hits; ?>
				</span>
			</td>
			<td class="hidden-phone center">
				<?php echo $item->id; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
