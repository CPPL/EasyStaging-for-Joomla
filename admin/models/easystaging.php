<?php
/**
 * EasyStaging Model for EasyStaging Component
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
 */
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
jimport( 'joomla.application.component.model' );
 
/**
 * EasyStaging Model
 *
 */
class EasyStagingModelEasyStaging extends JModel
{
    /**
     * EasyStaging data array
     *
     * @var array
     */
    var $_data;
 
    /**
     * Returns the query
     * @return string The query to be used to retrieve the rows from the database
     */
    function _buildQuery()
    {
        $query = ' SELECT * FROM #__easystaging '
        ;
        return $query;
    }
 
    /**
     * @return array Array of objects containing the data from the database
     */
    function getData()
    {
        // Lets load the data if it doesn't already exist
        if (empty( $this->_data ))
        {
            $query = $this->_buildQuery();
            $this->_data = $this->_getList( $query );
        }
 
        return $this->_data;
    }
}
