<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_quinty
 *
 * @copyright   Copyright (C) 2019 Punya.co.uk    All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * HTML View class for the Quinty Component
 *
 * @since  0.02.2
 */
class QuintyViewQuinty extends JViewLegacy
{
	/**
	 * Display the Quinty view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	function display($tpl = null)
	{
		// Assign data to the view
		$this->msg = 'Dummy';

		// Display the view
		parent::display($tpl);
	}
}