<?php
/**
 * @package    Information Component
 * @version    1.1.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

$app = Factory::getApplication();
$doc = Factory::getDocument();

HTMLHelper::stylesheet('media/com_info/css/admin-item.min.css', array('version' => 'auto'));

HTMLHelper::_('jquery.framework');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen', 'select');

$doc->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "item.cancel" || document.formvalidator.isValid(document.getElementById("item-form")))
		{
			Joomla.submitform(task, document.getElementById("item-form"));
		}
	};
');
?>
<form action="<?php echo Route::_('index.php?option=com_info&view=item&id=' . $this->item->id); ?>"
	  method="post"
	  name="adminForm" id="item-form" class="form-validate" enctype="multipart/form-data">
	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
	<div class="form-horizontal">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'fulltext')); ?>

		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'fulltext', Text::_('COM_INFO_ITEM_FULLTEXT')); ?>
		<div class="row-fluid">
			<div class="span9">
				<fieldset class="adminform">
					<?php echo $this->form->getInput('fulltext'); ?>
				</fieldset>
			</div>
			<div class="span3">
				<div class="control-group hidden">
					<div class="well">
						<div class="lead">
							<?php echo Text::_('COM_INFO_ITEM_SHORTCODES'); ?>
						</div>
						<div>
							<div class="row-fluid">
								<div class="span4 text-right"><strong class="text-error">{id}</strong></div>
								<div class="span8"><?php echo Text::_('COM_INFO_ITEM_SHORTCODES_ID'); ?></div>
							</div>
							<div class="row-fluid">
								<div class="span4 text-right"><strong class="text-error">{title}</strong></div>
								<div class="span8">
									<?php echo Text::_('COM_INFO_ITEM_SHORTCODES_TITLE'); ?>
								</div>
							</div>
							<div class="row-fluid">
								<div class="span4 text-right"><strong class="text-error">{imageFolder}</strong></div>
								<div class="span8">
									<?php echo Text::_('COM_INFO_ITEM_SHORTCODES_IMAGEFOLDER'); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<fieldset class="form-vertical">
					<?php echo $this->form->renderFieldset('global'); ?>
				</fieldset>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>


		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'introtext', Text::_('COM_INFO_ITEM_INTROTEXT')); ?>
		<div class="row-fluid">
			<div class="span9">
				<fieldset class="adminform">
					<?php echo $this->form->getInput('introtext'); ?>
				</fieldset>
			</div>
			<div class="span3">
				<fieldset class="form-vertical">
					<?php echo $this->form->renderField('list_item_layout', 'attribs'); ?>
				</fieldset>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'images', Text::_('COM_INFO_ITEM_IMAGES_FIELDSET')); ?>
		<?php echo $this->form->renderField('imagefolder'); ?>
		<p class="lead"><?php echo Text::_('COM_INFO_ITEM_IMAGES'); ?></p>
		<?php echo $this->form->getInput('images'); ?>
		<hr>
		<p class="lead"><?php echo Text::_('COM_INFO_ITEM_HEADER'); ?>(1200 x 392)</p>
		<?php echo $this->form->getInput('header'); ?>
		<hr>
		<?php echo $this->form->renderField('introimage'); ?>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php
		echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'tags', Text::_('JTAG'));
		echo $this->form->getInput('tags');
		echo HTMLHelper::_('bootstrap.endTab');
		?>

		<?php
		echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'attribs', Text::_('COM_INFO_ITEM_SETTINGS'));
		echo $this->form->renderFieldset('attribs');
		echo HTMLHelper::_('bootstrap.endTab');
		?>

		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span6">
				<?php echo $this->form->renderFieldset('publishingdata'); ?>
			</div>
			<div class="span6">
				<?php echo $this->form->renderFieldset('metadata'); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="return" value="<?php echo $app->input->getCmd('return'); ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
