<?php
/**
 * @package		PatchTester
 * @copyright	Copyright (C) 2011 Ian MacLennan, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Methods supporting pull requests.
 *
 * @package	    PatchTester
 */
class PatchtesterModelPull extends JModel
{

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since       1.6
	 */
	protected function populateState()
	{
		// Load the parameters.
		$params = JComponentHelper::getParams('com_patchtester');
		$this->setState('params', $params);
		$this->setState('github_user', $params->get('org'));
		$this->setState('github_repo', $params->get('repo'));

		parent::populateState();
	}

	protected function parsePatch($patch)
	{
		$state = 0;
		$files = array();

		foreach ($patch AS $line) {
			switch ($state)
			{
				case 0:
					if (strpos($line, 'diff --git') === 0) {
						$state = 1;
					}
					$file = new stdClass;
					$file->action = 'modified';
					break;

				case 1:
					if (strpos($line, 'index') === 0) {
						$file->index = substr($line, 6);
					}

					if (strpos($line, '---') === 0) {
						$file->old = substr($line, 6);
					}

					if (strpos($line, '+++') === 0) {
						$file->new = substr($line, 6);
					}

					if (strpos($line, 'new file mode') === 0) {
						$file->action = 'added';
					}

					if (strpos($line, 'deleted file mode') === 0) {
						$file->action = 'deleted';
					}

					if (strpos($line, '@@') === 0) {
						$state = 0;
						$files[] = $file;
					}
					break;
			}
		}
		return $files;
	}

	public function apply($id)
	{
		jimport('joomla.client.github');
		jimport('joomla.client.http');

		$table = JTable::getInstance('tests', 'PatchTesterTable');
		$github = new JGithub();
		$pull = $github->pulls->get($this->getState('github_user'), $this->getState('github_repo'), $id);
		$patchUrl = $pull->diff_url;

		$http = new JHttp;

		$patch = $http->get($patchUrl)->body;
		$patch = explode("\n", $patch);


		if (is_null($pull->head->repo)) {
			$this->setError(JText::_('COM_PATCHTESTER_REPO_IS_GONE'));
			return false;
		}

		$files = $this->parsePatch($patch);

		foreach($files AS $file) {
			if ($file->action == 'added' || $file->action == 'modified') {
				$http = new JHttp;

				$url = 'https://raw.github.com/' . $pull->head->user->login . '/' . $pull->head->repo->name . '/' .
				$pull->head->ref . '/' . $file->new;


				// if the backup file already exists, we can't apply the patch
				if ($file->action != 'deleted' && file_exists(JPATH_COMPONENT . '/backups/' . md5($file->new) . '.txt')) {
					$this->setError(JText::_('COM_PATCHTESTER_CONFLICT'));
					return false;
				}

				if (($file->action == 'deleted' || $file->action == 'modified') && !file_exists(JPATH_ROOT . '/' . $file->old)) {
					$this->setError(JText::_('COM_PATCHTESTER_FILE_DELETED_MODIFIED_DOES_NOT_EXIST'));
					return false;
				}

				try {
					$file->body = $http->get($url)->body;
				} catch (Exception $e) {
					$this->setError(JText::_('COM_PATCHTESTER_APPLY_FAILED_ERROR_RETRIEVING_FILE'));
					return false;
				}
			}
		}

		// at this point, we have ensured that we have all the new files and there are no conflicts

		foreach ($files AS $file)
		{
			// we only create a backup if the file already exists
			if ($file->action == 'deleted' || (file_exists(JPATH_ROOT . '/' . $file->new) && $file->action == 'modified')) {
				JFile::copy(JPath::clean(JPATH_ROOT . '/' . $file->old), JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt');
			}

			switch ($file->action)
			{
				case 'modified':
				case 'added':
					JFile::write(JPath::clean(JPATH_ROOT . '/' . $file->new), $file->body);
					break;

				case 'deleted':
					JFile::delete(JPATH::clean(JPATH_ROOT . '/' . $file->old));
					break;
			}
		}
		$table->pull_id = $pull->number;
		$table->data = json_encode($files);
		$table->patched_by = JFactory::getUser()->id;
		$table->applied = 1;
		$version = new JVersion;
		$table->applied_version = $version->getShortVersion();
		$result = $table->store();

		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	public function revert($id)
	{
		$table = JTable::getInstance('tests', 'PatchTesterTable');

		$table->load($id);

		// we don't want to restore files from an older version
		$version = new JVersion;
		if ($table->applied_version != $version->getShortVersion()) {
			$table->applied = 0;
			$table->applied_version = '';
			$table->store();
			return true;
		}

		$files = json_decode($table->data);

		foreach ($files AS $file) {
			switch ($file->action) {
				case 'deleted':
				case 'modified':
					JFile::copy(JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt', JPATH_ROOT . '/' . $file->old);
					JFile::delete(JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt');
					break;

				case 'added':
					JFile::delete(JPath::clean(JPATH_ROOT . '/' . $file->new));
					break;
			}
		}

		$table->applied_version = '';
		$table->applied = 0;
		$table->store();

		return true;
	}

}
