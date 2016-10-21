Joomla.submitbutton = function(pressbutton) {
    "use strict";
	if (pressbutton === 'plans.delete')
	{
		 var deleteOK = confirm(Joomla.JText._('COM_EASYSTAGING_PLAN_JS_CONFIRM_DELETE'));
		 if (deleteOK)
		 {
			 Joomla.submitform(pressbutton);
		 }
		 else
         {
             return 0;
         }
	}
	Joomla.submitform(pressbutton);
};
