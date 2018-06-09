/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

(function (kdt) {
    "use strict";

    /**
     * Register the frontend functions.
     *
     * @event onDocumentReady
     *   All events are getting registered as soon as the
     *   document is complete.
     */
    document.addEventListener("DOMContentLoaded", function () {
        krexx.onDocumentReady();
    });

    /**
     * kreXX JS Class.
     *
     * @namespace krexx
     *   It a just a collection of used js routines.
     */
    function krexx() {}

    /**
     * Executed on document ready
     */
    krexx.onDocumentReady = function () {
        // Init our kdt lib before usage.
        kdt.initialize(krexx);

        // In case we are handling a broken html structure, we must move everything
        // to the bottom.
        kdt.moveToBottom('.kouterwrapper');

        // Get viewport height to set kreXX data payload to max 75% for debug.
        // The payload for the fatal error handler is set to the remaining space.
        krexx.setPayloadMaxHeight();

        // Initialize the draggable.
        kdt.draXX('.kwrapper', '.khandle', function (){},function (){});

        /**
         * Register toggling to the elements.
         *
         * @event click
         *   Expands a krexx node when it is not expanded.
         *   When it is already expanded, it closes it.
         */
        kdt.addEvent('.kwrapper .kexpand', 'click', krexx.toggle);

        /**
         * Add the additional data to the footer.
         *
         * @event click
         */
        kdt.addEvent('.kwrapper .kel', 'click', krexx.setAdditionalData);

        /**
         * Register the Collapse-All functions on it's symbol
         *
         * @event click
         */
        kdt.addEvent('.kwrapper .kolps', 'click', kdt.collapse);

        /**
         * Register the code generator on the P symbol.
         *
         * @event click
         */
        kdt.addEvent('.kwrapper .kgencode', 'click', krexx.generateCode);

        /**
         * Prevents the click-event-bubbling on the generated code.
         *
         * @event click
         */
        kdt.addEvent('.kodsp', 'click', kdt.preventBubble);

        /**
         * Register krexx close button function.
         *
         * @event click
         *   Displays a closing animation of the corresponding
         *   krexx output "window" and then removes it from the markup.
         */
        kdt.addEvent('.kwrapper .ktool-tabs .kclose, .kwrapper .kheadnote-wrapper .kclose', 'click', krexx.close);

        /**
         * Register the click on the tabs.
         *
         * @event click
         */
        kdt.addEvent('.ktool-tabs .ktab:not(.ksearchbutton)', 'click', krexx.switchTab);

        /**
         * Register functions for the local dev-settings.
         *
         * @event change
         *   Changes on the krexx html forms.
         *   All changes will automatically be written to the browser cookies.
         */
        kdt.addEvent('.kwrapper .keditable select, .kwrapper .keditable input:not(.ksearchfield)', 'change', kdt.setSetting);

        /**
         * Register cookie reset function on the reset button.
         *
         * @event click
         *   Resets the local settings in the settings cookie,
         *   when the reset button ic clicked.
         */
        kdt.addEvent('.kwrapper .resetbutton', 'click', kdt.resetSetting);

        /**
         * Register the recursions resolving.
         *
         * @event click
         *   When a recursion is clicked, krexx tries to locate the
         *   first output of the object and highlight it.
         */
        kdt.addEvent('.kwrapper .kcopyFrom', 'click', krexx.copyFrom);

        /**
         * Register the displaying of the search menu
         *
         * @event click
         *   When the button is clicked, krexx will display the
         *   search menu associated this the same output window.
         */
        kdt.addEvent('.kwrapper .ksearchbutton, .kwrapper .ksearch .kclose', 'click', krexx.displaySearch);

        /**
         * Register the search event on the next button.
         *
         * @event click
         *   When the button is clicked, krexx will start searching.
         */
        kdt.addEvent('.kwrapper .ksearchnow', 'click', krexx.performSearch);

        /**
         * Listens for a <RETURN> in the search field.
         *
         * @event keyup
         *   A <RETURN> will initiate the search.
         */
        kdt.addEvent('.kwrapper .ksearchfield', 'keyup', krexx.searchfieldReturn);

        /**
         * Always check if the searchfield is inside the viewport, in case we
         * display the fatal error handler
         */
        kdt.addEvent('.kfatalwrapper-outer', 'scroll', krexx.checkSeachInViewport);

        /**
         * Clear our search results, because we now have new options.
         *
         * @event change
         */
        kdt.addEvent('.ksearchcase', 'change', krexx.performSearch.clearSearch);

        /**
         * Clear our search results, because we now have new options.
         *
         * @event change
         */
        kdt.addEvent('.ksearchkeys', 'change', krexx.performSearch.clearSearch);

        /**
         * Clear our search results, because we now have new options.
         *
         * @event change
         */
        kdt.addEvent('.ksearchshort', 'change', krexx.performSearch.clearSearch);

        /**
         * Clear our search results, because we now have new options.
         *
         * @event change
         */
        kdt.addEvent('.ksearchlong', 'change', krexx.performSearch.clearSearch);

        /**
         * Clear our search results, because we now have new options.
         *
         * @event change
         */
        kdt.addEvent('.ksearchwhole', 'change', krexx.performSearch.clearSearch);

        /**
         * Display our search options.
         *
         * @event click
         */
        kdt.addEvent('.koptions', 'click', krexx.displaySearchOptions);

        // Expand the configuration info, we have enough space here!
        krexx.expandConfig();

        // Disable form-buttons in case a logfile is opened local.
        if (window.location.protocol === 'file:') {
            krexx.disableForms();
        }

        // Move the output into the viewport. Debugging onepager is so annoying, otherwise.
        kdt.moveToViewport('.kouterwrapper');

        // Register the click handler on all kreXX instances.
        kdt.clickHandler.register('.kwrapper.kouterwrapper, .kfatalwrapper-outer');
    };

    /**
     * When clicked on s recursion, this function will
     * copy the original analysis result there and delete
     * the recursion.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    krexx.copyFrom = function (event, element) {

        var i;

        // Get the DOM id of the original analysis.
        var domid = kdt.getDataset(element, 'domid');
        // Get the analysis data.
        var orgNest = document.querySelector('#' + domid);

        // Does the element exist?
        if (orgNest) {
            // Get the EL of the data (element with the arrow).
            var orgEl = orgNest.previousElementSibling;
            // Clone the analysis data and insert it after the recursion EL.
            element.parentNode.insertBefore(orgNest.cloneNode(true), element.nextSibling);
            // Clone the EL of the analysis data and insert it after the recursion EL.
            var newEl = orgEl.cloneNode(true);
            element.parentNode.insertBefore(newEl, element.nextSibling);

            // Change the key of the just cloned EL to the one from the recursion.
            kdt.findInDomlistByClass(newEl.children, 'kname').innerHTML = kdt.findInDomlistByClass(element.children, 'kname').innerHTML;
            // We  need to remove the ids from the copy to avoid double ids.
            var allChildren = newEl.nextElementSibling.getElementsByTagName("*");
            for (i = 0; i < allChildren.length; i++) {
                allChildren[i].removeAttribute('id');
            }
            newEl.nextElementSibling.removeAttribute('id');

            // Now we add the dom-id to the clone, as a data-field. this way we can
            // make sure to always produce the right path to this value during source
            // generation.
            kdt.setDataset(newEl.parentNode, 'domid', domid);

            // Get the json info data of the recursion. We save some data there, in case
            // we are resolving a getter.
            var recursionJson = kdt.getDataset(element, 'addjson', false);
            recursionJson = kdt.parseJson(recursionJson);
            if (typeof recursionJson !== 'object') {
               recursionJson = {};
            }
            // We need to merge the original json data with the recusion json data.
            var orgJson = kdt.getDataset(orgEl, 'addjson', false);
            orgJson = kdt.parseJson(orgJson);
            if (typeof orgJson !== 'object') {
               orgJson = {};
            }
            kdt.setDataset(newEl, 'addjson', JSON.stringify(kdt.simpleMerge(orgJson, recursionJson)));

            // Remove the recursion EL.
            element.parentNode.removeChild(element);
        }

    };

    /**
     * Initiates the search.
     *
     * The results are saved in the var results.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    krexx.performSearch = function (event, element) {

        // Hide the search options.
        kdt.addClass([element.parentNode.nextElementSibling], 'khidden');

        // Stitching together our configuration.
        var searchtext = element.parentNode.querySelector('.ksearchfield').value;
        var caseSensitive = element.parentNode.parentNode.querySelector('.ksearchcase').checked;
        var searchKeys = element.parentNode.parentNode.querySelector('.ksearchkeys').checked;
        var searchShort = element.parentNode.parentNode.querySelector('.ksearchshort').checked;
        var searchLong = element.parentNode.parentNode.querySelector('.ksearchlong').checked;
        var searchWhole = element.parentNode.parentNode.querySelector('.ksearchwhole').checked;

        // Apply our configuration.
        if (caseSensitive === false) {
            searchtext = searchtext.toLowerCase();
        }

        // Nothing to search for.
        if (searchtext.length === 0) {
            // Not enough chars as a searchtext!
            element.parentNode.querySelector('.ksearch-state').textContent = '<- Please enter a search text.';
            return
        }

        // We only search for more than 3 chars.
        if (searchtext.length > 2 || searchWhole) {
            var instance = kdt.getDataset(element, 'instance');
            var direction = kdt.getDataset(element, 'direction');
            var payload = document.querySelector('#' + instance + ' .kpayload:not(.khidden)');

            // We need to un-collapse everything, in case it it collapsed.
            var collapsed = payload.querySelectorAll('.kcollapsed');
            for (var i = 0; i < collapsed.length; i++) {
                kdt.trigger(collapsed[i], 'click');
            }

            // Are we already having some results?
            if (typeof krexx.performSearch.results[instance] !== "undefined") {
                if (typeof krexx.performSearch.results[instance][searchtext] === "undefined") {
                    refreshResultlist();
                }
            } else {
                refreshResultlist();
            }

            // Set the pointer to the next or previous element
            if (direction === 'forward') {
                krexx.performSearch.results[instance][searchtext]['pointer']++;
            }
            else {
                krexx.performSearch.results[instance][searchtext]['pointer']--;
            }

            // Do we have an element?
            if (typeof krexx.performSearch.results[instance][searchtext]['data'][krexx.performSearch.results[instance][searchtext]['pointer']] === "undefined") {
                if (direction === 'forward') {
                    // There is no next element, we go back to the first one.
                    krexx.performSearch.results[instance][searchtext]['pointer'] = 0;
                }
                else {
                    krexx.performSearch.results[instance][searchtext]['pointer'] = krexx.performSearch.results[instance][searchtext]['data'].length - 1;
                }
            }

            // Feedback about where we are
            element.parentNode.querySelector('.ksearch-state').textContent = (krexx.performSearch.results[instance][searchtext]['pointer'] + 1) + ' / ' + (krexx.performSearch.results[instance][searchtext]['data'].length);
            // Now we simply jump to the element in the array.
            if (typeof krexx.performSearch.results[instance][searchtext]['data'][krexx.performSearch.results[instance][searchtext]['pointer']] !== 'undefined') {
                // We got another one!
                krexx.jumpTo(krexx.performSearch.results[instance][searchtext]['data'][krexx.performSearch.results[instance][searchtext]['pointer']]);
            }
        }
        else {
            // Not enough chars as a searchtext!
            element.parentNode.querySelector('.ksearch-state').textContent = '<- must be bigger than 3 characters';
        }

        /**
         * Resets our searchlist and fills it with results.
         */
        function refreshResultlist() {
            // Remove all previous highlights
            kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');

            // Apply our configuration.
            var selector = [];
            if (searchKeys === true) {
                selector.push('li.kchild span.kname');
            }
            if (searchShort === true) {
                selector.push('li.kchild span.kshort')
            }
            if (searchLong === true) {
                selector.push('li div.kpreview');
            }

            // Get a new list of elements
            krexx.performSearch.results[instance] = [];
            krexx.performSearch.results[instance][searchtext] = [];
            krexx.performSearch.results[instance][searchtext]['data'] = [];
            krexx.performSearch.results[instance][searchtext]['pointer'] = [];

            // Poll out payload for elements to search
            var list = [];
            if (selector.length > 0) {
                list = payload.querySelectorAll(selector.join(', '));
            }

            var textContent = '';
            for (var i = 0; i < list.length; ++i) {
                // Does it contain our search string?
                textContent = list[i].textContent;
                if (caseSensitive === false) {
                    textContent = textContent.toLowerCase();
                }
                if (searchWhole) {
                    if (textContent === searchtext) {
                        kdt.toggleClass(list[i], 'ksearch-found-highlight');
                        krexx.performSearch.results[instance][searchtext]['data'].push(list[i]);
                    }
                } else {
                    if (textContent.indexOf(searchtext) > -1) {
                        kdt.toggleClass(list[i], 'ksearch-found-highlight');
                        krexx.performSearch.results[instance][searchtext]['data'].push(list[i]);
                    }
                }
            }
            // Reset our index.
            krexx.performSearch.results[instance][searchtext]['pointer'] = -1;
        }
    };

    /**
     * Here we save the search results
     *
     * This is multidimensional array:
     * results[kreXX-instance][search text][search results]
     *                                     [pointer]
     * The [pointer] is the key of the [search result] where
     * you would jump to when you click "next"
     *
     */
    krexx.performSearch.results = [];

    /**
     * Reset the searchresults, because we now have new search options.
     */
    krexx.performSearch.clearSearch = function () {
        // Wipe our instance data, nothing more
        krexx.performSearch.results[kdt.getDataset(this, 'instance')] = [];
    };

    /**
     * Display the search dialog
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    krexx.displaySearch = function (event, element) {

        var instance = kdt.getDataset(element.parentNode, 'instance');
        var search = document.querySelector('#search-' + instance);
        var searchtab = document.querySelector('#' + instance + ' .ksearchbutton');

        // Toggle display / hidden.
        if (kdt.hasClass(search, 'khidden')) {
            // Display it.
            kdt.toggleClass(search, 'khidden');
            kdt.toggleClass(searchtab, 'kactive');
            search.querySelector('.ksearchfield').focus();
        }
        else {
            // Hide it.
            kdt.toggleClass(search, 'khidden');
            kdt.toggleClass(searchtab, 'kactive');
            // Clear the results.
            kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
        }
    };

    /**
     * Toggle the display of the search options.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    krexx.displaySearchOptions = function (event, element) {
        // Get the options and switch the display class.
        kdt.toggleClass(element.parentNode.nextElementSibling, 'khidden');
    };

    /**
     * Hides or displays the nest under an expandable element.
     *
     * @param {Event} event
     *   The event object
     * @param {Node} element
     *   The element thet was clicked.
     */
    krexx.toggle = function (event, element) {
        kdt.toggleClass(element, 'kopened');
        kdt.toggleClass(element.nextElementSibling, 'khidden');

    };

    /**
     * Here we store our jump-to-scroll-animation interval.
     */
    var interval;

    /**
     * "Jumps" to an element in the markup and highlights it.
     *
     * It is used when we are facing a recursion in our analysis.
     *
     * @param {Element} el
     *   The element you want to focus on.
     */
    krexx.jumpTo = function (el) {

        var nests = kdt.getParents(el, '.knest');
        var container;

        // Show them.
        kdt.removeClass(nests, 'khidden');
        // We need to expand them all.
        for (var i = 0; i < nests.length; i++) {
            kdt.addClass([nests[i].previousElementSibling], 'kopened');
        }

        // Remove old highlighting.
        kdt.removeClass('.highlight-jumpto', 'highlight-jumpto');
        // Highlight new one.
        kdt.addClass([el], 'highlight-jumpto');

        // Getting our scroll container
        container = kdt.getParents(el, '.kpayload');

        container.push(document.querySelector('.kfatalwrapper-outer'));
        if (container.length > 0) {
            // We need to find out in which direction we must go.
            // We also must determine the speed we want to travel.
            var step;
            var destination = el.getBoundingClientRect().top - container[0].getBoundingClientRect().top + container[0].scrollTop - 50;
            var diff = Math.abs(container[0].scrollTop - destination);
            if (container[0].scrollTop < destination) {
                // Forward.
                step = Math.round(diff / 12);
            }
            else {
                // Backward.
                step = Math.round(diff / 12) * -1;
            }

            // We stop scrolling, since we have a new target;
            clearInterval(interval);
            // We also need to check if the setting of the new valkue was successful.
            var lastValue = container[0].scrollTop;
            interval = setInterval(function () {
                container[0].scrollTop += step;
                if (Math.abs(container[0].scrollTop - destination) <= Math.abs(step) || container[0].scrollTop === lastValue) {
                    // We are here now, the next step would take us too far.
                    // So we jump there right now and then clear the interval.
                    container[0].scrollTop = destination;
                    clearInterval(interval);
                }
                lastValue = container[0].scrollTop;
            }, 1);
        }
    };

    /**
     * Shows a "fast" closing animation and then removes the krexx window from the markup.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    krexx.close = function (event, element) {

        var instance = kdt.getDataset(element, 'instance');
        var elInstance = document.querySelector('#' + instance);

        // Remove it nice and "slow".
        var opacity = 1;
        var interval = setInterval(function () {
            if (opacity < 0) {
                // It's invisible now, so we clear the timer and remove it from the DOM.
                clearInterval(interval);
                elInstance.parentNode.removeChild(elInstance);
                return;
            }
            opacity -= 0.1;
            elInstance.style.opacity = opacity;
        }, 20);
    };

    /**
     * Disables the editing functions, when a krexx output is loaded as a file.
     *
     * These local settings would actually do
     * nothing at all, because they would land inside a cookie
     * for that file, and not for the server.
     */
    krexx.disableForms = function () {
        var elements = document.querySelectorAll('.kwrapper .kconfiguration .keditable input , .kwrapper .kconfiguration .keditable select');
        for (var i = 0; i < elements.length; i++) {
            elements[i].disabled = true;
        }
    };

    /**
     * The kreXX code generator.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    krexx.generateCode = function (event, element) {

        // We don't want to bubble the click any further.
        event.stop = true;

        var codedisplay = element.nextElementSibling;
        var resultArray = [];
        var resultString = '';
        var sourcedata;
        var domid;
        var wrapperLeft = '';
        var wrapperRight = '';

        // Get the first element
        var el = kdt.getParents(element, 'li.kchild')[0];

        // Start the loop to collect all the date
        while (el) {

            // Get the domid
            domid = kdt.getDataset(el, 'domid');
            sourcedata = kdt.getDataset(el, 'source');
            wrapperLeft = kdt.getDataset(el, 'codewrapperLeft');
            wrapperRight = kdt.getDataset(el, 'codewrapperRight');

            if (sourcedata === '. . .') {
                if (domid !== '') {
                    // We need to get a new el, because we are facing a recursion, and the
                    // current path is not really reachable.
                    el = document.querySelector('#' + domid).parentNode;
                    // Get the source, again.
                    resultArray.push(kdt.getDataset(el, 'source'));
                }
            }
            if (sourcedata !== '') {
                resultArray.push(sourcedata);
            }
            // Get the next el.
            el = kdt.getParents(el, 'li.kchild')[0];
        }
        // Now we reverse our result, so that we can resolve it from the beginning.
        resultArray.reverse();

        for (var i = 0; i < resultArray.length; i++) {
            // We must check if our value is actually reachable.
                // '. . .' means it is not reachable,
                // we will stop right here and display a comment stating this.
            if (resultArray[i] === '. . .') {
                resultString = '// Value is either protected or private.<br /> // Sorry . . ';
                break;
            }

            // Check if we are facing a ;stop; instruction
            if (resultArray[i] === ';stop;') {
                resultString = '';
                resultArray[i] = '';
            }

            // We're good, value can be reached!
            if (resultArray[i].indexOf(';firstMarker;') !== -1) {
                // We add our result sofar into the "source template"
                resultString = resultArray[i].replace(';firstMarker;', resultString);
            } else {
                // Normal concatenation.
                resultString = resultString + resultArray[i];
            }
        }

        // Add the wrapper that we collected so far
        resultString = wrapperLeft + resultString + wrapperRight;

        // Add the text
        codedisplay.innerHTML = '<div class="kcode-inner">' + resultString + '</div>';
        if (codedisplay.style.display === 'none') {
            codedisplay.style.display = '';
            kdt.selectText(codedisplay);
        }
        else {
            codedisplay.style.display = 'none';
        }
    };

    /**
     * Sets the kactive on the clicked element and removes it from the others.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    krexx.switchTab = function (event, element) {

        var instance = kdt.getDataset(element.parentNode, 'instance');
        var what = kdt.getDataset(element, 'what');

        // Toggle the highlighting.
        kdt.removeClass('#' + instance + ' .kactive:not(.ksearchbutton)', 'kactive');

        if (element.classList) {
            element.classList.add('kactive');
        }
        else {
            element.className += ' kactive';
        }

        // Toggle what is displayed
        kdt.addClass('#' + instance + ' .kpayload', 'khidden');
        kdt.removeClass('#' + instance + ' .' + what, 'khidden');
    };

    /**
     * Sets the max-height on the payload elements, depending on the viewport.
     */
    krexx.setPayloadMaxHeight = function () {
        // Get the height.
        var height = Math.round(Math.min(document.documentElement.clientHeight, window.innerHeight || 0) * 0.70);
        var elements;
        var i;

        if (height > 350) {
            // For the debug display
            elements = document.querySelectorAll('.krela-wrapper .kpayload');
            for (i = 0; i < elements.length; i++) {
                elements[i].style.maxHeight = height + 'px';
            }
        }

        // For the fatal error handler.
        elements = document.querySelectorAll('.kfatalwrapper-outer .kpayload');
        if (elements.length > 0) {
            var header = document.querySelector('.kfatalwrapper-outer ul.knode.kfirst').offsetHeight;
            var footer = document.querySelector('.kfatalwrapper-outer .kinfo-wrapper').offsetHeight;
            var handler = document.querySelector('.kfatalwrapper-outer').offsetHeight;
            // This sets the max payload height to the remaining height of the window,
            // sending the footer straight to the bottom of the viewport.
            height = handler - header - footer - 17;
            if (height > 350) {
                for (i = 0; i < elements.length; i++) {
                    elements[i].style.maxHeight = height + 'px';
                }
            }
        }

    };

    /**
     * Displays the additional data and marks the row that is displayed.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    krexx.setAdditionalData = function (event, element) {

        // When dealing with 400MB output, or more, this one takes more time than anything else.
        // We will delay it, so that is does not slow down other stuff.
        setTimeout(function() {
            var wrapper = kdt.getParents(element, '.kwrapper')[0];
            if (typeof wrapper === 'undefined') {
                // This only happens, when we are facing a recursion. There is no
                // additional json data, anyway.
                return;
            }

            var body = wrapper.querySelector('.kdatabody');
            var html = '';
            var counter = 0;
            var regex = /\\u([\d\w]{4})/gi;

            // Mark the clicked el, clear the others.
            kdt.removeClass(wrapper.querySelectorAll('.kcurrent-additional'), 'kcurrent-additional');
            kdt.addClass([element], 'kcurrent-additional');

            // Load the Json.
            var json = kdt.getDataset(element, 'addjson', false);
            json = kdt.parseJson(json);

            if (typeof json === 'object') {
                // We've got data!
                for (var prop in json) {
                    if (json[prop].length > 0) {
                        json[prop] = json[prop].replace(regex, function (match, grp) {
                            return String.fromCharCode(parseInt(grp, 16));
                        });
                        json[prop] = decodeURI(json[prop]);
                        html += '<tr><td>' + prop + '</td><td>' + json[prop] + '</td></tr>';
                        counter++;
                    }
                }
            }
            if (counter === 0) {
                // We have no data. Tell the user that there is nothing to see.
                html = '<tr><td>No data available for this item.</td><td>Sorry.</td></tr>';
            }

            // Add it to the DOM.
            html = '<table><caption class="kheadline">Additional data</caption><tbody class="kdatabody">' + html + '</tbody></table>';
            // Meh, IE9 does not allow me to edit the contents of a table. I have to
            // redraw the whole thing.  :-(
            body.parentNode.parentNode.innerHTML = html;

            // Since the additional data table might now be larger or smaller than,
            // we need to recalculate the height of the payload.
            krexx.setPayloadMaxHeight();

        }, 100);


    };

    /**
     * Checks if the search form is inside the viewport. If not, fixes it on top.
     * Gets triggered on,y when scolling the fatal error handler.
     */
    krexx.checkSeachInViewport = function () {
        // Get the search
        var search = document.querySelector('.kfatalwrapper-outer .search-wrapper');
        // Reset the inline styles
        search.style.position = '';
        search.style.top = '';

        // Measure it!
        var rect = search.getBoundingClientRect();
        if (rect.top < 0) {
            // Set it to the top
            search.style.position = 'fixed';
            search.style.top = '0px';
        }
    };

    /**
     * Listens for a <RETURN> in the search field.
     *
     * @param {Event} event
     * @event keyUp
     */
    krexx.searchfieldReturn = function (event) {
        // Prevents the default event behavior (ie: click).
        event.preventDefault();
        // Prevents the event from propagating (ie: "bubbling").
        event.stopPropagation();

        // If this is no <RETURN> key, do nothing.
        if (event.which !== 13) {
            return;
        }

        kdt.trigger(this.parentNode.querySelectorAll('.ksearchnow')[1], 'click');
    };

    /**
     * Expands the display of the configuration.
     */
    krexx.expandConfig = function () {
        // Get all configurations
        var configs = document.querySelectorAll('.kconfiguration');
        var elements;

        // Get the second child of every configuration.
        for (var i = 0; i < configs.length; i++) {
            elements = configs[i].querySelectorAll('.kchild .kexpand');
            // We chose the first one.
            kdt.toggleClass(elements[0], 'kopened');
            kdt.toggleClass(elements[0].nextElementSibling, 'khidden');

        }
    }

})(kreXXdomTools);
