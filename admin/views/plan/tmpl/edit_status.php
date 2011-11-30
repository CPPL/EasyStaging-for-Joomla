<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>
<p><?php echo 'Yay! Status!';?></p>
<div><?php echo JText::sprintf('COM_EASYSTAGING_LAST_RUN', $this->item->last_run); ?></div>
