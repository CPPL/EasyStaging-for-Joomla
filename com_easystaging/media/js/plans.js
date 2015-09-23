if(typeof jQuery === 'undefined')
{
    window.addEvent('domready', function(){
        "use strict";

        var hiwt = document.id('howitworkstoggle');
        var hiwp = document.id('howitworkspanel');

        hiwp.set('morph', {
            duration: 300
        });

        hiwt.addEvents({
            click: function(){
                var origHeight = 142;
                var bordersAndMargins = 22;
                var targetHeight;

                if (hiwp.getSize().y === (origHeight+bordersAndMargins))
                {
                    targetHeight = 635;
                    this.innerHTML = '^';
                }
                else
                {
                    targetHeight = origHeight;
                    this.innerHTML = '+';
                }

                hiwp.morph({
                    'height': targetHeight
                });
            }
        });

    });
}

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
