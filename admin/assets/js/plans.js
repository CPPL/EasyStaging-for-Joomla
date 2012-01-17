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
            
            if(hiwp.getSize().y == (origHeight+bordersAndMargins)){
            	targetHeight = 635;
            	this.innerHTML = '^';
            } else {
            	targetHeight = origHeight;
            	this.innerHTML = '+';
            }
               
            hiwp.morph({
                'height': targetHeight
            });
        }
    });
            
});
