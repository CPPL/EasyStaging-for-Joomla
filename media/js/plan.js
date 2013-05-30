/*jslint browser: true, mootools:true*/
/*global Joomla: true, alert: true*/

// Only define com_EasyStaging if it doesn't exist.
if (typeof(com_EasyStaging) === 'undefined')
{
	var com_EasyStaging = {};
}

	window.addEvent('domready',
        function () {
            cppl_tools.setUp('com_easystaging');
            $('startFile' ).addEvent('click', function (event) { com_EasyStaging.start(event.target.id); } );
            $('startDBase').addEvent('click', function (event) { com_EasyStaging.start(event.target.id); } );
            $('startAll'  ).addEvent('click', function (event) { com_EasyStaging.start(event.target.id); } );
            $('allTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTables(); } );
            $('skippedTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTables(0); } );
            $('pushTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTables(1, 2); } );
            $('ptpTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTables(3); } );
            $('pullTablesFilter').addEvent('click', function (event) { com_EasyStaging.filterTables(4, 5); } );

            // Just in case we want to copy the status ouput...
            $('currentStatus').addEvent('click',
                function(event) {
                    com_EasyStaging.SelectText('currentStatus');
                    lrs = document.id('lastRunStatus');
                    if(com_EasyStaging.lrs == undefined)
                    {
                        com_EasyStaging.lrs = lrs.innerHTML;
                    }
                    lrs.innerHTML = com_EasyStaging.lrs + Joomla.JText._('COM_EASYSTAGING_JS_COPY_INSTRUCTIONS');
                }
            );
            com_EasyStaging.setUp();
        }
    );

Joomla.submitbutton = function (task) {
	if (task === 'plan.cancel' || document.formvalidator.isValid(document.id('easystaging-form')))
	{
		/* Trim file exclusions */
		lfex = $('jform_localSite_file_exclusions');
		lfex.value = lfex.value.trim();
		/* Call Joomla's submit */
		Joomla.submitform(task, document.id('easystaging-form'));
	}
	else
	{
		alert(Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED'));
	}
};

 com_EasyStaging.start = function (whatWeWant)
 {
     if (confirm(Joomla.JText._('COM_EASYSTAGING_JS_PLAN_ABOUT_TO_RUN_WARNING')))
     {
         if(whatWeWant == undefined)
         {
             this.appendTextToCurrentStatus(cppl_tools.sprintf(Joomla.JText._('COM_EASYSTAGING_JS_MISSING_PARAMETER'), 'start()'));
         }
         else
         {
             var msg = '<strong>' + Joomla.JText._('COM_EASYSTAGING_JS_REQUEST_MADE_PLEASE_WAIT') + '</strong>';
             this.runStage = msg;
             this.appendTextToCurrentStatus(msg, false);
             this.requestData.step = whatWeWant;
             this.requestData.runticket = '';
             this.lockOutBtns(true);
             this.disableToolbarBtns();
             this.status();
             this.responseTimer = this.appendTimeSince.periodical(100,this);
             this.updateLastResponse();
         }
     }
 }

com_EasyStaging.status = function ()
{
    com_EasyStaging.requestData.task = 'plan.status';
    com_EasyStaging.hilightStatusMessages()

    var req = new Request.JSON({
        url: com_EasyStaging.jsonURL,
        method: 'get',
        data: com_EasyStaging.requestData,
        onRequest:  function ()
        {
            com_EasyStaging.waiting();
            if((com_EasyStaging.statusTimeout != null) && (com_EasyStaging.statusTimeout != undefined))
            {
                window.clearTimeout(com_EasyStaging.statusTimeout);
                com_EasyStaging.statusTimeout = null;
            }
        },
        onComplete: function (response)
        {
            com_EasyStaging.reportStatus ( response );
        }
    });
    req.send();
}

com_EasyStaging.reportStatus = function ( response )
{
    if (response.status !== 0)
    {
        switch (response.status)
        {
            // Finished nothing new to report.
            case 1:
                this.appendTextToCurrentStatus(response.msg);
                this.appendUpdatesToCurrentStatus(response.updates)
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
                    if(response.runticket != undefined)
                    {
                        this.requestData.runticket = response.runticket;
                    }
                    if(response.msg != this.previousResponse.msg)
                    {
                        this.appendTextToCurrentStatus(response.msg);
                    }
                    if(cppl_tools.typeof(response.running) != 'undefined')
                    {
                        this.checkJMessage(response);
                        this.updateLastRunStatus();
                        this.appendUpdatesToCurrentStatus(response.running);
                    }
                    this.appendUpdatesToCurrentStatus(response.updates);
                    if(response.stepsleft != undefined)
                    {
                        var stepsRemaining = response.stepsleft.length;
                        var leftMsg = '';
                        if(response.stepsleft.length != this.stepsLeft)
                        {
                            this.stepsLeft = response.stepsleft.length;
                            if(response.stepsleft.length == 1)
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
}

com_EasyStaging.appendUpdatesToCurrentStatus = function (updates)
{
    // If we have updates add them to the current status
    var number_of_updates = 0;
    if(updates != undefined && (number_of_updates = updates.length))
    {
        for (var i = 0; i< number_of_updates; i++)
        {
            var msg = updates[0].result_text;
            this.appendTextToCurrentStatus(msg);
            updates.shift();
        }
    }
}

com_EasyStaging.hilightStatusMessages = function ()
{
    document.id('currentStatus').addClass('payattention');
}

com_EasyStaging.runEnded = function (successfullRun)
{
    successfullRun = typeof(successfullRun) !== 'undefined' ? successfullRun : true;
    clearInterval(this.responseTimer);
    if (successfullRun)
    {
        var theMsg = '<strong>' + Joomla.JText._('COM_EASYSTAGING_JS_PLAN_RUN_COMPLETED') + '</strong>';
        com_EasyStaging.runStage = theMsg;
        this.appendTextToCurrentStatus(theMsg,true);
        this.setLastRunStatus();
    }

    this.notWaiting();
    this.enableBtns();
}
    /*
     Old 1.0 series functions follow
     */

com_EasyStaging.setUp = function ()
{
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
    this.tablesHidden          = 0;

	if (cppl_tools.getID() == 0)
	{
        this.lockOutBtns(false);
    }
};

/* Feedback Section */
com_EasyStaging.checkJMessage = function(response)
{
    var actionStage = this.last_action;

    if(cppl_tools.typeof(response.running) == 'array')
    {
        var runningStep = response.running[0];
        if(cppl_tools.typeof(runningStep) == 'object')
        {
            if(cppl_tools.typeof(runningStep.action_type) != 'undefined')
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

}

com_EasyStaging.updateLastRunStatus = function (updateText)
{
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
	append     = typeof(append) !== 'undefined' ? append : true;
	var jmsgs = [this.runStage];

	for (i=0;i<=this.lastRunStatus.length;i=i+1)
	{
		jmsgs.push(this.lastRunStatus.shift());
	}
	Joomla.renderMessages({'message': jmsgs });
};

com_EasyStaging.appendTextToCurrentStatus  = function (text, append, precedeWith)
{
	append      = typeof(append) !== 'undefined' ? append : true;
    precedeWith = typeof(precedeWith) !== 'undefined' ? precedeWith : '<br />';
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
	el = typeof(el) !== 'undefined' ? el : 'lastRunStatus';
	$(el).addClass('waiting');
};

com_EasyStaging.notWaiting = function (el)
{
	el = typeof(el) !== 'undefined' ? el : 'lastRunStatus';
	$(el).removeClass('waiting');
    this.updateLastResponse();
	this.last_notification = this.last_response;
};

com_EasyStaging.updateLastResponse = function ()
{
    var nowDateObj = new Date();
    this.last_response = nowDateObj.getTime();
}

com_EasyStaging.lockOutBtns = function (TabsToo)
{
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

com_EasyStaging.enableBtns = function (TabsToo)
{
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

com_EasyStaging.disableToolbarBtns = function ()
{
    // Disable Toolbar CSS
    $('toolbar').addClass('tb-off');
    var tbhref = $$('div#toolbar li.button a.toolbar');
    tbhref.addClass('tb-off');
    $$('div#toolbar li.button a.toolbar span').addClass('tb-off')

    // Store current onclick
    tbhref.each(function(ahref, index)
    {
        com_EasyStaging.toolbarClickEvents.push(ahref.onclick);
    })
    // Disable current onclick
    tbhref.each(function(ahref, index)
    {
        ahref.onclick = function()
        {
            return false;
        }
    })
}

com_EasyStaging.enableToolbarBtns = function ()
{
    // Enable Toolbar CSS
    $('toolbar').removeClass('tb-off');
    $$('div#toolbar li.button a.toolbar span').removeClass('tb-off')
    var tbhref = $$('div#toolbar li.button a.toolbar');
    tbhref.removeClass('tb-off');

    if(this.toolbarClickEvents.length)
    {
    // Restore click function
        tbhref.each(function(ahref, index)
        {
            ahref.onclick = com_EasyStaging.toolbarClickEvents.shift();
        })
    }
}

com_EasyStaging.SelectText = function (objId)
{
    this.fnDeSelectText();
    if (document.selection) {
        var range = document.body.createTextRange();
        range.moveToElementText(document.id(objId));
        range.select();
    }
    else if (window.getSelection) {
        var range = document.createRange();
        range.selectNode(document.id(objId));
        window.getSelection().addRange(range);
    }
}

com_EasyStaging.fnDeSelectText = function ()
{
    if (document.selection)
    {
        document.selection.empty();
    }
    else if (window.getSelection)
    {
        window.getSelection().removeAllRanges();
    }
}

com_EasyStaging.filterTables = function ()
{
    var tableRows = $$('tr.table-settings');
    this.tableFilter = Array.clone(arguments);
    this.tablesHidden = 0;

    // Update each row
    tableRows.each(function(row, index)
        {
            var theSelectValue = row.children[2].children[0].value;
            var inFilter = this.tableFilter.indexOf(parseInt(theSelectValue));

            if((inFilter >= 0) || (this.tableFilter.length ==0))
            {
                row.removeClass('hidden');
            }
            else
            {
                row.addClass('hidden');
                this.tablesHidden++;
            }

            // Notify user of changes
            var visibleTables = this.totalTables - this.tablesHidden;
            jmsgs = [cppl_tools.sprintf(Joomla.JText._('COM_EASYSTAGING_JS_FILTER_RESULTS'), visibleTables, this.totalTables, this.tablesHidden)];
            Joomla.renderMessages({'message': jmsgs });
        }, com_EasyStaging
    );
}
