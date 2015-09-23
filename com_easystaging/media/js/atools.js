/*
 * @package     EasyTable Pro
 * @Copyright   Copyright (C) 2012 Craig Phillips Pty Ltd.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @author      Craig Phillips {@link http://www.seepeoplesoftware.com}
*/

// Only define cppl_tools if it doesn't exist.
if (typeof(cppl_tools) === 'undefined') {
	var cppl_tools = {};
	cppl_tools.requestData  = {};
}

cppl_tools.setUp = function (option)
{
    "use strict";
    this.option                  = option;
	this.token                   = this.getToken();
	this.requestData[this.token] = 1;
	this.requestData.id          = this.getID();
	this.jsonRequestObj          = new Request.JSON({
		url:    'index.php?option='+option+'&format=json',
		method: 'get'
	});
};

cppl_tools.getToken = function()
{
    "use strict";
    var theToken;
	var els = document.getElementsByTagName('input');
	for (var i = 0; i < els.length; i++) {
		if ((els[i].type === 'hidden') && (els[i].name.length === 32) && els[i].value === '1') {
			theToken = els[i].name;
		}
	}
	return theToken;
};

cppl_tools.getTokenSegment  = function ()
{
    "use strict";
     if(typeof this.token === 'undefined' || this.token === null)
    {
        this.token = this.getToken();
    }
    var tokenSegment = ('&' + this.token + '=1');
    return tokenSegment;
};


cppl_tools.getID   = function ()
{
    "use strict";
     if ($('id')) {
		return $('id').value;
	} else {
		return null;
	}
};

cppl_tools.compareTwoObjects  = function (firstObj, secondObj)
{
    "use strict";
     firstObj = typeof firstObj !== 'undefined' ? firstObj : false;
    secondObj = typeof secondObj !== 'undefined' ? secondObj : false;
    // Assume success, look for failure
    var compareResult = true;

    if(firstObj && secondObj)
    {
        // Compare our two objects
        if((typeof firstObj === "object") && (typeof secondObj === "object"))
        {
            for (var aProp in firstObj)
            {
                if(secondObj.hasOwnProperty(aProp) && (aProp !== "__proto__") && (typeof firstObj[aProp] !== "function"))
                {
                    var foP = firstObj[aProp];
                    var foPType = this.typeof(foP);
                    var soP = secondObj[aProp];
                    var soPType = this.typeof(soP);
                    if(foPType === soPType)
                    {
                        if(foPType === "object")
                        {
                            if(!this.compareTwoObjects(foP, soP))
                            {
                                // The objects are different
                                compareResult = false;
                                break;
                            }
                        }
                        else if(foPType === "array")
                        {
                           if(!this.compareTwoArrays(foP, soP))
                           {
                               // Ok these arrays are different, that's a fail
                               compareResult = false;
                               break;
                           }
                        }
                        else if(foP !== soP)
                        {
                            // Ok same property different values, that's a fail
                            compareResult = false;
                            break;
                        }
                    }
                    else
                    {
                        // Properties of the same name but different types, thats a fail
                        compareResult = false;
                        break;
                    }
                }
                else
                {
                    // Missing a property that's a fail
                    compareResult = false;
                    break;
                }
            }
        }
        else
        {
            // We only check objects
            compareResult = false;
        }
    }
    else
    {
        // Need two objects to compare ;)
        compareResult = false;
    }

    return compareResult;
};

cppl_tools.getArrayElementsThatMatchString  = function (needle, haystack, matchWhole)
{
    "use strict";
     matchWhole = typeof matchWhole !== 'undefined' ? matchWhole : false;

    var Matches = [];

    for (var i = 0; i < haystack.length; i++)
    {
        var thisElement = haystack[i];
        if((matchWhole && (thisElement === needle)) || (!matchWhole && (thisElement.indexOf(needle) >= 0)))
        {
            Matches.push(thisElement);
        }
    }

    return Matches;
};

cppl_tools.compareTwoArrays  = function (firstArray, secondArray)
{
    "use strict";
     firstArray = typeof firstArray !== 'undefined' ? firstArray : false;
    secondArray = typeof secondArray !== 'undefined' ? secondArray : false;
    // Assume success, look for failure
    var compareResult = true;

    if(firstArray && secondArray)
    {
        // Compare our two objects
        if((this.typeof(firstArray) === "array") && (this.typeof(secondArray) === "array"))
        {
            if(firstArray.length === secondArray.length)
            {
                var faItemType;
                var saItemType;
                var faItem;
                var saItem;
                for (var i = 0; i < firstArray.length; i++)
                {
                    faItemType = this.typeof(firstArray[i]);
                    faItem = firstArray[i];
                    saItem = secondArray[i];

                    if(faItemType !== 'array')
                    {
                        if(faItemType !== 'object')
                        {
                            if(faItem !== saItem)
                            {
                                // Items are not the same
                                compareResult = false;
                                break;
                            }
                        }
                        else if(!this.compareTwoObjects(faItem, saItem))
                        {
                            // Sub-objects are not the same...
                            compareResult = false;
                            break;
                        }
                    }
                    else
                    {
                        if(!this.compareTwoArrays(faItem, saItem))
                        {
                            // These sub-arrays are not equal
                            compareResult = false;
                            break;
                        }
                    }
                }
            }
            else
            {
                // Arrays do not have a matching number of elements, thats a fail
                compareResult = false;
            }
        }
        else
        {
            compareResult = false;
        }
    }
    else
    {
        compareResult = false;
    }

    return compareResult;
};

cppl_tools.typeof  = function (testItem)
{
    "use strict";
     var t = typeof testItem;
    if (t === 'object')
    {
        if (testItem)
        {
            if (Object.prototype.toString.call(testItem) === '[object Array]')
            {
                t = 'array';
            }
        } else {
            t = 'null';
        }
    }
    return t;
};

cppl_tools.disableToolbarBtn = function (toolBarBtn, newToolTipText)
{
    "use strict";
    // Setup the default vars
	var ourBtn = $(toolBarBtn);
	var ourBtnLink = ourBtn.childNodes[1];
	var ourBtnSpan = ourBtnLink.childNodes[1];
	// Check to see if button class is already set to -off
	if (ourBtnSpan.get('class').indexOf('-off') > 0)
	{
		return;
	}
	// Disable the link
	ourBtnLink.removeEvents();
	ourBtnLink.removeAttribute('href');
	ourBtnLink.removeAttribute('rel');
	ourBtnLink.addClass('hasTip');

	// Setup the new tooltip message
	var newTitle = newToolTipText;
	ourBtnLink.set('title', newTitle);
	var ourBtnTips = ourBtnLink.get('title').split('::',2);
	ourBtnLink.store('tip:title', ourBtnTips[0]);
	ourBtnLink.store('tip:text', ourBtnTips[1]);
	// Re-init Tooltips - @todo find a less nuclear way of doing this...
	var JTooltips = new Tips($$('.hasTip'), { maxTitleChars: 50, fixed: false});

	// Change icon
	// This could be a problem if buttons ever end up with multiple classes in different orders.
	var ourBtnSpanClassArray = ourBtnSpan.get('class').split(' ');
	var ourBtnSpanClassOff = ourBtnSpanClassArray[0] + '-off';

	ourBtnSpan.addClass( ourBtnSpanClassOff );
	ourBtnSpan.removeClass( ourBtnSpanClassArray[0] );
};

cppl_tools.addToList = function(theList, itemToAdd)
{
    "use strict";
	var newList = theList.split(', ');
	newList.push(itemToAdd);
	return newList.join(', ');
};

cppl_tools.deleteFromList = function(theList, itemToRemove)
{
    "use strict";
    var originalList = theList.split(', ');
	var newList = [];
	// Remove the matching element from the array
	for(var i=0; i<originalList.length; i++) {
		if (originalList[i] !== itemToRemove)
        {
            newList.push(originalList[i]);
        }
	}
	return newList.join(', ');
};

cppl_tools.makeURLSafe = function(str)
{
    "use strict";
	// Modify it if it's just a number
	if(this.isNumber(str))
	{
		str = 'a' + str;
	}
	var urlSafeStr = str.replace(/\s+/g,"-").replace(/[^A-Za-z0-9\-\%]/g,'').toLowerCase();
    var theAlias;

	if (urlSafeStr === '')
	{
		theAlias = encodeURIComponent(str).toLowerCase();
	} else {
		theAlias = urlSafeStr;
	}
	return theAlias; 
};

cppl_tools.isNumber = function (n) {
    "use strict";
	return !isNaN(parseFloat(n)) && isFinite(n);
};

cppl_tools.addMessages  = function (messages)
{
    "use strict";
     var container = document.id('system-message-container');

    var dl = new Element('dl', {
        id: 'system-message',
        role: 'alert'
    });
    Object.each(messages, function (item, type) {
        var dt = new Element('dt', {
            'class': type,
            html: type
        });
        dt.inject(dl);
        var dd = new Element('dd', {
            'class': type
        });
        dd.addClass('message');
        var list = new Element('ul');

        Array.each(item, function (item, index, object) {
            var li = new Element('li', {
                html: item
            });
            li.inject(list);
        }, this);
        list.inject(dd);
        dd.inject(dl);
    }, this);
    dl.inject(container);
};

cppl_tools.appendMessages = function(messages) {
    if(typeof jQuery === 'undefined')
    {
        var container = document.id('system-message-container');

        var dl = new Element('dl', {
            id: 'system-message',
            role: 'alert'
        });
        Object.each(messages, function (item, type) {
            var dt = new Element('dt', {
                'class': type,
                html: type
            });
            dt.inject(dl);
            var dd = new Element('dd', {
                'class': type
            });
            dd.addClass('message');
            var list = new Element('ul');

            Array.each(item, function (item, index, object) {
                var li = new Element('li', {
                    html: item
                });
                li.inject(list);
            }, this);
            list.inject(dd);
            dd.inject(dl);
        }, this);
        dl.inject(container);

    }
    else
    {
        var $ = jQuery.noConflict(), $container, $div, $h4, $divList, $p;
        $container = $('#system-message-container');

        $.each(messages, function(type, item) {
            $div = $('<div/>', {
                'id' : 'system-message',
                'class' : 'alert alert-' + type
            });
            $container.append($div)

            $h4 = $('<h4/>', {
                'class' : 'alert-heading',
                'text' : Joomla.JText._(type)
            });
            $div.append($h4);

            $divList = $('<div/>');
            $.each(item, function(index, item) {
                $p = $('<p/>', {
                    html : item
                });
                $divList.append($p);
            });
            $div.append($divList);
        });
    }
};



/**
Derived from sprintf() for JavaScript 1.0
https://github.com/alexei/sprintf.js

 Copyright (c) 2007-2013, Alexandru Marasteanu <hello [at) alexei (dot] ro>
 All rights reserved.

 Redistribution and use in source and binary forms, with or without
 modification, are permitted provided that the following conditions are met:
 * Redistributions of source code must retain the above copyright
 notice, this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright
 notice, this list of conditions and the following disclaimer in the
 documentation and/or other materials provided with the distribution.
 * Neither the name of this software nor the names of its contributors may be
 used to endorse or promote products derived from this software without
 specific prior written permission.

 THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 DISCLAIMED. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR
 ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
**/
cppl_tools.sprintf = function() {
    var re = {
        not_string: /[^s]/,
        number: /[def]/,
        text: /^[^\x25]+/,
        modulo: /^\x25{2}/,
        placeholder: /^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/,
        key: /^([a-z_][a-z_\d]*)/i,
        key_access: /^\.([a-z_][a-z_\d]*)/i,
        index_access: /^\[(\d+)\]/,
        sign: /^[\+\-]/
    }

    function sprintf() {
        var key = arguments[0], cache = sprintf.cache
        if (!(cache[key] && cache.hasOwnProperty(key))) {
            cache[key] = sprintf.parse(key)
        }
        return sprintf.format.call(null, cache[key], arguments)
    }

    sprintf.format = function(parse_tree, argv) {
        var cursor = 1, tree_length = parse_tree.length, node_type = "", arg, output = [], i, k, match, pad, pad_character, pad_length, is_positive = true, sign = ""
        for (i = 0; i < tree_length; i++) {
            node_type = get_type(parse_tree[i])
            if (node_type === "string") {
                output[output.length] = parse_tree[i]
            }
            else if (node_type === "array") {
                match = parse_tree[i] // convenience purposes only
                if (match[2]) { // keyword argument
                    arg = argv[cursor]
                    for (k = 0; k < match[2].length; k++) {
                        if (!arg.hasOwnProperty(match[2][k])) {
                            throw new Error(sprintf("[sprintf] property '%s' does not exist", match[2][k]))
                        }
                        arg = arg[match[2][k]]
                    }
                }
                else if (match[1]) { // positional argument (explicit)
                    arg = argv[match[1]]
                }
                else { // positional argument (implicit)
                    arg = argv[cursor++]
                }

                if (get_type(arg) == "function") {
                    arg = arg()
                }

                if (re.not_string.test(match[8]) && (get_type(arg) != "number" && isNaN(arg))) {
                    throw new TypeError(sprintf("[sprintf] expecting number but found %s", get_type(arg)))
                }

                if (re.number.test(match[8])) {
                    is_positive = arg >= 0
                }

                switch (match[8]) {
                    case "b":
                        arg = arg.toString(2)
                        break
                    case "c":
                        arg = String.fromCharCode(arg)
                        break
                    case "d":
                        arg = parseInt(arg, 10)
                        break
                    case "e":
                        arg = match[7] ? arg.toExponential(match[7]) : arg.toExponential()
                        break
                    case "f":
                        arg = match[7] ? parseFloat(arg).toFixed(match[7]) : parseFloat(arg)
                        break
                    case "o":
                        arg = arg.toString(8)
                        break
                    case "s":
                        arg = ((arg = String(arg)) && match[7] ? arg.substring(0, match[7]) : arg)
                        break
                    case "u":
                        arg = arg >>> 0
                        break
                    case "x":
                        arg = arg.toString(16)
                        break
                    case "X":
                        arg = arg.toString(16).toUpperCase()
                        break
                }
                if (!is_positive || (re.number.test(match[8]) && match[3])) {
                    sign = is_positive ? "+" : "-"
                    arg = arg.toString().replace(re.sign, "")
                }
                pad_character = match[4] ? match[4] == "0" ? "0" : match[4].charAt(1) : " "
                pad_length = match[6] - (sign + arg).length
                pad = match[6] ? str_repeat(pad_character, pad_length) : ""
                output[output.length] = match[5] ? sign + arg + pad : (pad_character == 0 ? sign + pad + arg : pad + sign + arg)
            }
        }
        return output.join("")
    }

    sprintf.cache = {}

    sprintf.parse = function(fmt) {
        var _fmt = fmt, match = [], parse_tree = [], arg_names = 0
        while (_fmt) {
            if ((match = re.text.exec(_fmt)) !== null) {
                parse_tree[parse_tree.length] = match[0]
            }
            else if ((match = re.modulo.exec(_fmt)) !== null) {
                parse_tree[parse_tree.length] = "%"
            }
            else if ((match = re.placeholder.exec(_fmt)) !== null) {
                if (match[2]) {
                    arg_names |= 1
                    var field_list = [], replacement_field = match[2], field_match = []
                    if ((field_match = re.key.exec(replacement_field)) !== null) {
                        field_list[field_list.length] = field_match[1]
                        while ((replacement_field = replacement_field.substring(field_match[0].length)) !== "") {
                            if ((field_match = re.key_access.exec(replacement_field)) !== null) {
                                field_list[field_list.length] = field_match[1]
                            }
                            else if ((field_match = re.index_access.exec(replacement_field)) !== null) {
                                field_list[field_list.length] = field_match[1]
                            }
                            else {
                                throw new SyntaxError("[sprintf] failed to parse named argument key")
                            }
                        }
                    }
                    else {
                        throw new SyntaxError("[sprintf] failed to parse named argument key")
                    }
                    match[2] = field_list
                }
                else {
                    arg_names |= 2
                }
                if (arg_names === 3) {
                    throw new Error("[sprintf] mixing positional and named placeholders is not (yet) supported")
                }
                parse_tree[parse_tree.length] = match
            }
            else {
                throw new SyntaxError("[sprintf] unexpected placeholder")
            }
            _fmt = _fmt.substring(match[0].length)
        }
        return parse_tree
    }

    /**
     * helpers
     */
    function get_type(variable) {
        return Object.prototype.toString.call(variable).slice(8, -1).toLowerCase()
    }

    function str_repeat(input, multiplier) {
        return Array(multiplier + 1).join(input)
    }

    // Horrible hack
    return sprintf(arguments[0], arguments[1], arguments[2], arguments[3], arguments[4], arguments[5]);
}
