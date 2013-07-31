<?php
/**
 * @package    EasyStaging
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
class JFormFieldEasyStagingAction extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $type = 'EasyStagingAction';

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
		$actionChoices[] = array('value' => '', 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION_LABEL'), 'disable' => 'true');
		$actionChoices[] = array('value' => 0, 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION0'));
		$actionChoices[] = array('value' => '', 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION_PUSH_DIV_LABEL'), 'disable' => 'true');
		$actionChoices[] = array('value' => 1, 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION1'));
		$actionChoices[] = array('value' => 2, 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION2'));
		$actionChoices[] = array('value' => '', 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION_PULLPUSH_DIV_LABEL'), 'disable' => 'true');
		$actionChoices[] = array('value' => 3, 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION3'));
		$actionChoices[] = array('value' => '', 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION_PULL_DIV_LABEL'), 'disable' => 'true');
		$actionChoices[] = array('value' => 4, 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION4'));
		$actionChoices[] = array('value' => 5, 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION5'));
		$actionChoices[] = array('value' => 6, 'text' => JText::_('COM_EASYSTAGING_TABLE_ACTION6'));

		return $actionChoices;
	}
}