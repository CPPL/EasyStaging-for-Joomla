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
            $('startFile' ).addEvent('click', function (event) { com_EasyStaging.ajaxCheckIn(event); } );
            $('startDBase').addEvent('click', function (event) { com_EasyStaging.ajaxCheckIn(event); } );
            $('startAll'  ).addEvent('click', function (event) { com_EasyStaging.ajaxCheckIn(event); } );
            $('status'    ).addEvent('click', function (event) { com_EasyStaging.start(event.target.id); } );
            // Just in case we want to copy the status ouput...
            $('currentStatus').addEvent('click',
                function(event) {
                    com_EasyStaging.SelectText('currentStatus');
                    lrs = document.getElementById('lastRunStatus');
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
		Joomla.submitform(task, document.getElementById('easystaging-form'));
	}
	else
	{
		alert(Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED'));
	}
};

 com_EasyStaging.start = function (whatWeWant)
 {
     if(whatWeWant == undefined)
     {
         com_EasyStaging.appendTextToCurrentStatus(cppl_tools.sprintf(Joomla.JText._('COM_EASYSTAGING_JS_MISSING_PARAMETER'), 'start()'));
     }
     else
     {
         com_EasyStaging.appendTextToCurrentStatus('<strong>' + Joomla.JText._('COM_EASYSTAGING_JSON_REQUEST_MADE_PLEASE_WAIT') + '</strong>', false);
         com_EasyStaging.requestData.step = whatWeWant;
         com_EasyStaging.requestData.runticket = '';
         com_EasyStaging.lockOutBtns(true);
         com_EasyStaging.disableToolbarBtns();
         com_EasyStaging.status();
     }
 }

com_EasyStaging.status = function ()
{
    com_EasyStaging.requestData.task = 'plan.status';
    com_EasyStaging.runStage = Joomla.JText._('COM_EASYSTAGING_JS_IN_PROGRESS');
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
    com_EasyStaging.notWaiting();
    if (response.status !== 0)
    {
        switch (response.status)
        {
            // Finished nothing new to report.
            case 1:
                com_EasyStaging.appendTextToCurrentStatus(response.msg);
                com_EasyStaging.runEnded();
                break;
            // Still have steps to process
            case 2:
                if( cppl_tools.compareTwoObjects(this.previousResponse, JSON.parse(JSON.stringify(response))))
                {
                    com_EasyStaging.appendTextToCurrentStatus('.',true,'');
                }
                else
                {
                    this.previousResponse = JSON.parse(JSON.stringify(response));
                    // Only the run creation will return a runticket so we need to keep a copy so we can get run status
                    if(response.runticket != undefined)
                    {
                        com_EasyStaging.requestData.runticket = response.runticket;
                    }
                    com_EasyStaging.appendTextToCurrentStatus(response.msg);
                    com_EasyStaging.appendUpdatesToCurrentStatus(response.updates)
                    if(response.stepsleft != undefined)
                    {
                        var stepsRemaining = response.stepsleft.length;
                        var leftMsg = '';
                        if(response.stepsleft.length == 1)
                        {
                            leftMsg = Joomla.JText._('COM_EASYSTAGING_JS_STEP_LEFT');
                        }
                        else if(stepsRemaining > 1)
                        {
                            leftMsg = cppl_tools.sprintf(Joomla.JText._('COM_EASYSTAGING_JS_STEPS_LEFT'), stepsRemaining);
                        }
                        com_EasyStaging.appendTextToCurrentStatus(leftMsg);
                    }
                }
                com_EasyStaging.statusTimeout = window.setTimeout(com_EasyStaging.status, com_EasyStaging.statusCheckInterval);
                break;
            // Shouldn't happen... unless we're adding new features ;)
            default:
                com_EasyStaging.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JS_ERROR_UNKNOWN_PROC_PATH')+'</span>');
                com_EasyStaging.runEnded(false);
                break;
        }
    }
    else
    {
        com_EasyStaging.appendTextToCurrentStatus(response.error);
        com_EasyStaging.appendTextToCurrentStatus('<span class="es_ajax_error_msg">'+Joomla.JText._('COM_EASYSTAGING_JS_STATUS_CHECK_FAILED')+'</span>');
        com_EasyStaging.runEnded(false);
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
            com_EasyStaging.appendTextToCurrentStatus(msg);
            updates.shift();
        }
    }
}

com_EasyStaging.hilightStatusMessages = function ()
{
    document.getElementById('currentStatus').setStyle('background','#fffea1');
}

com_EasyStaging.runEnded = function (successfullRun)
{
    successfullRun = typeof(successfullRun) !== 'undefined' ? successfullRun : true;
    clearInterval(this.responseTimer);
    if (successfullRun)
    {
        this.appendTextToCurrentStatus('<strong>' + Joomla.JText._('COM_EASYSTAGING_JS_PLAN_RUN_COMPLETED') + '</strong><br />',true);
        this.setLastRunStatus();
        this.currentStatusScroller.toBottom.delay(100,this.currentStatusScroller);
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
	this.table_count           = 0;
	this.tables_proc           = 0;
	this.last_response         = 0;
	this.last_notification     = 0;
	this.runStage              = '';
    this.previousResponse      = null;
	this.lastRunStatus         = [];
	this.currentStatus         = [];
	this.SQLFileLists          = [];
    this.toolbarClickEvents    = [];
	this.requestData           = {};
	var token                  = this.getToken();
	this.requestData[token]    = 1;
    this.requestData.runticket = '';
	this.requestData.plan_id   = this.getID();
	this.jsonURL               = 'index.php?option=com_easystaging&format=json';
	this.jsonRequestObj        = new Request.JSON({
		url:    'index.php?option=com_easystaging&format=json',
		method: 'get'
	});
	
	if (this.getID() == 0)
	{
        this.lockOutBtns(false);
    }
};

{

    {
    }
    {
    }


/* Feedback Section */
com_EasyStaging.updateLastRunStatus = function (updateText)
{
	firstMsg = this.lastRunStatus.shift();
	if (typeof(updateText) !== 'undefined')
	{
		this.lastRunStatus.unshift( updateText );
	}
	this.lastRunStatus.unshift( Joomla.JText._('COM_EASYSTAGING_JS_IN_PROGRESS') + firstMsg );
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

	if (append)
	{
		$('currentStatus').innerHTML = $('currentStatus').innerHTML + precedeWith + text;
	}
	else
	{
		$('currentStatus').innerHTML = text;
	}
//	this.currentStatusScroller.toBottom();
};

com_EasyStaging.appendTimeSince = function ()
{
	var theNowDateObj = new Date();
	var theNowMilliseconds = theNowDateObj.getTime();
	var theDiff = theNowMilliseconds - this.last_response;
	if ((theNowMilliseconds - this.last_notification) >= 500)
	{
		this.lastRunStatus.push('<em>' + (theDiff/1000).round(1) + ' ' + Joomla.JText._('COM_EASYSTAGING_JS_SECONDS_SINCE_LAS_DESC') + '</em>');
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
	var nowDateObj = new Date();
	this.last_response = nowDateObj.getTime();
	this.last_notification = this.last_response;
	this.currentStatusScroller.toBottom();
};

com_EasyStaging.runFinished = function (successfullRun)
{
	successfullRun = typeof(successfullRun) !== 'undefined' ? successfullRun : true;
	clearInterval(this.responseTimer);
	if (successfullRun)
    {
        this.appendTextToCurrentStatus('<strong>' + Joomla.JText._('COM_EASYSTAGING_JS_PLAN_RUN_COMPLETED') + '</strong><br />',true);
        this.setLastRunStatus();
        this.currentStatusScroller.toBottom.delay(100,this.currentStatusScroller);
    }

	this.notWaiting();
	this.enableBtns();

    // Finally set the "last run" timestamp for the Plan and clean up.
	this.requestData.task = 'plan.finishRun';
	var req = new Request.JSON({
		method: 'get',
		url: com_EasyStaging.jsonURL,
		data: com_EasyStaging.requestData,
		onComplete: function (response) {
						com_EasyStaging.lastRunStatus.push(Joomla.JText._('COM_EASYSTAGING_JS_PLAN_RUN_COMPLETED'));
						com_EasyStaging.lastRunStatus.push(response.msg);
						com_EasyStaging.setLastRunStatus();
						com_EasyStaging.appendTextToCurrentStatus(response.cleanupMsg, true);
						Joomla.renderMessages({'message': [ response.msg, response.cleanupMsg ]});
					}
	});
	req.send();
};

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
	this.currentStatusScroller.toBottom.delay(100,this.currentStatusScroller);
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
	this.currentStatusScroller.toBottom.delay(100,this.currentStatusScroller);

    // Enable Toolbar
    this.enableToolbarBtns();
};

com_EasyStaging.disableToolbarBtns = function ()
{
    // Disable Toolbar CSS
    $('toolbar').addClass('tb-off');
    tbhref = $$('div#toolbar li.button a.toolbar');
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
    tbhref = $$('div#toolbar li.button a.toolbar');
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
        range.moveToElementText(document.getElementById(objId));
        range.select();
    }
    else if (window.getSelection) {
        var range = document.createRange();
        range.selectNode(document.getElementById(objId));
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
