<?php
/**
 * @package    EasyStaging_Pro
 * @author     Craig Phillips <craig@craigphillips.biz>
 * @copyright  Copyright (C) 2012 Craig Phillips Pty Ltd.
 * @license    GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @url        http://www.seepeoplesoftware.com
 */
defined('_JEXEC') or die('Restricted Access');

JFormHelper::loadFieldClass('list');
/**
 * JFormFieldEasyTable provides the options for the Table selection menu.
 *
 * @package     EasyStaging
 *
 * @subpackage  Model/Fields
 *
 * @since       1.1
 */
class JFormFieldEasyStagingRsyncDirection extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $type = 'EasyStagingRsyncDirection';

	/**
	 * getOptions() provides the options for each table action.
	 *
	 * @return  array
	 *
	 * @since   1.1
	 */
	public function getOptions()
	{
		$actionChoices = array( );
		$actionChoices[] = array('value' => 0, 'text' => JText::_('COM_EASYSTAGING_RSYNC_DIRECTION0'));
		$actionChoices[] = array('value' => 1, 'text' => JText::_('COM_EASYSTAGING_RSYNC_DIRECTION1'));
		$actionChoices[] = array('value' => 2, 'text' => JText::_('COM_EASYSTAGING_RSYNC_DIRECTION2'));

		return $actionChoices;
	}
}
