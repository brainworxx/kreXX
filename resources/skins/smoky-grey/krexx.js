/**
 * @file
 *   Template js functions for kreXX.
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2015 Brainworxx GmbH
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

(function () {
  "use strict";

  /**
   * Register the frontend functions.
   *
   * @event onDocumentReady
   *   All events are getting registered as soon as the
   *   document is complete.
   */
  document.addEventListener("DOMContentLoaded", function() {
    krexx.onDocumentReady();
  });

  /**
   * kreXX JS Class.
   *
   * @namespace
   *   It a just a collection of used js routines.
   */
  function krexx() {}

  /**
   * Executed on document ready
   *
   * @event documentready
   */
  krexx.onDocumentReady = function () {

    // Initialize the draggable.
    krexx.draXX('.kwrapper',  '.kheadnote');

    /**
     * Register krexx close button function.
     *
     * @event click
     *   Displays a closing animation of the corresponding
     *   krexx output "window" and then removes it from the markup.
     */
    krexx.addEvent('.kwrapper .kheadnote-wrapper .kclose', 'click', krexx.close);

    /**
     * Register toggling to the elements.
     *
     * @event click
     *   Expands a krexx node when it is not expanded.
     *   When it is already expanded, it closes it.
     */
    krexx.addEvent('.kwrapper .kexpand', 'click', krexx.toggle);

    /**
     * Register the click on the tabs.
     *
     * @event click
     */
    krexx.addEvent('.ktool-tabs .ktab:not(.ksearchbutton)', 'click', krexx.switchTab);

    /**
     * Register functions for the local dev-settings.
     *
     * @event change
     *   Changes on the krexx html forms.
     *   All changes will automatically be written to the browser cookies.
     */
    krexx.addEvent('.kwrapper .keditable select, .kwrapper .keditable input:not(.ksearchfield)', 'change', krexx.setSetting);

    /**
     * Register cookie reset function on the reset button.
     *
     * @event click
     *   Resets the local settings in the settings cookie,
     *   when the reset button ic clicked.
     */
    krexx.addEvent('.kwrapper .resetbutton', 'click', krexx.resetSetting);

    /**
     * Register the recursions resolving.
     *
     * @event click
     *   When a recursion is clicked, krexx tries to locate the
     *   first output of the object and highlight it.
     */
    krexx.addEvent('.kwrapper .kcopyFrom', 'click', krexx.copyFrom);

    /**
     * Register the displaying of the search menu
     *
     * @event click
     *   When the button is clicked, krexx will display the
     *   search menu associated this the same output window.
     */
    krexx.addEvent('.kwrapper .ksearchbutton, .kwrapper .ksearch .kclose', 'click', krexx.displaySearch);

    /**
     * Register the search event on the next button.
     *
     * @event click
     *   When the button is clicked, krexx will start searching.
     */
    krexx.addEvent('.kwrapper .ksearchnow', 'click', krexx.performSearch);

    /**
     * Listens for a <RETURN> in the search field.
     *
     * @event keyup
     *   A <RETURN> will initiate the search.
     */
    krexx.addEvent('.kwrapper .ksearchfield', 'keyup', krexx.searchfieldReturn);

    /**
     * Register the Collapse-All funfions on it's symbol
     *
     * @event click
     */
    krexx.addEvent('.kwrapper .kcollapse-me', 'click', krexx.collapse);

    /**
     * Register the code generator on the P symbol.
     *
     * @event click
     */
    krexx.addEvent('.kwrapper .kgencode', 'click', krexx.generateCode);

    /**
     * Add the additional data to the footer.
     *
     * @event click
     */
    krexx.addEvent('.kwrapper .kel', 'click', krexx.setAdditionalData);

    /**
     * Always check if the searchfield is inside the viewport, in case we
     * display the fatal error handler
     *
     */
    krexx.addEvent('.kfatalwrapper-outer', 'scroll', krexx.checkSeachInViewport)

    // Get viewport height to set kreXX data payload to max 75%
    krexx.setPayloadMaxHeight();

    // Expand the configuration info, we have enough space here!
    krexx.expandConfig();

    // Disable form-buttons in case a logfile is opened local.
    if (window.location.protocol === 'file:') {
      krexx.disableForms();
    }
  };

  /**
   * Register our jQuery draggable plugin.
   *
   * @param {string} selector
   *   The selector for the content we want to drag around
   * @param {string} handle
   *   The selector for the handle (the element where you click and pull the
   *   "window".
   */
  krexx.draXX = function (selector, handle) {

    krexx.addEvent(selector + ' ' + handle, 'mousedown', startDraxx);

    /**
     * Starts the dragging on a mousedown.
     *
     * @event  mousedown
     * @param event
     */
    function startDraxx (event) {
      // The selector has an ID, we only have one of them.
      var elContent = document.querySelector(selector);

      var offset = getElementOffset(elContent);

      // Calculate original offset.
      var offSetY = offset.top + elContent.offsetHeight - event.pageY - elContent.offsetHeight;
      var offSetX = offset.left + outerWidth(elContent) - event.pageX - outerWidth(elContent);

      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();

      /**
       * @param {event} event
       *   The mousemove event from the pulling of the handle.
       *
       * @event mousemove
       *   The actual dragging of the handle.
       */
      document.addEventListener("mousemove", drag);

      /**
       * Stops the dragging process
       *
       * @event mouseup
       */
      document.addEventListener("mouseup", function () {
        // Prevents the default event behavior (ie: click).
        event.preventDefault();
        // Prevents the event from propagating (ie: "bubbling").
        event.stopPropagation();
        // Unregister to prevent slowdown.
        document.removeEventListener("mousemove", drag);
      });

      /**
       * Drags the DOM element arround
       *
       * @param event
       */
      function drag(event) {
        // Prevents the default event behavior (ie: click).
        event.preventDefault();
        // Prevents the event from propagating (ie: "bubbling").
        event.stopPropagation();

        var left = event.pageX + offSetX;
        var top = event.pageY + offSetY;

        elContent.style.left = left + "px";
        elContent.style.top = top + "px";
      }
    }

    /**
     * Gets the top and left offset of a DOM element.
     *
     * @param element
     * @returns {{top: number, left: number}}
     */
    function getElementOffset(element) {
      var de = document.documentElement;
      var box = element.getBoundingClientRect();
      var top = box.top + window.pageYOffset - de.clientTop;
      var left = box.left + window.pageXOffset - de.clientLeft;
      return { top: top, left: left };
    }

    /**
     * Gets the outer width of an element.
     *
     * @param el
     * @returns {number}
     */
    function outerWidth(el) {
      var width = el.offsetWidth;
      var style = getComputedStyle(el);
      width += parseInt(style.marginLeft) + parseInt(style.marginRight);
      return width;
    }

  };

  /**
   * When clicked on s recursion, this function will
   * copy the original analysis result there and delete
   * the recursion.
   *
   * @param event
   */
  krexx.copyFrom = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var i;

    // Get the DOM id of the original analysis.
    var domid = krexx.getDataset(this, 'domid');
    // Get the analysis data.
    var orgNest = document.querySelector('#' + domid);

    // Does the element exist?
    if (orgNest) {
      // Get the EL of the data (element with the arrow).
      var orgEl = orgNest.previousElementSibling;
      // Clone the analysis data and insert it after the recursion EL.
      this.parentNode.insertBefore(orgNest.cloneNode(true), this.nextSibling);
      // Clone the EL of the analysis data and insert it after the recursion EL.
      var newEl = orgEl.cloneNode(true);
      this.parentNode.insertBefore(newEl, this.nextSibling);
      // Register the events on the new element.
      newEl.addEventListener('click', krexx.toggle);
      newEl.addEventListener('click', krexx.setAdditionalData);
      newEl.querySelector('.kgencode').addEventListener('click', krexx.generateCode);
      newEl.querySelector('.kcollapse-me').addEventListener('click', krexx.collapse);

      // Register the toggel function.
      var newExpand = newEl.nextElementSibling.querySelectorAll('.kexpand');
      for (i = 0; i < newExpand.length; i++) {
        newExpand[i].addEventListener('click', krexx.toggle);
      }
      // Register the Collapse function.
      var hideEverythingElse = newEl.nextElementSibling.querySelectorAll('.kcollapse-me');
      for (i = 0; i < hideEverythingElse.length; i++) {
        hideEverythingElse[i].addEventListener('click', krexx.collapse);
      }
      // Register the Code-Generation function.
      var codegen = newEl.nextElementSibling.querySelectorAll('.kgencode');
      for (i = 0; i < codegen.length; i++) {
        codegen[i].addEventListener('click', krexx.generateCode);
      }

      // Change the key of the just cloned EL to the one from the recursion.
      krexx.findInDomlistByClass(newEl.children, 'kname').innerHTML = krexx.findInDomlistByClass(this.children, 'kname').innerHTML;
      // We  need to remove the ids from the copy to avoid double ids.
      var allChildren = newEl.nextElementSibling.getElementsByTagName("*");
      for (i = 0; i < allChildren.length; i++) {
        allChildren[i].removeAttribute('id');
      }
      newEl.nextElementSibling.removeAttribute('id');

      // Now we add the dom-id to the clone, as a data-field. this way we can
      // make sure to always produce the right path to this value during source
      // generation.
      krexx.setDataset(newEl.parentNode, 'domid', domid);

      // Remove the recursion EL.
      this.parentNode.removeChild(this);
    }

  };

  /**
   * Collapses elements for a breadcrumb
   *
   * Hides all other elements, except the one with
   * the button. This way, we can get a breadcrumb
   * to the element we want to look at.
   *
   * @param event
   */
  krexx.collapse = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var button = event.target;
    var wrapper = krexx.getParents(button, '.kwrapper')[0];

    // Remove all old classes within this debug "window"
    krexx.removeClass(wrapper.querySelectorAll('.kfilterroot'), 'kfilterroot');
    krexx.removeClass(wrapper.querySelectorAll('.krootline'), 'krootline');
    krexx.removeClass(wrapper.querySelectorAll('.ktopline'), 'ktopline');

    // Here we start the hiding, only when clicked on a
    // none-collapsed button.
    if(!krexx.hasClass(button, 'kcollapsed')) {
      krexx.addClass(krexx.getParents(button, 'div.kbg-wrapper > ul'), 'kfilterroot');
      // Add the "rootline" to all elements between the button and the filterroot
      krexx.addClass(krexx.getParents(button, 'ul.knode, li.kchild'), 'krootline');
      // Add the "topline" to the highest element in the rootline
      krexx.addClass([krexx.getParents(button, '.krootline')[0]], 'ktopline');
      // Reset the old collapse button.
      krexx.removeClass(wrapper.querySelectorAll('.kcollapsed'), 'kcollapsed');

      // Highlight the new collapse button.
      krexx.addClass([button], 'kcollapsed');
    }
    else {
      // Reset the button, since we are un-collapsing nodes here.
      krexx.removeClass('.kcollapsed', 'kcollapsed');
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
   * @var array
   */
  var results = [];

  /**
   * Initiates the search.
   *
   * The results are saved in the var results.
   *
   * @param event
   */
  krexx.performSearch = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var searchtext = this.parentNode.querySelector('.ksearchfield').value;

    // we only search for more than 3 chars.
    if (searchtext.length > 3) {
      var instance = krexx.getDataset(this, 'instance') ;
      var direction = krexx.getDataset(this, 'direction');
      var payload =  document.querySelector('#' + instance + ' .kpayload:not(.khidden)');

      // We need to un-collapse everything, in case it it collapsed.
      var collapsed = payload.querySelectorAll('.kcollapsed');
      for (var i = 0; i < collapsed.length; i++) {
        krexx.trigger(collapsed[i], 'click');
      }

      // Are we already having some results?
      if (typeof results[instance] != "undefined") {
        if (typeof results[instance][searchtext] == "undefined") {
          refreshResultlist();
        }
      }
      else {
        refreshResultlist();
      }
      
      // Set the pointer to the next or previous element
      if (direction == 'forward') {
        results[instance][searchtext]['pointer']++;
      }
      else {
        results[instance][searchtext]['pointer']--;
      }

      // Do we have an element?
      if (typeof results[instance][searchtext]['data'][results[instance][searchtext]['pointer']] == "undefined") {
        if (direction == 'forward') {
          // There is no next element, we go back to the first one.
          results[instance][searchtext]['pointer'] = 0;
        }
        else {
          results[instance][searchtext]['pointer'] = results[instance][searchtext]['data'].length - 1;
        }
      }
      
      // Feedback about where we are
      this.parentNode.querySelector('.ksearch-state').textContent = results[instance][searchtext]['pointer'] + ' / ' + (results[instance][searchtext]['data'].length - 1);
      // Now we simply jump to the element in the array.
      if (typeof results[instance][searchtext]['data'][results[instance][searchtext]['pointer']] !== 'undefined') {
        // We got another one!
        krexx.jumpTo(results[instance][searchtext]['data'][results[instance][searchtext]['pointer']]);
      }
    }
    else {
      // Not enough chars as a searchtext!
      this.parentNode.querySelector('.ksearch-state').textContent = '<- must be bigger than 3 characters';
    }

    /**
     * Resets our searchlist and fills it with results.
     */
    function refreshResultlist() {
      // Remove all previous highlights
      krexx.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
      // Get a new list of elements
      results[instance] = [];
      results[instance][searchtext] = [];
      results[instance][searchtext]['data'] = [];
      // Poll out payload for elements to search
      var list = payload.querySelectorAll("li span, li div.kpreview");
      for (var i = 0; i < list.length; ++i) {
        // Does it contain our search string?
        if (list[i].textContent.indexOf(searchtext) > -1) {
          krexx.toggleClass(list[i], 'ksearch-found-highlight');
          results[instance][searchtext]['data'].push(list[i]);
        }
      }
      // Reset our index.
      results[instance][searchtext]['pointer'] = -1;
    }
    

  };

  /**
   * Display the search dialog
   *
   * @param event
   */
  krexx.displaySearch = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var instance = krexx.getDataset(this.parentNode, 'instance');
    var search = document.querySelector('#search-' + instance);
    var searchtab = document.querySelector('#' + instance + ' .ksearchbutton');

    // Toggle display / hidden.
    if (krexx.hasClass(search, 'khidden')) {
      // Display it.
      krexx.toggleClass(search, 'khidden');
      krexx.toggleClass(searchtab, 'kactive');
      search.querySelector('.ksearchfield').focus();
    }
    else {
      // Hide it.
      krexx.toggleClass(search, 'khidden');
      krexx.toggleClass(searchtab, 'kactive');
      // Clear the results.
      krexx.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight')
      results = [];
    }
  };

  /**
   * Hides or displays the nest under an expandable element.
   *
   * @param event
   */
  krexx.toggle = function (event) {
    // Prevents the default event behavior (ie: click).
    // event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    krexx.toggleClass(this, 'kopened');
    krexx.toggleClass(this.nextElementSibling, 'khidden');

  };

  /**
   * "Jumps" to an element in the markup and highlights it.
   *
   * It is used when we are facing a recursion in our analysis.
   *
   * @param {HTMLElement} el
   *   The element you want to focus on.
   */
  krexx.jumpTo = function (el) {

    var nests = krexx.getParents(el, '.knest');
    var container;

    // Show them.
    krexx.removeClass(nests, 'khidden');
    // We need to expand them all.
    for (var i = 0; i < nests.length; i++) {
      krexx.addClass(nests[i].previousElementSibling, 'kopened')
    }

    // Remove old highlighting.
    krexx.removeClass('.highlight-jumpto', 'highlight-jumpto');
    // Highlight new one.
    krexx.addClass([el], 'highlight-jumpto');

    // Getting our scroll container
    container = krexx.getParents(el, '.kpayload');

    container.push(document.querySelector('.kfatalwrapper-outer'));
    if (container.length > 0) {
      var step;
      var destination = el.getBoundingClientRect().top - container[0].getBoundingClientRect().top + container[0].scrollTop - 50;
      if (container[0].scrollTop < destination) {
        step = 10;
      }
      else {
        step = -10;
      }

      // We stop scrolling, since we have a new target;
      clearInterval(interval);
      // We also need to check if the setting of the new valkue was successful.
      var lastValue = container[0].scrollTop;
      var interval = setInterval(function() {
        container[0].scrollTop +=  step;
        if (Math.abs(container[0].scrollTop - destination) <= Math.abs(step) || container[0].scrollTop == lastValue) {
          // We are here now, the next step would take us too far.
          clearInterval(interval);
        }
        lastValue = container[0].scrollTop;
      }, 1);
    }
  };

  /**
   * Reads the values from a cookie.
   *
   * @param {string} krexxDebugSettings
   *   Name of the cookie.
   *
   * @return string
   *   The value, set in the cookie.
   */
  krexx.readSettings = function (krexxDebugSettings) {
    var cookieName = krexxDebugSettings + "=";
    var cookieArray = document.cookie.split(';');
    var result = {};

    for (var i = 0; i < cookieArray.length; i++) {
      var c = cookieArray[i];
      while (c.charAt(0) === ' ') {
        c = c.substring(1, c.length);
      }
      if (c.indexOf(cookieName) === 0) {
        try {
          // Return json, if possible.
          result = JSON.parse(c.substring(cookieName.length, c.length));
        }
        catch (error) {
          // Return the value.
          result = c.substring(cookieName.length, c.length);
        }
      }
    }
    return result;
  };

  /**
   * Adds the value from a html element to the local cookie settings.
   *
   * @param event
   */
  krexx.setSetting = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    // Get the old value.
    var settings = krexx.readSettings('KrexxDebugSettings');
    // Get new settings from element.
    var newValue = this.value;
    var valueName = this.name;
    settings[valueName] = newValue;

    // Save it.
    var date = new Date();
    date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
    var expires = 'expires=' + date.toUTCString();
    // Remove a possible old value from a previous version.
    document.cookie = 'KrexxDebugSettings=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    // Set the new one.
    document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';
    // Feedback about update.
    alert(valueName + ' --> ' + newValue + '\n\nPlease reload the page to use the new local settings.');
  };

  /**
   * Resets all values in the local cookie settings.
   *
   * @param event
   */
  krexx.resetSetting = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    // We do not delete the cookie, we simply remove all settings in it.
    var settings = {};
    var date = new Date();
    date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
    var expires = 'expires=' + date.toUTCString();
    document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';

    alert('All local configuration have been reset.\n\nPlease reload the page to use the these settings.');
  };

  /**
   * Shows a "fast" closing animation and then removes the krexx window from the markup.
   *
   * @param {HTMLElement} event
   *   The closing button.
   */
  krexx.close = function (event) {

    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var instance = krexx.getDataset(event.target, 'instance');
    var elInstance = document.querySelector('#' + instance);

    // Remove it nice and "slow".
    var opacity = 1;
    var interval = setInterval(function() {
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
    var elements = document.querySelectorAll('.kwrapper .keditable input, .kwrapper .keditable select');
    for (var i = 0; i < elements.length; i++) {
      elements.disabled = true;
    }
  };

  /**
   * The kreXX code generator.
   *
   * @event click
   * @param event
   */
  krexx.generateCode = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var codedisplay = event.target.nextElementSibling;
    var result = '';
    var sourcedata;
    var domid;
    // Get the first element
    var el = krexx.getParents(event.target, 'li.kchild')[0];


    // Start the loop to collect all the date
    while (el) {
      // Get the domid
      domid = krexx.getDataset(el, 'domid');
      sourcedata = krexx.getDataset(el, 'source');

      if (typeof sourcedata !== 'undefined' && sourcedata == '. . .') {
        if (typeof domid !== 'undefined') {
          // We need to get a new el, because we are facing a recursion, and the
          // current path is not really reachable.
          el = document.querySelector('#' + domid).parentNode;
          // Get the source, again.
          sourcedata = krexx.getDataset(el, 'source');
        }
      }

      // Recheck everything.
      if (typeof sourcedata !== 'undefined') {
        // We must check if our value is actually reachable.
        // '. . .' means it is not reachable,
        // we will stop right here and display a comment stating this.
        if (sourcedata == '. . .') {
          result = '// Value is either protected or private.<br /> // Sorry . . ';
          break;
        }
        else {
          // We're good, value can be reached!
          result = sourcedata + result;
        }
      }
      // Get the next el.
      el = krexx.getParents(el, 'li.kchild')[0];
    }

    // 3. Add the text
    codedisplay.innerHTML ='<div class="kcode-inner">' + result + ';</div>';
    if (codedisplay.style.display == 'none') {
      codedisplay.style.display = '';
    }
    else {
      codedisplay.style.display = 'none';
    }
    krexx.selectText(codedisplay);
  };

  /**
   * Selects some text
   *
   * @see http://stackoverflow.com/questions/985272/selecting-text-in-an-element-akin-to-highlighting-with-your-mouse
   * @autor Jason
   *
   * @param element
   * @constructor
   */
  krexx.selectText = function (element) {
    var doc = document
      , text = element
      , range, selection;

    if (doc.body.createTextRange) {
      range = document.body.createTextRange();
      range.moveToElementText(text);
      range.select();
    } else if (window.getSelection) {
      selection = window.getSelection();
      range = document.createRange();
      range.selectNodeContents(text);
      selection.removeAllRanges();
      selection.addRange(range);
    }
  };

  /**
   * Gets the dataset from en element.
   *
   * @param el
   * @param what
   */
  krexx.getDataset = function (el, what) {
    var result;

    if (typeof el !== 'undefined') {
      result = el.getAttribute('data-' + what);

      if (result !== null) {
        return result;
      }
    }

  };

  /**
   * Sets the dataset from en element.
   *
   * @param el
   * @param what
   * @param value
   */
  krexx.setDataset = function (el, what, value) {
    if (typeof el !== 'undefined') {
      el.setAttribute('data-' + what, value);
    }
  };

  /**
   * Sets the kactive on the clicked element and removes it from the others.
   *
   * @even click
   * @param event
   */
  krexx.switchTab = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var instance = krexx.getDataset(this.parentNode, 'instance');
    var what = krexx.getDataset(this, 'what');

    // Toggle the highlighting.
    krexx.removeClass('#' + instance + ' .kactive:not(.ksearchbutton)', 'kactive');

    if (this.classList) {
      this.classList.add('kactive');
    }
    else {
      this.className += ' kactive';
    }

    // Toggle what is displayed
    krexx.addClass('#' + instance + ' .kpayload', 'khidden');
    krexx.removeClass('#' + instance + ' .' + what, 'khidden');
  };

  /**
   * Sets the max-height on the payload elements, depending on the viewport.
   *
   * @event document ready
   */
  krexx.setPayloadMaxHeight = function () {
    // Get the height.
    var height = Math.round(Math.max(document.documentElement.clientHeight, window.innerHeight || 0) * 0.60);

    if (height > 0) {
      var elements = document.querySelectorAll('.krela-wrapper .kpayload');
      for (var i = 0; i < elements.length; i++) {
        elements[i].style.maxHeight = height + 'px';
      }
    }
  };

  /**
   * Displays the additional data and marks the row that is displayed.
   *
   * @event click
   * @param event
   */
  krexx.setAdditionalData = function (event) {

    var wrapper = krexx.getParents(this, '.kwrapper')[0];
    var body = wrapper.querySelector('.kdatabody');
    var html = '';
    var counter = 0;

    // Mark the clicked el, clear the others.
    krexx.removeClass(wrapper.querySelectorAll('.kcurrent-additional'), 'kcurrent-additional');
    krexx.addClass([this], 'kcurrent-additional');

    // Load the Json.

    var json = krexx.getDataset(this, 'addjson');
    json = JSON.parse(json);

    if (typeof json === 'object') {
      // We've got data!
      for (var prop in json) {
        if (json[prop].length > 0) {
          html += '<tr><td>' + prop + '</td><td>' + json[prop] + '</td></tr>';
          counter++;
        }
      }
    }
    if (counter == 0) {
      // We have no data. Tell the user that there is nothing to see.
      html = '<tr><td>No data available for this item.</td><td>Sorry.</td></tr>';
    }

    // Add it to the DOM.
    body.innerHTML = html;
  };

  /**
   * Checks if the search form is inside the viewport. If not, fixes it on top.
   * Gets triggered on,y when scolling the fatel error handler.
   *
   * @event scroll
   */
  krexx.checkSeachInViewport = function (event) {
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
   * Adds a eventlistener to a list of elements.
   *
   * @param selector
   * @param eventName
   * @param callback
   *
   * @return
   *   The elements have processed.
   */
  krexx.addEvent = function (selector, eventName, callback) {
    var elements = document.querySelectorAll(selector);

    for (var i = 0; i < elements.length; i++) {
      elements[i].addEventListener(eventName, callback);
    }
  };

  /**
   * Toggles the class of an element
   *
   * @param el
   * @param className
   */
  krexx.toggleClass = function(el, className) {

    if (el.classList) {
      // Just toggle it.
      el.classList.toggle(className);
    } else {
      // no class list there, we need to do this by hand.
      var classes = el.className.split(' ');
      var existingIndex = classes.indexOf(className);

      if (existingIndex >= 0)
        classes.splice(existingIndex, 1);
      else
        classes.push(className);

      el.className = classes.join(' ');
    }
  };

  /**
   * Removes a class from elements
   *
   * @param selector
   * @param className
   */
  krexx.removeClass = function(selector, className) {
    var elements;

    if (typeof selector === 'string') {
      // Get our elements.
      elements = document.querySelectorAll(selector);
    }
    else {
      // We already have our list that we will use.
      elements = selector;
    }

    for (var i = 0; i < elements.length; i++) {
      if (elements[i].classList) {
        elements[i].classList.remove(className);
      }
      else {
        elements[i].className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
      }
    }
  };

  /**
   * Adds a class to elements.
   *
   * @param selector
   * @param className
   */
  krexx.addClass = function(selector, className) {
    var elements;

    if (typeof selector === 'string') {
      // Get our elements.
      elements = document.querySelectorAll(selector);
    }
    else {
      // We already have our list that we will use.
      elements = selector;
    }

    for (var i = 0; i < elements.length; i++) {
      if (elements[i].classList) {
        elements[i].classList.add(className);
      }
      else {
        elements[i].className += ' ' + className;
      }
    }
  };

  /**
   * Gets the first element from a list which hat that class.
   *
   * @param elements
   * @param className
   * @returns the element
   */
  krexx.findInDomlistByClass = function(elements, className) {

    className = " " + className + " ";
    for (var i = 0; i < elements.length; i++) {
      if ( (" " + elements[i].className + " ").replace(/[\n\t]/g, " ").indexOf(className) > -1 ) {
        return  elements[i];
      }
    }
  };

  /**
   * Determines if an element has a class.
   *
   * @param el
   * @param className
   * @returns {boolean}
   */
  krexx.hasClass = function(el, className) {
    if (el.classList) {
      return el.classList.contains(className);
    }
    else {
      return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
    }
  };

  /**
   * Triggers an event on an element.
   *
   * @param el
   * @param eventName
   */
  krexx.trigger = function(el, eventName) {
    var event = document.createEvent('HTMLEvents');
    event.initEvent(eventName, true, false);
    el.dispatchEvent(event);
  };

  /**
   * Gets all parents of an element which has the specified class.
   *
   * @param el
   * @param selector
   * @returns {Array}
   */
  krexx.getParents = function(el, selector) {
    var result = [];
    var parent = el.parentNode;

    while (parent !== null && typeof parent[matches()] === 'function') {

      // Check for classname
      if (parent[matches()](selector)) {
      // if (krexx.hasClass(parent, className)) {
        result.push(parent);
      }
      // Get the next one.
      parent = parent.parentNode;
    }
    return result;

    // Workarround for several browsers, since matches() is still not really
    // implemented in IE.
    function matches() {
      var el = document.querySelector('body');
      return ( el.mozMatchesSelector || el.msMatchesSelector ||
               el.oMatchesSelector   || el.webkitMatchesSelector ||
               {name:'getAttribute'} ).name;
    }
  };

  /**
   * Listens for a <RETURN> in the search field.
   *
   * @event keyup
   * @param event
   */
  krexx.searchfieldReturn = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    // If this is no <RETURN> key, do nothing.
    if (event.which != 13) {
      return;
    }

    krexx.trigger(this.parentNode.querySelectorAll('.ksearchnow')[1], 'click');
  };

  /**
   * Expands the display of the configuration.
   */
  krexx.expandConfig = function() {
    // Get all configurations
    var configs = document.querySelectorAll('.kconfiguration');
    var elements;

    // Get the second child of every configuration.
    for (var i = 0; i < configs.length; i++) {
      elements = configs[i].querySelectorAll('.kchild .kexpand');
      // We chose the first one.
      krexx.toggleClass(elements[0], 'kopened');
      krexx.toggleClass(elements[0].nextElementSibling, 'khidden');

    }
  }

})();
