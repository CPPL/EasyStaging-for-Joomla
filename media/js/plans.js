window.addEvent('domready', function(){

    var hiwt = document.id('howitworkstoggle');
    var hiwp = document.id('howitworkspanel');

    hiwp.set('morph', {
        duration: 500
    });
    
    hiwt.addEvents({
        click: function(){
            origHeight = 132;
            bordersAndMargins = 22;
            
            if (hiwp.getSize().y == (origHeight+bordersAndMargins))
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

Joomla.submitbutton = function(pressbutton) {
	if (pressbutton == 'plans.delete')
	{
		 deleteOK = confirm(Joomla.JText._('COM_EASYSTAGING_PLAN_JS_CONFIRM_DELETE'));
		 if (deleteOK)
		 {
			 Joomla.submitform(pressbutton);
		 }
		 else
			 return 0;
	}
	Joomla.submitform(pressbutton);
}
