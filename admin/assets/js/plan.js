window.addEvent('domready', function() {
	/* ajax replace element text */
	$('startFile' ).addEvent('click', function (event) { ajaxCheckIn(event); } );
	$('startDBase').addEvent('click', function (event) { ajaxCheckIn(event); } );
	$('startAll'  ).addEvent('click', function (event) { ajaxCheckIn(event); } );
});

/*
	Button
	  |
	ajaxCheckin    -> we use this to setup some basic variables and check that we can talk to the server
	  |
	 < > { Branch to relevant path. }
	  |
	performTask                                                                                          <--
	  - onRequest  -> set Starting Message                                                                  |
	  - onComplete -> set Response Message -> Process Result Data ( and if allOK proceed to next task ).  --|
 */

function ajaxCheckIn (e)
{
	// Which button was pressed?
	var btnPath = e.target.id;
	// Setup some basic variables that we'll use throughout the process
	var jsonURL = 'index.php?option=com_easystaging&format=json';
	var token   = com_es_getToken();
	// theRequestData is basically unchanged for each step, usuall only the task is updated.
	var theRequestData = {};
	theRequestData['task'] = 'plan.hello';
	theRequestData[token] = 1;
	theRequestData['plan_id'] = com_es_getID();
	theRequestData['btnPath'] = btnPath;

	$('lastRunStatus').innerHTML=Joomla.JText._('In progress...');

	var req = new Request.JSON({
		method: 'get',
		url: jsonURL,
		timeout: 1000,
		data: theRequestData,
		onRequest:  function() { appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_JSON_REQUEST_MADE_PLEASE_WAIT'), false); },
		onComplete: function(response) { processCheckIn ( response, jsonURL, theRequestData ); },
		onTimeout:  function() {}
	});
	req.send();
}

function processCheckIn ( response, jsonURL, theRequestData )
{
	appendResponseMSGToCurrentStatus(response);
	if(response.status != '0') {
		appendTextToCurrentStatus(response.msg, false);

		switch(theRequestData.btnPath)
		{
		case 'startAll':
		case 'startFile':	rsyncStep01( response, jsonURL, theRequestData );
		break;

		case 'startDBase':	dBaseStep01( response, jsonURL, theRequestData );
		break;
		
		default: appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('ERROR - Unknown Process path.')+'</span>');
		}
	} else {
		appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('AJAX Check-in with EasyStaging Server failed.')+'</span>');
	}
}

function rsyncStep01 ( response, jsonURL, theRequestData )
{
	theRequestData['task'] = 'plan.doRsyncStep01';
	var req = new Request.JSON({
		method: 'get',
		url: jsonURL,
		timeout: 1000,
		data: theRequestData,
		onRequest: function() { appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_JSON_START_RSYNC_STEP1')); },
		onComplete: function(response) { processRsyncStep01 ( response, jsonURL, theRequestData ) },
	});
	req.send();
}

function processRsyncStep01( response, jsonURL, theRequestData )
{
	appendTextToCurrentStatus(response.data.msg);
	if(response.status != '0') {
		appendTextToCurrentStatus('<em>' + response.data.fileData + '</em>');
		rsyncStep02( response, jsonURL, theRequestData );
	} else {
		appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JSON_RSYNC_FAILED_PROCE_TERM')+'</span>');
	}
}

function rsyncStep02 ( response, jsonURL, theRequestData )
{
	theRequestData['task'] = 'plan.doRsyncStep02';

	var req = new Request.JSON({
		method: 'get',
		url: jsonURL,
		data: theRequestData,
		onRequest: function() { appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_STARTING_RSYNC_PROCESS')); },
		onComplete: function(response) { processRsyncStep02( response, jsonURL, theRequestData ); }
	});

	req.send();
}

function processRsyncStep02( response, jsonURL, theRequestData )
{
	appendTextToCurrentStatus(response.msg);
	if(response.status != '0') {
		appendTextToCurrentStatus('<em>' + response.data + '</em>');
		appendTextToCurrentStatus(Joomla.JText._('RSync process completed.'));
		if(theRequestData.btnPath == 'startAll') {
			dBaseStep01( response, jsonURL, theRequestData );
		}
	} else {
		appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JSON_RSYNC_FAILED_PROCE_TERM')+'</span>');
	}
}

function dBaseStep01 ( response, jsonURL, theRequestData )
{
	theRequestData['task'] = 'plan.doDBaseStep01';
	var req = new Request.JSON({
		method: 'get',
		url: jsonURL,
		timeout: 1000,
		data: theRequestData,
		onRequest: function() { appendTextToCurrentStatus(Joomla.JText._('Starting Database replication.')); },
		onComplete: function(response) { processDBaseStep01 ( response, jsonURL, theRequestData ) }
	});
	req.send();
}

function processDBaseStep01( response, jsonURL, theRequestData )
{
	appendTextToCurrentStatus(response.msg);
	if(response.status != '0') {
		appendTextToCurrentStatus('<em>' + response.data.msg + '</em>');
		dBaseStep02( response.data, jsonURL, theRequestData );
	} else {
		appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('Database replication failed, process cancelled.')+'</span>');
	}	
}

function dBaseStep02 ( tableData, jsonURL, theRequestData )
{
	theRequestData['task'] = 'plan.doDBaseTableCopy';
	
	rows = tableData.rows;
	
	// Process each table individually
	replicationRequests = {};
	rows.each(function(row){
		theRequestData['tableName'] = row.tablename;
		replicationRequests[row.tablename] = new Request.JSON ({
			method: 'get',
			url: jsonURL,
			data: theRequestData,
			onRequest: function() { appendTextToCurrentStatus(Joomla.JText._('Starting copy of table: ') + row.tablename); },
			onComplete: function(response) { appendTextToCurrentStatus(response.msg); appendTextToCurrentStatus(response.log); appendTextToCurrentStatus(response.data); }
		});
	});

	var tableCopyQueue = new Request.Queue ({
		requests: replicationRequests,
		stopOnFailure: false,
		onComplete:function() { appendTextToCurrentStatus(Joomla.JText._('--'))}
	});
	
	Object.each(replicationRequests, function(rrValue, rrKey){rrValue.send();});
}

function com_es_getID ()
{
	theID = $('id').value;
	return theID;
}
function com_es_getToken()
{
	return $('esTokenForJSON').name
}
function com_es_getTokenSegment()
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
