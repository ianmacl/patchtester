<?php
/**
 * @package		PatchTester
 * @copyright	Copyright (C) 2011 Ian MacLennan, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

JHtml::_('behavior.tooltip');
JHtml::_('behavior.modal');

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));

?>
<script type="text/javascript">
	var submitpatch = function(task, id) {
		document.id('pull_id').set('value', id);
		return Joomla.submitbutton(task);
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_patchtester&view=pulls'); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<div class="filter-search fltlft">
			<label class="filter-search-lbl" for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?></label>
			<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_BANNERS_SEARCH_IN_TITLE'); ?>" />
			<button type="submit"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
	</fieldset>
	<div class="clr"> </div>

	<table class="adminlist">
		<thead>
			<tr>
				<th width="1%">
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
				</th>
				<th width="5%">
					<?php echo JHtml::_('grid.sort', 'COM_PATCHTESTER_PULL_ID', 'number', $listDirn, $listOrder); ?>
				</th>
				<th class="title">
					<?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'title', $listDirn, $listOrder); ?>
				</th>
				<th class="title">
					<?php echo JText::_('COM_PATCHTESTER_JOOMLACODE_ISSUE'); ?>
				</th>
				<th width="20%">
					<?php echo JText::_('JSTATUS'); ?>
				</th>
				<th width="20%">
					<?php echo JText::_('COM_PATCHTESTER_TEST_THIS_PATCH'); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="10">
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) :
			if (isset($this->patches[$item->number])) {
				$patch = $this->patches[$item->number];
			} else {
				$patch = false;
			}
			?>
			<tr class="row<?php echo $i % 2; ?>">
				<td class="center">
					<?php echo JHtml::_('grid.id', $i, $item->id); ?>
				</td>
				<td class="center">
					<?php echo $item->number; ?>
				</td>
				<td>
					<a href="<?php echo $item->html_url; ?>" title="<?php echo htmlspecialchars($item->body); ?>"><?php echo $item->title; ?></a>
				</td>
				<td>
					<?php
					if ($item->joomlacode_issue > 0) {
						echo '<a href="http://joomlacode.org/gf/project/joomla/tracker/?action=TrackerItemEdit&tracker_item_id=';
						echo  $item->joomlacode_issue . '" class="modal" rel="{handler: \'iframe\', size: {x: 900, y: 500}}">';
						echo '[#' . $item->joomlacode_issue . ']</a>';
					}
					?>
				</td>
				<td class="center">
					<?php
					if ($patch && $patch->applied) {
						echo JText::_('COM_PATCHTESTER_APPLIED');
					} else {
						echo JText::_('COM_PATCHTESTER_NOT_APPLIED');
					}
					?>
				</td>
				<td class="center">
					<?php
					if ($patch && $patch->applied) {
						echo '<a href="javascript:submitpatch(\'pull.revert\', '.(int)$patch->id.');">'.JText::_('COM_PATCHTESTER_REVERT_PATCH').'</a>';
					} else {
						echo '<a href="javascript:submitpatch(\'pull.apply\', '.(int)$item->number.');">'.JText::_('COM_PATCHTESTER_APPLY_PATCH').'</a>';
					}
					?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="pull_id" id="pull_id" value="" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
