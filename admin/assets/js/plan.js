window.addEvent('domready', function() {
  /* ajax replace element text */
  $('startFile' ).addEvent('click', function (event) { ajaxCheckIn(event); } );
  $('startDBase').addEvent('click', function (event) { ajaxCheckIn(event); } );
  $('startAll'  ).addEvent('click', function (event) { ajaxCheckIn(event); } );
});

function ajaxCheckIn (e)
{
	var jsonURL = 'index.php?option=com_easystaging&task=plan.hello&format=json&plan_id=' + com_es_getID() + com_es_getToken();
	var req = new Request.JSON({
      method: 'get',
      url: jsonURL,
      onRequest: function() { appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_JSON_REQUEST_MADE_PLEASE_WAIT'), false); },
      onComplete: function(response) { appendResponseMSGToCurrentStatus(response); rsyncStep01(response); }
    });
    req.send();
}

function rsyncStep01 (response)
{
	if(response.status !='0'){
		var jsonURL = 'index.php?option=com_easystaging&task=plan.doRsyncStep01&format=json&plan_id=' + com_es_getID() + com_es_getToken();
	    var req = new Request.JSON({
	        method: 'get',
	        url: jsonURL,
	        onRequest: function() { appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_JSON_START_RSYNC_STEP1')); },
	        onComplete: function(response) { appendResponseMSGToCurrentStatus(response); rsyncStep02(response); },
	        onFailure: function (response) { appendResponseMSGToCurrentStatus(response); },
	        onException: function (headerName, value) { appendResponseMSGToCurrentStatus(headerName + value) }
	      });
	    req.send();
	} else {
		appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JSON_RSYNC_FAILED_PROCE_TERM')+'</span>');
	}
}

function rsyncStep02 (response)
{
	if(response.status !='0') {
		var jsonURL = 'index.php?option=com_easystaging&task=plan.doRsyncStep02&format=json&plan_id=' + com_es_getID() + com_es_getToken();
	    var req = new Request.JSON({
	        method: 'get',
	        url: jsonURL,
	        onRequest: function() { appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_STARTING_RSYNC_PROCESS')); },
	        onComplete: function(response) { appendResponseMSGToCurrentStatus(response); rsyncStep03(response); },
	        onFailure: function (response) { appendResponseMSGToCurrentStatus(response); },
	        onException: function (headerName, value) { appendResponseMSGToCurrentStatus(headerName + value) }
	      });
	    req.send();
	} else {
		appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JSON_RSYNC_FAILED_PROCE_TERM')+'</span>');
	}
}

function rsyncStep03 (response)
{
	if(reponse.status !='0') {
		appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_FILE_SYNC_WA_DESC'));
	} else {
		appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_FILE_SYNC_FAILE_DESC'));
	}
}

function com_es_getID ()
{
	theID = $('id').value;
	return theID;
}
function com_es_getToken()
{
	token = $('esTokenForJSON').name
	tokenSegment = ('&' + token + '=1'); 
 	return tokenSegment;
 }

function appendResponseMSGToCurrentStatus (response)
{
	if(response.status == '1') {
		appendTextToCurrentStatus( response.msg );		
	} else {
		appendTextToCurrentStatus( response.msg );
	}
}

function appendTextToCurrentStatus (text, append)
{
	/* Once we start updates we want it be very visible. */
	$('currentStatus').setStyle('background','#fffea1');
	
	append = typeof(append) != 'undefined' ? append : true;
	 if(append) {
		 $('currentStatus').innerHTML = $('currentStatus').innerHTML + '<br />' + text;
	 } else {
		 $('currentStatus').innerHTML = text;
	 }
}
