<?php
/**
 * @package		PatchTester
 * @copyright	Copyright (C) 2011 Ian MacLennan, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of pull request.
 *
 * @package	    PatchTester
 */
class PatchtesterModelPulls extends JModelList
{

	/**
	 * Constructor.
	 *
	 * @param	array	An optional associative array of configuration settings.
	 * @see		JController
	 * @since	1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'id',
				'title', 'title',
				'updated_at', 'updated_at',
				'user', 'user'
			);
		}

		parent::__construct($config);
	}


	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_patchtester');

		$this->setState('params', $params);
		$this->setState('github_user', $params->get('org', 'joomla'));
		$this->setState('github_repo', $params->get('repo', 'joomla-cms'));

		// List state information.
		parent::populateState('number', 'desc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 * @return	string		A store id.
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		return parent::getStoreId($id);
	}

	public function getAppliedPatches()
	{
		$query = $this->_db->getQuery(true);
		$query->select('*');
		$query->from('#__tests');
		$query->where('applied = 1');

		$this->_db->setQuery($query);
		$tests = $this->_db->loadObjectList('pull_id');
		return $tests;
	}

	public function getItems()
	{
		jimport('joomla.client.github');

		if ($this->getState('github_user') == '' || $this->getState('github_repo') == '') {
			return array();
		}

		$this->ordering = $this->getState('list.ordering', 'title');
		$this->orderDir = $this->getState('list.direction', 'asc');
		$search = $this->getState('filter.search');

		$github = new JGithub();
		$pulls = $github->pulls->getAll($this->getState('github_user'), $this->getState('github_repo'));
		usort($pulls, array($this, 'sortItems'));

		foreach ($pulls AS $i => &$pull)
		{
			if($search && false === strpos($pull->title, $search)) {
				unset($pulls[$i]);
				continue;
			}
			$matches = array();
			preg_match('#\[\#([0-9]+)\]#', $pull->title, $matches);
			$pull->joomlacode_issue = isset($matches[1]) ? $matches[1] : 0;
		}
		return $pulls;
	}

	public function sortItems($a, $b)
	{
		switch ($this->ordering)
		{
			case 'title' :
				return ($this->orderDir == 'asc') ? strcasecmp($a->title, $b->title) : strcasecmp($b->title, $a->title);

			case 'number' :
			default :
				return ($this->orderDir == 'asc') ? $b->number < $a->number :  $b->number > $a->number;
		}
	}
}
