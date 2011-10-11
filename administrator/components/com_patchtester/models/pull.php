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
                // Initialise variables.
                $app = JFactory::getApplication('administrator');

                // Load the parameters.
                $params = JComponentHelper::getParams('com_patchtester');
                $this->setState('params', $params);
                $this->setState('github_user', $params->get('org'));
                $this->setState('github_repo', $params->get('repo'));

                parent::populateState();
        }


	public function apply($id)
	{
		jimport('joomla.client.github');
		jimport('joomla.client.http');

		$table = JTable::getInstance('tests', 'PatchTesterTable');
		$github = new JGithub();
		$pull = $github->pulls->get($this->getState('github_user'), $this->getState('github_repo'), $id);
		$patchUrl = $pull->patch_url;

		$http = new JHttp;

		$patch = $http->get($patchUrl)->body;
		$patch = explode("\n", $patch);

		$files = array();

		foreach ($patch AS $line)
		{
			if (is_null($pull->head->repo)) {
				$this->setError(JText::_('COM_PATCHTESTER_REPO_IS_GONE'));
				return false;
			}

			if (strpos($line, '--- a/') === 0) {
				$file = substr($line, 6);

				// if the backup file already exists, we can't apply the patch
				if (file_exists(JPATH_COMPONENT . '/backups/' . md5($file) . '.txt')) {
					$this->setError(JText::_('COM_PATCHTESTER_CONFLICT'));
					return false;
				}
				$files[] = $file;
			}
		}
		
		foreach ($files AS $file)
		{
			$http = new JHttp;
			// we only create a backup if the file already exists
			if (file_exists(JPATH_ROOT . '/' . $file)) {
				JFile::copy(JPath::clean(JPATH_ROOT . '/' . $file), JPATH_COMPONENT . '/backups/' . md5($file) . '.txt');
			}

			$url = 'https://raw.github.com/' . $pull->head->user->login . '/' . $pull->head->repo->name . '/' .
				$pull->head->ref . '/' . $file;
			try {
				$newFile = $http->get($url);
				JFile::write(JPath::clean(JPATH_ROOT . '/' . $file), $newFile->body);
			} catch (Exception $e) {
				echo $e->getMessage();
				echo $url;
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
			JFile::copy(JPATH_COMPONENT . '/backups/' . md5($file) . '.txt', JPATH_ROOT . '/' . $file);
			JFile::delete(JPATH_COMPONENT . '/backups/' . md5($file) . '.txt');
		}

		$table->applied_version = '';
		$table->applied = 0;
		$table->store();

		return true;
	}

}
