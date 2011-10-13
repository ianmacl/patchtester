<?php
/**
 * @package		PatchTester
 * @copyright	Copyright (C) 2011 Ian MacLennan, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Pull controller class
 *
 * @package	    PatchTester
 */
class PatchtesterControllerPull extends JController
{
	public function apply()
	{
		$model = $this->getModel('pull');
		if ($model->apply(JRequest::getVar('pull_id'))) {
			$msg = 'Patch successfully applied';
			$type = 'message';
		} else {
			$msg = $model->getError();
			$type = 'error';
		}
		$this->setRedirect(JRoute::_('index.php?option=com_patchtester&view=pulls', false), $msg, $type);
	}

	public function revert()
	{
		$model = $this->getModel('pull');
		if ($model->revert(JRequest::getVar('pull_id'))) {
			$msg = 'Patch successfully reverted';
			$type = 'message';
		} else {
			$msg = 'Patch did not revert';
			$type = 'error';
		}
		$this->setRedirect(JRoute::_('index.php?option=com_patchtester&view=pulls', false), $msg, $type);
	}

}
