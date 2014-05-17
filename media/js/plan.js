// Only define com_EasyStaging if it doesn't exist.
/* global cppl_tools */
if (typeof(com_EasyStaging) === 'undefined')
{
	var com_EasyStaging = {};
}

if (typeof jQuery === 'undefined') {
    window.addEvent('domready',
        function () {
            "use strict";
            cppl_tools.setUp('com_easystaging');
            var publishedStatus = ($('jform_published').value === 1);
            var runOnlyMode = $('runOnlyMode').value;

            if(publishedStatus)
            {
                $('startFile').addEvent('click', function (event) { com_EasyStaging.start(event.target.id); });
                $('startDBase').addEvent('click', function (event) { com_EasyStaging.start(event.target.id); });
                $('startAll').addEvent('click', function (event) { com_EasyStaging.start(event.target.id); });
            }

            if(runOnlyMode !== 1)
            {
                $('tableNamesFilter').addEvent('keyup', function (event) {setTimeout(com_EasyStaging.filterTableNames(), 0);});
                $('tf-toggle').addEvent('click', function (event) {com_EasyStaging.toggleFilters();});
                $('allTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTableActions(); } );
                $('skippedTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTableActions(0);});
                $('notSkippedTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTableActions('N'); });
                $('pushTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTableActions(1, 2); });
                $('ptpTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTableActions(3);});
                $('pullTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTableActions(4, 5, 6);});
            }

            // Just in case we want to copy the status ouput...
            $('currentStatus').addEvent('click',
                function(event) {
                    com_EasyStaging.SelectText('currentStatus');
                    var lrs = document.id('lastRunStatus');
                    if(com_EasyStaging.lrs === undefined)
                    {
                        com_EasyStaging.lrs = lrs.innerHTML;
                    }
                    lrs.innerHTML = com_EasyStaging.lrs + Joomla.JText._('COM_EASYSTAGING_JS_COPY_INSTRUCTIONS');
                }
            );
            com_EasyStaging.setUp(publishedStatus);
            com_EasyStaging.filterTableActions('N');
            com_EasyStaging.runOnlyMode = runOnlyMode;
        }
    );
}

Joomla.submitbutton = function (task) {
    "use strict";
	if (task === 'plan.cancel' || document.formvalidator.isValid(document.id('easystaging-form')))
	{
		if(task !== 'plan.cancel')
        {
            /* Trim file exclusions */
            var lfex = $('jform_localSite_file_exclusions');
            lfex.value = lfex.value.trim();
        }

		/* Call Joomla's submit */
		Joomla.submitform(task, document.id('easystaging-form'));
	}
	else
	{
		alert(Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED'));
	}
};

 com_EasyStaging.start = function (whatWeWant, confirmed)
 {
     "use strict";
     confirmed = typeof(confirmed) !== 'undefined' ? confirmed : false;
     if (confirmed || confirm(Joomla.JText._('COM_EASYSTAGING_JS_PLAN_ABOUT_TO_RUN_WARNING')))
     {
         if(whatWeWant === undefined)
         {
             this.appendTextToCurrentStatus(cppl_tools.sprintf(Joomla.JText._('COM_EASYSTAGING_JS_MISSING_PARAMETER'), 'start()'));
         }
         else
         {
             var msg = '<strong>' + Joomla.JText._('COM_EASYSTAGING_JS_REQUEST_MADE_PLEASE_WAIT') + '</strong>';
             this.runStage = msg;
             this.appendTextToCurrentStatus(msg, confirmed);
             this.requestData.step = whatWeWant;
             this.requestData.runticket = '';
             this.lockOutBtns(true);
             this.disableToolbarBtns();
             this.status();
             this.responseTimer = this.appendTimeSince.periodical(100,this);
             this.updateLastResponse();
         }
     }
 };

com_EasyStaging.status = function ()
{
    "use strict";
    com_EasyStaging.requestData.task = 'plan.status';
    com_EasyStaging.hilightStatusMessages();

    var req = new Request.JSON({
        url: com_EasyStaging.jsonURL,
        method: 'get',
        data: com_EasyStaging.requestData,
        onRequest:  function ()
        {
            com_EasyStaging.waiting();
            if((com_EasyStaging.statusTimeout !== null) && (com_EasyStaging.statusTimeout !== undefined))
            {
                window.clearTimeout(com_EasyStaging.statusTimeout);
                com_EasyStaging.statusTimeout = null;
            }
        },
        onComplete: function (response)
        {
            com_EasyStaging.reportStatus ( response );
        },
        onError: function( test, error)
        {
            com_EasyStaging.reportStatus( response );
        }
    });
    req.send();
};

com_EasyStaging.reportStatus = function ( response )
{
    "use strict";
    if (response.status !== 0)
    {
        switch (response.status)
        {
            // Finished nothing new to report.
            case 1:
                this.appendTextToCurrentStatus(response.msg);
                this.appendUpdatesToCurrentStatus(response.updates);
                this.runEnded();
                break;
            // Still have steps to process
            case 2:
                if( !cppl_tools.compareTwoObjects(this.previousResponse, JSON.parse(JSON.stringify(response))))
                {
                    // An updated response time to take notes
                    this.previousResponse = JSON.parse(JSON.stringify(response));
                    this.updateLastResponse();

                    // Only the run creation will return a runticket so we need to keep a copy so we can get run status
                    if(response.runticket !== undefined)
                    {
                        this.requestData.runticket = response.runticket;
                    }
                    if(response.msg !== this.previousResponse.msg)
                    {
                        this.appendTextToCurrentStatus(response.msg);
                    }
                    if(cppl_tools.typeof(response.running) !== 'undefined')
                    {
                        this.checkJMessage(response);
                        this.updateLastRunStatus();
                        this.appendUpdatesToCurrentStatus(response.running);
                    }
                    this.appendUpdatesToCurrentStatus(response.updates);
                    if(response.stepsleft !== undefined)
                    {
                        var stepsRemaining = response.stepsleft.length;
                        var leftMsg = '';
                        if(response.stepsleft.length !== this.stepsLeft)
                        {
                            this.stepsLeft = response.stepsleft.length;
                            if(response.stepsleft.length === 1)
                            {
                                leftMsg = Joomla.JText._('COM_EASYSTAGING_JS_STEP_LEFT');
                            }
                            else if(stepsRemaining > 1)
                            {
                                leftMsg = cppl_tools.sprintf(Joomla.JText._('COM_EASYSTAGING_JS_STEPS_LEFT'), stepsRemaining);
                            }
                            this.lastRunStatus.unshift(leftMsg);
                        }
                    }
                }
                this.statusTimeout = window.setTimeout(this.status, this.statusCheckInterval);
                break;
            // Shouldn't happen... unless we're adding new features ;)
            default:
                this.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JS_ERROR_UNKNOWN_PROC_PATH')+'</span>');
                this.runEnded(false);
                break;
        }
        this.currentStatusScroller.toBottom();
    }
    else
    {
        this.appendTextToCurrentStatus(response.error);
        this.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JS_STATUS_CHECK_FAILED')+'</span>');
        this.runEnded(false);
    }
};

com_EasyStaging.reportError = function ( text, error )
{
    "use strict";
    this.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JS_STATUS_CHECK_FAILED') + ':' + text + ':' + error + '</span>');
    this.statusTimeout = window.setTimeout(this.status, this.statusCheckInterval);
};

com_EasyStaging.appendUpdatesToCurrentStatus = function (updates)
{
    "use strict";
    // If we have updates add them to the current status
    var number_of_updates = updates.length;
    if(updates !== undefined && number_of_updates)
    {
        for (var i = 0; i< number_of_updates; i++)
        {
            var msg = updates[0].result_text;
            this.appendTextToCurrentStatus(msg);
            updates.shift();
        }
    }
};

com_EasyStaging.hilightStatusMessages = function ()
{
    "use strict";
    document.id('currentStatus').addClass('payattention');
};

com_EasyStaging.runEnded  = function (successfullRun)
{
    "use strict";
    successfullRun = typeof(successfullRun) !== 'undefined' ? successfullRun : true;
    clearInterval(this.responseTimer);
    if (successfullRun)
    {
        var theMsg = '<strong>' + Joomla.JText._('COM_EASYSTAGING_JS_PLAN_RUN_COMPLETED') + '</strong>';
        com_EasyStaging.runStage = theMsg;
        this.appendTextToCurrentStatus(theMsg,true);
        this.setLastRunStatus();
    }

    window.clearTimeout(com_EasyStaging.statusTimeout);
    this.statusTimeout = null;

    this.notWaiting();
    this.enableBtns();
};
    /*
     Old 1.0 series functions follow
     */

com_EasyStaging.setUp  = function (isPublished)
{
    "use strict";
    isPublished = typeof(isPublished) !== 'undefined' ? isPublished : true;
	this.currentStatusScroller = new Fx.Scroll($('currentStatus'));
	this.last_response         = 0;
	this.last_notification     = 0;
    this.last_action           = 0;
	this.runStage              = '';
    this.previousResponse      = null;
    this.stepsLeft             = null;
	this.lastRunStatus         = [];
	this.currentStatus         = [];
    this.toolbarClickEvents    = [];
	this.requestData           = {};
	var token                  = cppl_tools.getToken();
	this.requestData[token]    = 1;
    this.requestData.runticket = '';
	this.requestData.plan_id   = cppl_tools.getID();
	this.jsonURL               = 'index.php?option=com_easystaging&format=json';
    this.tableFilter           = null;
    this.totalTables           = 0;
    this.tablesHidden          = 0;

	if (cppl_tools.getID() === 0 && isPublished)
	{
        this.lockOutBtns(false);
    }
};

/* Feedback Section */
com_EasyStaging.checkJMessage = function(response)
{
    "use strict";
    var actionStage = this.last_action;

    if(cppl_tools.typeof(response.running) === 'array')
    {
        var runningStep = response.running[0];
        if(cppl_tools.typeof(runningStep) === 'object')
        {
            if(cppl_tools.typeof(runningStep.action_type) !== 'undefined')
            {
                actionStage = parseInt(runningStep.action_type);
                this.last_action = actionStage;
            }
        }
    }
    switch (actionStage)
    {
        case 1:
        case 2:
        case 3:
            this.runStage = Joomla.JText._('COM_EASYSTAGING_JS_FILE_SYNC_IN_PROG');
            break;
        case 10:
        case 11:
        case 12:
        case 13:
        case 14:
        case 15:
            this.runStage = Joomla.JText._('COM_EASYSTAGING_JS_TABLE_SYNC_IN_PROG');
            break;
        default :
            this.runStage = Joomla.JText._('COM_EASYSTAGING_JS_IN_PROGRESS');
            break;
    }

};

com_EasyStaging.updateLastRunStatus = function (updateText)
{
    "use strict";
	var firstMsg = this.lastRunStatus.shift();
	if (typeof(updateText) !== 'undefined')
	{
		this.lastRunStatus.unshift( updateText );
	}
	this.lastRunStatus.unshift( this.runStage + firstMsg );
	this.setLastRunStatus();
};

com_EasyStaging.setLastRunStatus = function (append)
{
    "use strict";
	append     = typeof(append) !== 'undefined' ? append : true;
	var jmsgs = [this.runStage];

	for (var i = 0; i <= this.lastRunStatus.length; i = i + 1)
	{
		jmsgs.push(this.lastRunStatus.shift());
	}
	Joomla.renderMessages({'message': jmsgs });
};

com_EasyStaging.appendTextToCurrentStatus  = function (text, append)
{
    "use strict";
    append = typeof(append) !== 'undefined' ? append : true;
    var newTextElement = Elements.from("<p>" + text + "</p>");
    var currentStatus = document.id('currentStatus');

	if (!append)
	{
        currentStatus.empty();
	}
    currentStatus.adopt(newTextElement);
};

com_EasyStaging.appendTimeSince = function ()
{
    "use strict";
	var theNowDateObj = new Date();
	var theNowMilliseconds = theNowDateObj.getTime();
	var theDiff = theNowMilliseconds - this.last_response;
	if ((theNowMilliseconds - this.last_notification) >= 500)
	{
		this.lastRunStatus.push('<em>' + (theDiff/1000).round(0) + ' ' + Joomla.JText._('COM_EASYSTAGING_JS_SECONDS_SINCE_LAS_DESC') + '</em>');
		this.setLastRunStatus();
		this.last_notification = theNowMilliseconds;
	}
};

com_EasyStaging.waiting = function (el)
{
    "use strict";
	el = typeof(el) !== 'undefined' ? el : 'lastRunStatus';
	$(el).addClass('waiting');
};

com_EasyStaging.notWaiting = function (el)
{
    "use strict";
	el = typeof(el) !== 'undefined' ? el : 'lastRunStatus';
	$(el).removeClass('waiting');
    this.updateLastResponse();
	this.last_notification = this.last_response;
};

com_EasyStaging.updateLastResponse = function ()
{
    "use strict";
    var nowDateObj = new Date();
    this.last_response = nowDateObj.getTime();
};

com_EasyStaging.lockOutBtns  = function (TabsToo)
{
    "use strict";
    TabsToo = typeof(TabsToo) !== 'undefined' ? TabsToo : true;
    // Disable Plan control buttons
    $('startFile').disabled = 1;
	$('startFileBtn').addClass('com_easystaging_plan_btn_off');
	$('startDBase').disabled = 1;
	$('startDBaseBtn').addClass('com_easystaging_plan_btn_off');
	$('startAll').disabled = 1;
	$('startAllBtn').addClass('com_easystaging_plan_btn_off');

    // Hide tabs so users can't switch during a plan run
    if(TabsToo)
    {
        $$('dl#com_easystaging_tabs.tabs').hide();
    }
};

com_EasyStaging.enableBtns  = function (TabsToo)
{
    "use strict";
    TabsToo = typeof(TabsToo) !== 'undefined' ? TabsToo : true;
	// Enable Plan control buttons
    $('startFile').disabled = 0;
	$('startFileBtn').removeClass('com_easystaging_plan_btn_off');
	$('startDBase').disabled = 0;
	$('startDBaseBtn').removeClass('com_easystaging_plan_btn_off');
	$('startAll').disabled = 0;
	$('startAllBtn').removeClass('com_easystaging_plan_btn_off');

    // Show tabs
    if(TabsToo)
    {
        $$('dl#com_easystaging_tabs.tabs').show();
    }

    // Enable Toolbar
    this.enableToolbarBtns();
};

com_EasyStaging.disableToolbarBtns  = function ()
{
    "use strict";
    // Disable Toolbar CSS
    $('toolbar').addClass('tb-off');
    var tbhref = $$('div#toolbar li.button a.toolbar');
    tbhref.addClass('tb-off');
    $$('div#toolbar li.button a.toolbar span').addClass('tb-off');

    // Store current onclick
    tbhref.each(function(ahref, index)
    {
        com_EasyStaging.toolbarClickEvents.push(ahref.onclick);
    });
    // Disable current onclick
    tbhref.each(function(ahref, index)
    {
        ahref.onclick = function()
        {
            return false;
        };
    });
};

com_EasyStaging.enableToolbarBtns = function ()
{
    "use strict";
    // Enable Toolbar CSS
    $('toolbar').removeClass('tb-off');
    $$('div#toolbar li.button a.toolbar span').removeClass('tb-off');
    var tbhref = $$('div#toolbar li.button a.toolbar');
    tbhref.removeClass('tb-off');

    if(this.toolbarClickEvents.length)
    {
    // Restore click function
        tbhref.each(function(ahref, index)
        {
            ahref.onclick = com_EasyStaging.toolbarClickEvents.shift();
        });
    }
};

com_EasyStaging.SelectText  = function (objId)
{
    "use strict";
    var range;
    this.fnDeSelectText();

    if (document.selection) {
        range = document.body.createTextRange();
        range.moveToElementText(document.id(objId));
        range.select();
    }
    else if (window.getSelection) {
        range = document.createRange();
        range.selectNode(document.id(objId));
        window.getSelection().addRange(range);
    }
};

com_EasyStaging.fnDeSelectText = function ()
{
    "use strict";
    if (document.selection)
    {
        document.selection.empty();
    }
    else if (window.getSelection)
    {
        window.getSelection().removeAllRanges();
    }
};

com_EasyStaging.toggleFilters = function()
{
    "use strict";
    var filtersDIV = document.id('table-filters');
    var fDH = filtersDIV.getHeight();
    var origHeight = 27;
    var maxOrigHeifht = 29;

    if ((fDH >= origHeight) && (fDH <= maxOrigHeifht))
    {
        filtersDIV.addClass('open');
    }
    else
    {
        filtersDIV.removeClass('open');
    }
};


com_EasyStaging.filterTableActions = function ()
{
    "use strict";
    var tableRows = $$('tr.table-settings');
    this.tableFilter = Array.clone(arguments);
    this.tablesHidden = 0;
    this.totalTables = tableRows.length;

    // Update each row
    tableRows.each(function(row, index)
        {
            var theSelectValue = row.children[2].children[0].value;
            var inFilter = this.tableFilter.indexOf(parseInt(theSelectValue));

            if((inFilter >= 0) || (this.tableFilter.length === 0) || ((theSelectValue !== "0") && (this.tableFilter[0] === 'N')))
            {
                row.removeClass('hidden');
            }
            else
            {
                row.addClass('hidden');
                this.tablesHidden++;
            }
        }, com_EasyStaging
    );

    // Notify user of changes
    var visibleTables = this.totalTables - this.tablesHidden;
    var jmsgs = [cppl_tools.sprintf(Joomla.JText._('COM_EASYSTAGING_JS_FILTER_RESULTS'), visibleTables, this.totalTables, this.tablesHidden)];
    Joomla.renderMessages({'message': jmsgs });
};

com_EasyStaging.filterTableNames = function ()
{
    "use strict";
    var tableRows = $$('tr.table-settings');
    var tnf = document.getElementById('tableNamesFilter');
    this.tableFilter = tnf.value;
    this.tablesHidden = 0;

    if (this.tableFilter.length > 2)
    {
        // Update each row
        tableRows.each(function(row, index)
            {
                var theTableName = row.children[0].children[0].innerHTML;
                var filterText = this.tableFilter;

                if(theTableName.indexOf(filterText) >= 0)
                {
                    row.removeClass('hidden');
                }
                else
                {
                    row.addClass('hidden');
                    this.tablesHidden++;
                }
            }, com_EasyStaging
        );
        // Notify user of changes
        var visibleTables = this.totalTables - this.tablesHidden;
        var jmsgs = [cppl_tools.sprintf(Joomla.JText._('COM_EASYSTAGING_JS_FILTER_RESULTS'), visibleTables, this.totalTables, this.tablesHidden)];
        Joomla.renderMessages({'message': jmsgs });
    }
 };

com_EasyStaging.checkDBSettings = function()
{
    "use strict";
    // Get our database fields
    var dbh = document.getElementById('jform_remoteSite_database_host');
    this.requestData.database_host = dbh.value;
    var dbu = document.getElementById('jform_remoteSite_database_user');
    this.requestData.database_user = dbu.value;
    var dbp = document.getElementById('jform_remoteSite_database_password');
    this.requestData.database_password = dbp.value;
    var dbn = document.getElementById('jform_remoteSite_database_name');
    this.requestData.database_name = dbn.value;
    var dbt = document.getElementById('jform_remoteSite_database_table_prefix');
    this.requestData.database_table_prefix = dbt.value;

    com_EasyStaging.requestData.task = 'plan.checkDBConnection';
    com_EasyStaging.hilightStatusMessages();

    var req = new Request.JSON({
        url: com_EasyStaging.jsonURL,
        method: 'get',
        data: com_EasyStaging.requestData,
        onRequest:  function ()
        {
            com_EasyStaging.waiting();
            if((com_EasyStaging.statusTimeout !== null) && (com_EasyStaging.statusTimeout !== undefined))
            {
                window.clearTimeout(com_EasyStaging.statusTimeout);
                com_EasyStaging.statusTimeout = null;
            }
        },
        onComplete: function (response)
        {
            com_EasyStaging.testResult ( response );
        }
    });
    req.send();
};

com_EasyStaging.testResult = function( response )
{
    "use strict";
    this.notWaiting();
    this.appendTextToCurrentStatus(response.msg);

};

com_EasyStaging.checkRsyncWorks = function()
{
    "use strict";
    // Add the "test started" message
    this.hilightStatusMessages();
    this.appendTextToCurrentStatus(Joomla.JText._('COM_EASYSTAGING_JSON_TEST_RSYNC_STARTED'));

    this.requestData.es_drfca = 1;
    this.start('startFile', true);
    this.requestData.es_drfca = 0;
};

