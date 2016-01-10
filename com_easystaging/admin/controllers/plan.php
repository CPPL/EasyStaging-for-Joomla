<?php
/**
 * @package     EasyStaging
 * @link        http://seepeoplesoftware.com
 * @license     GNU/GPL
 * @copyright   Craig Phillips Pty Ltd
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');

require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/plan.php';
/**
 * Class EasyStagingControllerPlan
 * 
 * @since  1.0
 */
class EasyStagingControllerPlan extends JControllerForm
{
    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    /**
     *
     * @param null $key
     * @param null $urlVar
     *
     * @return bool
     */
    public function run($key = null, $urlVar = null)
    {
        $jinput = JFactory::getApplication()->input;

        // Initialise variables.
        $app        = JFactory::getApplication();
        $model      = $this->getModel();
        $table      = $model->getTable();
        $cid        = $jinput->post('cid', array(), 'array');
        $context    = "$this->option.run.$this->context";


        // Set the run view
        $jinput->set('layout', 'Run');

        // Determine the name of the primary key for the data.

        if (empty($key)) {
            $key = $table->getKeyName();
        }

        // To avoid data collisions the urlVar may be different from the primary key.
        if (empty($urlVar)) {
            $urlVar = $key;
        }

        // Get the previous record id (if any) and the current record id.
        $recordId = (int) (count($cid) ? $cid[0] : $jinput->get($urlVar, null, 'INT'));
        $checkin  = property_exists($table, 'checked_out');

        // Access check.
        if (!$this->allowRun($recordId)) {
            $this->setError(JText::_('COM_EASYSTAGING_PLAN_YOU_DO_NOT_HAVE_PERM'));
            $this->setMessage($this->getError(), 'error');

            return false;
        }

        // Attempt to check-out the plan to run and redirect.
        if ($checkin && !$model->checkout($recordId)) {
            // Check-out failed, bounce out as we shouldn't run a plan that may be changing.
            $app->enqueueMessage(
                JText::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()),
                'WARNING'
            );
            $this->setMessage($this->getError(), 'error');
            $this->setRedirect(
                JRoute::_(
                    'index.php?option=' . $this->option .
                    '&view=' . $this->view_list . $this->getRedirectToListAppend(),
                    false
                )
            );

            return false;
        } else {
            // Check-out succeeded, push the new record id into the session.
            $this->holdEditId($context, $recordId);
            $app->setUserState($context . '.data', null);
            $this->setRedirect(
                'index.php?option=' . $this->option .
                '&view=' . $this->view_item . $this->getRedirectToItemAppend($recordId, $urlVar)
            );

            return true;
        }
    }

    public function cancel($key = null)
    {
        /*
         * @TODO remove the JRequest::checkToken() once JInput is upto speed
         */
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $jinput = JFactory::getApplication()->input;

        // If we're canceling a plan_run view
        $layout     = $jinput->get('layout');
        $task       = $jinput->get('task');

        if (($task == 'cancel') && ($layout == 'run')) {
            // Initialise variables.
            $app        = JFactory::getApplication();
            $model      = $this->getModel();
            $table      = $model->getTable();
            $checkin    = property_exists($table, 'checked_out');
            $context    = $this->option . '.run.' . $this->context;

            if (empty($key)) {
                $key = $table->getKeyName();
            }

            $recordId   = $jinput->get($key, null, 'INT');

            // Attempt to check-in the current record.
            if ($recordId) {
                // Check we are holding the id in the edit list.
                if (!$this->checkEditId($context, $recordId)) {
                    // Somehow the person just went to the form - we don't allow that.
                    $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $recordId));
                    $this->setMessage($this->getError(), 'error');
                    $this->setRedirect(
                        JRoute::_(
                            'index.php?option=' . $this->option .
                            '&view=' . $this->view_list . $this->getRedirectToListAppend(),
                            false
                        )
                    );

                    return false;
                }

                if ($checkin) {
                    if ($model->checkin($recordId) === false) {
                        // Check-in failed, go back to the record and display a notice.
                        $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()));
                        $this->setMessage($this->getError(), 'error');
                        $this->setRedirect(
                            'index.php?option=' . $this->option .
                            '&view=' . $this->view_item . $this->getRedirectToItemAppend($recordId, $key)
                        );

                        return false;
                    }
                }
            }
        
            // Clean the session data and redirect.
            $this->releaseEditId($context, $recordId);
            $app->setUserState($context.'.data', null);
            $this->setRedirect(
                JRoute::_(
                    'index.php?option=' . $this->option .
                    '&view=' . $this->view_list . $this->getRedirectToListAppend(),
                    false
                )
            );
        
            return true;
        } else {
            return parent::cancel($key);
        }
    }

    /**
     * Check RUN perms.
     *
     * @param   int  $id  Plan id
     *
     * @return bool
     */
    protected function allowRun($id)
    {
        $canDo = PlanHelper::getActions($id);

        return $canDo->get('easystaging.run');
    }
}
