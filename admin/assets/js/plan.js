if (typeof(com_EasyStaging) === 'undefined') {
	var com_EasyStaging = {};
}

window.addEvent('domready', function() {
	/* ajax replace element text */
	$('startFile' ).addEvent('click', function (event) { com_EasyStaging.ajaxCheckIn(event); } );
	$('startDBase').addEvent('click', function (event) { com_EasyStaging.ajaxCheckIn(event); } );
	$('startAll'  ).addEvent('click', function (event) { com_EasyStaging.ajaxCheckIn(event); } );
	com_EasyStaging.setUp();
});

com_EasyStaging.setUp = function ()
{
	this.currentStatusScroller = new Fx.Scroll($('currentStatus'));
	this.table_count = 0;
	this.tables_proc = 0;
	this.last_response = 0;
	this.last_notification = 0;
	this.lastRunStatus = '';
	this.SQLFileLists = new Array();
	this.requestData = new Object ();
	token = this.getToken();
	this.requestData[token] = 1;
	this.requestData['plan_id'] = this.getID();
	this.jsonURL = 'index.php?option=com_easystaging&format=json';
	this.jsonRequestObj = new Request.JSON({
		url:    'index.php?option=com_easystaging&format=json',
		method: 'get'
	})
}
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

com_EasyStaging.ajaxCheckIn = function (e)
{
	this.lockOutBtns();
	this.last_response = new Date().getTime();
	this.responseTimer = this.appendTimeSince.periodical(500,this);
	/* Once we start updates we want it be very visible. */
	$('currentStatus').setStyle('background','#fffea1');

	this.waiting();
	// Which button was pressed?
	var btnPath = e.target.id;
	// requestData is basically unchanged for each step, usuall only the task is updated.
	this.requestData['task'] = 'plan.hello';
	this.requestData['btnPath'] = btnPath;
	this.lastRunStatus = Joomla.JText._('COM_EASYSTAGING_JS_CHECK_IN_WITH_SERVER');

	var req = new Request.JSON({
		url: com_EasyStaging.jsonURL,
		method: 'get',
		data: com_EasyStaging.requestData,
		onRequest:  function() { com_EasyStaging.appendTextToCurrentStatus('<strong>' + Joomla.JText._('COM_EASYSTAGING_JSON_REQUEST_MADE_PLEASE_WAIT') + '/<strong>', false); },
		onComplete: function(response) { com_EasyStaging.processCheckIn ( response ); },
	});
	req.send();
	this.updateLastRunStatus();
}

com_EasyStaging.processCheckIn  = function ( response )
{
	this.lockOutBtns();
	this.notWaiting();
	this.appendTextToCurrentStatus(response.msg);
	if(response.status != '0') {
		this.appendTextToCurrentStatus(response.msg, false);

		switch(this.requestData.btnPath)
		{
		case 'startAll':
		case 'startFile':	
			$('startFile').value = Joomla.JText._('COM_EASYSTAGING_JS_FILES_COPYING___')
			this.setupRsync( response );
		break;

		case 'startDBase':	this.checkDBConnection( response );
		break;
		
		default: this.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JS_ERROR_UNKNOWN_PROC_PATH')+'</span>');
		}
	} else {
		this.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JS_AJAX_CHECK_IN_FAILED')+'</span>');
	}
}

com_EasyStaging.setupRsync  = function ( response )
{
	this.waiting();
	this.lastRunStatus = Joomla.JText._('COM_EASYSTAGING_JS_FILE_SYNC_IN_PROG');
	this.updateLastRunStatus();
	this.requestData['task'] = 'plan.setupRsync';
	var req = new Request.JSON({
		method: 'get',
		url: com_EasyStaging.jsonURL,
		data: com_EasyStaging.requestData,
		onRequest: function() { com_EasyStaging.appendTextToCurrentStatus('<strong>' + Joomla.JText._('COM_EASYSTAGING_JSON_START_RSYNC_STEP1') + '</strong>'); },
		onComplete: function(response) { com_EasyStaging.processRsyncSetup ( response ) },
	});
	req.send();
}

com_EasyStaging.processRsyncSetup = function ( response )
{
	this.notWaiting();
	this.appendTextToCurrentStatus(response.msg);

	if(response.status != '0') {
		this.appendTextToCurrentStatus(response.data.msg);
		this.appendTextToCurrentStatus('<em>' + response.data.fileData + '</em>');
		this.requestData['fileName'] = response.data.fullPathToExclusionFile;
		this.runRsync( response );
	} else {
		this.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JSON_RSYNC_FAILED_PROCE_TERM')+'</span>')
		this.appendTextToCurrentStatus('<em>' + response.data.msg + '</em>');
	}
}

com_EasyStaging.runRsync  = function ( response )
{
	this.waiting();
	this.requestData['task'] = 'plan.runRsync';

	var req = new Request.JSON({
		method: 'get',
		url: com_EasyStaging.jsonURL,
		data: com_EasyStaging.requestData,
		onRequest: function() { com_EasyStaging.appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_STARTING_RSYNC_PROCESS')); },
		onComplete: function(response) { com_EasyStaging.processRsyncRun( response ); }
	});

	req.send();
}

com_EasyStaging.processRsyncRun = function ( response )
{
	this.notWaiting();
	this.appendTextToCurrentStatus(response.msg);
	if(response.status != '0') {
		rsyncOutput = response.data.toString().replace(/,/g,"<br />");
		
		this.appendTextToCurrentStatus('<em>' + rsyncOutput + '</em>');
		this.appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_JS_RSYNC_PROCESS_COMPLETED') + '<br />');
		if(this.requestData.btnPath == 'startAll') {
			com_EasyStaging.checkDBConnection( response );
		} else {
			this.runFinished();
		}
	} else {
		this.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JSON_RSYNC_FAILED_PROCE_TERM')+'</span>');
	}
}

com_EasyStaging.checkDBConnection  = function ( response )
{
	this.waiting();
	this.lastRunStatus = Joomla.JText._('COM_EASYSTAGING_JS_DATABASE_REPLICATION_IN_PROG');
	this.updateLastRunStatus();
	this.requestData['task'] = 'plan.checkDBConnection';
	var req = new Request.JSON({
		method: 'get',
		url: com_EasyStaging.jsonURL,
		data: com_EasyStaging.requestData,
		onRequest: function() { com_EasyStaging.appendTextToCurrentStatus('<strong>' + Joomla.JText._('COM_EASYSTAGING_JS_CHECKING_REMOTE_LIVE_DB_CONN') + '</strong>'); com_EasyStaging.updateLastRunStatus(Joomla.JText._('COM_EASYSTAGING_JS_CHECKING_REMOTE_LIVE_DB_CONN'));},
		onComplete: function(response) { com_EasyStaging.processCheckDBConnection ( response ) }
	});
	req.send();
}

com_EasyStaging.processCheckDBConnection = function ( response )
{
	this.notWaiting();
	if(response.status != '0') {
		this.lastRunStatus = Joomla.JText._('COM_EASYSTAGING_JS_CONNECTED_WITH_REMOT_DB');
		this.updateLastRunStatus();
		this.appendTextToCurrentStatus('<em>' + response.msg + '</em><br />');
		this.getDBTables( response );
	} else {
		this.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JS_DATABASE_REPLICATION_FAILE_CAN')+'</span>');
	}	
}

com_EasyStaging.getDBTables  = function ( response )
{
	this.waiting();
	this.lastRunStatus = Joomla.JText._('COM_EASYSTAGING_JS_STARTING_TABLE_COP_DESC');
	this.updateLastRunStatus();
	this.requestData['task'] = 'plan.getDBTables';
	this.requestData['remoteTableList'] = response.data;
	var req = new Request.JSON({
		method: 'get',
		url: com_EasyStaging.jsonURL,
		data: com_EasyStaging.requestData,
		onRequest: function() { com_EasyStaging.appendTextToCurrentStatus('<strong>' + Joomla.JText._('COM_EASYSTAGING_JS_STARTING_DATABASE_REPLICATION') + '</strong>'); },
		onComplete: function(response) { com_EasyStaging.processGetDBTables ( response ) }
	});
	req.send();
}

com_EasyStaging.processGetDBTables  = function ( response )
{
	this.notWaiting();
	com_es_table_count = response.tablesFound;
	this.table_count = response.tablesFound;
	this.lastRunStatus = '' + this.table_count + Joomla.JText._('COM_EASYSTAGING_JS__TABLES_FOUND_');
	this.updateLastRunStatus();
	
	this.appendTextToCurrentStatus(response.msg);
	if(response.status != '0') {
		this.appendTextToCurrentStatus('<em>' + response.data.msg + '</em>');
		this.createTableExportFiles( response.data );
	} else {
		this.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JS_DATABASE_REPLICATION_FAILE_DESC')+'</span>');
	}	
}

com_EasyStaging.createTableExportFiles  = function ( tableData )
{
	this.waiting();
	this.requestData['task'] = 'plan.createTableExportFile';
	
	rows = tableData.rows;
	
	// Process each table individually
	createExportSQLRequests = {};
	rows.each(function(row){
		com_EasyStaging.requestData['tableName'] = row.tablename;
		createExportSQLRequests[row.tablename] = new Request.JSON ({
			method: 'get',
			url: com_EasyStaging.jsonURL,
			data: com_EasyStaging.requestData,
			onRequest: function() { com_EasyStaging.appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_JS_STARTING_COPY_O_DESC') + row.tablename); },
			onComplete: function(response) { com_EasyStaging.processCreateTableExportFiles ( response ); }
		});
	});

	this.tableCopyQueue = new Request.Queue ({
		requests: createExportSQLRequests,
		stopOnFailure: false,
		onComplete:function() { com_EasyStaging.appendTextToCurrentStatus('--')}
	});
	
	Object.each(createExportSQLRequests, function(rrValue, rrKey){rrValue.send();});
}

com_EasyStaging.processCreateTableExportFiles  = function ( response )
{
	this.last_response = new Date().getTime();
	this.tables_proc = this.tables_proc + 1;

	if(this.tables_proc > 1) {
		this.lastRunStatus = (this.tables_proc + Joomla.JText._('COM_EASYSTAGING_JS_TABLES_PROCESSED'));
	} else {
		this.lastRunStatus = (this.tables_proc + Joomla.JText._('COM_EASYSTAGING_JS_TABLE_PROCESSED'));
	}
	this.updateLastRunStatus();
	this.appendTextToCurrentStatus(response.msg);
	this.appendTextToCurrentStatus('<em>' + response.log);

	if(response.status != '0')
	{
		this.SQLFileLists.push(response.pathToSQLFile);
		this.appendTextToCurrentStatus(response.data + '</em>');
	} else {
		this.appendTextToCurrentStatus(response.data);
		this.appendTextToCurrentStatus('<br />' + response.pathToSQLFile + '</em>');
	}

	if(this.tables_proc == this.table_count) {
		this.tables_proc = 0;
		this.appendTextToCurrentStatus('<br /><strong>' + Joomla.JText._('COM_EASYSTAGING_JS_RUNNING_TABLE_SQL_EXPORTS') + '</strong>');
		this.runTableExports();
	}
}

com_EasyStaging.runTableExports = function( )
{
	this.requestData['task'] = 'plan.runTableExport';
	runTableRequests = {};
	this.SQLFileLists.each(function(path){
		com_EasyStaging.requestData['pathToSQLFile'] = path;
		runTableRequests[path] = new Request.JSON ({
			method: 'get',
			url: com_EasyStaging.jsonURL,
			data: com_EasyStaging.requestData,
			onRequest: function() { com_EasyStaging.appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_JS_LOADING_SQL_EXPORT_FILE') + path); },
			onComplete: function(response) { com_EasyStaging.processRunTableExport ( response ); }
		});
	})

	this.runTableExportsQueue = new Request.Queue ({
		requests: runTableRequests,
		stopOnFailure: false,
		onComplete:function() { com_EasyStaging.appendTextToCurrentStatus('--')}
	});
	
	Object.each(runTableRequests, function(rteValue, rteKey){rteValue.send();});
}

com_EasyStaging.processRunTableExport = function ( response )
{
	this.last_response = new Date().getTime();
	if(response.status != '0')
	{
		this.tables_proc = this.tables_proc + 1;
		if(this.tables_proc > 1) {
			this.lastRunStatus = (this.tables_proc + Joomla.JText._('COM_EASYSTAGING_JS_TABLES_EXPORTED'));
		} else {
			this.lastRunStatus = (this.tables_proc + Joomla.JText._('COM_EASYSTAGING_JS_TABLE_EXPORTED'));
		}

		this.updateLastRunStatus();
		this.appendTextToCurrentStatus(response.msg);

		if(this.tables_proc == this.table_count) {
			this.tables_proc = 0;
			this.runFinished();
		}
	} else {
		this.runTableExportsQueue.clear();
		clearInterval(this.responseTimer);
		this.notWaiting();
		this.enableBtns();
		this.lastRunStatus = Joomla.JText._('COM_EASYSTAGING_JS_RUN_ABORTED_TABLE_EXPORT_FAILED');
		this.updateLastRunStatus();
		this.appendTextToCurrentStatus(response.msg);
	}
}

com_EasyStaging.getID  = function ()
{
	return $('id').value;
}
com_EasyStaging.getToken = function ()
{
	var els = document.getElementsByTagName('input');
	for (var i = 0; i < els.length; i++) {
		if ((els[i].type == 'hidden') && (els[i].name.length == 32) && els[i].value == '1') {
			theToken = els[i].name;
		}
	}
	return theToken;
}
com_EasyStaging.getTokenSegment = function ()
{
	token = this.getToken();
	tokenSegment = ('&' + token + '=1'); 
	return tokenSegment;
}

com_EasyStaging.updateLastRunStatus = function (updateText)
{
	updateText = typeof(updateText) != 'undefined' ? updateText : '';
	this.setLastRunStatus( Joomla.JText._('COM_EASYSTAGING_JS_IN_PROGRESS') + this.lastRunStatus + ' ' + updateText );
}
com_EasyStaging.setLastRunStatus = function (updateText, append)
{
	updateText = typeof(updateText) != 'undefined' ? updateText : '';
	append = typeof(append) != 'undefined' ? append : false;
	if(append)
	{
		$('lastRunStatus').innerHTML = $('lastRunStatus').innerHTML + updateText;
	} else {
		$('lastRunStatus').innerHTML = updateText;
	}
}

com_EasyStaging.appendResponseMSGToCurrentStatus  = function (response, append)
{
	append = typeof(append) != 'undefined' ? append : true;
	if(response.status == '1') {
		this.appendTextToCurrentStatus( response.msg, append );		
	} else {
		this.appendTextToCurrentStatus( response.msg, append );
	}
}

com_EasyStaging.appendTextToCurrentStatus  = function (text, append)
{
	append = typeof(append) != 'undefined' ? append : true;
	if(append) {
		$('currentStatus').innerHTML = $('currentStatus').innerHTML + '<br />' + text;
	} else {
		$('currentStatus').innerHTML = text;
	}
	this.currentStatusScroller.toBottom();
}

com_EasyStaging.appendTimeSince = function ()
{
	theNowDateObj = new Date();
	theNowMilliseconds = theNowDateObj.getTime();
	theDiff = theNowMilliseconds - this.last_response;
	if((theNowMilliseconds - this.last_notification) >= 500)
	{
		this.updateLastRunStatus('<br /><em>' + (theDiff/1000).round(1) + ' ' + Joomla.JText._('COM_EASYSTAGING_JS_SECONDS_SINCE_LAS_DESC') + '</em>', true);
		this.last_notification = theNowMilliseconds;
	}
}

com_EasyStaging.waiting = function (el)
{
	el = typeof(el) != 'undefined' ? el : 'lastRunStatus';
	$(el).addClass('waiting');
}

com_EasyStaging.notWaiting = function (el)
{
	el = typeof(el) != 'undefined' ? el : 'lastRunStatus';
	$(el).removeClass('waiting');
	nowDateObj = new Date();
	this.last_response = nowDateObj.getTime();
	this.last_notification = this.last_response;
}
com_EasyStaging.runFinished = function()
{
	clearInterval(this.responseTimer);
	this.setLastRunStatus(Joomla.JText._('COM_EASYSTAGING_JS_PLAN_RUN_COMPLETED'));
	this.currentStatusScroller.toBottom.delay(100,this.currentStatusScroller);
	this.notWaiting();
	this.enableBtns();

	// Finally set the "last run" timestamp for the Plan.
	this.requestData['task'] = 'plan.finishRun';
	var req = new Request.JSON({
		method: 'get',
		url: com_EasyStaging.jsonURL,
		data: com_EasyStaging.requestData,
		onComplete: function(response) { com_EasyStaging.setLastRunStatus(response.msg,false); }
	});
	req.send();

}


com_EasyStaging.lockOutBtns = function()
{
	$('startFile').disabled = 1;
	$('startFileBtn').addClass('com_easystaging_plan_btn_off');
	$('startDBase').disabled = 1;
	$('startDBaseBtn').addClass('com_easystaging_plan_btn_off');
	$('startAll').disabled = 1;
	$('startAllBtn').addClass('com_easystaging_plan_btn_off');
}
com_EasyStaging.enableBtns = function()
{
	$('startFile').disabled = 0;
	$('startFileBtn').removeClass('com_easystaging_plan_btn_off');
	$('startDBase').disabled = 0;
	$('startDBaseBtn').removeClass('com_easystaging_plan_btn_off');
	$('startAll').disabled = 0;
	$('startAllBtn').removeClass('com_easystaging_plan_btn_off');
}
