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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

class Hans {

    /**
     * kreXX dom tools.
     *
     * @var {Kdt}
     */
    protected kdt:Kdt;

    /**
     * Our dragable lib.
     *
     * @var {Draxx}
     */
    protected draxx:Draxx;

    /**
     * Out DOM search.
     *
     * @var {Search}
     */
    protected search:Search;

    /**
     * The event handler.
     *
     * @var {Evenhandler}
     */
    protected eventHandler:Eventhandler;

    /**
     * Getting our act together.
     */
    constructor()
    {
        // Init our libs before usage.
        this.kdt = new Kdt();
        this.kdt.setKrexx(this);
        this.eventHandler = new Eventhandler('.kwrapper.kouterwrapper, .kfatalwrapper-outer');
        this.search = new Search(this.eventHandler, this.jumpTo);

        // In case we are handling a broken html structure, we must move everything
        // to the bottom.
        this.kdt.moveToBottom('.kouterwrapper');

        // Initialize the draggable.
        this.initDraxx();

        /**
         * Register krexx close button function.
         *
         * @event click
         *   Displays a closing animation of the corresponding
         *   krexx output "window" and then removes it from the markup.
         */
        this.eventHandler.addEvent('.kwrapper .kheadnote-wrapper .kclose, .kwrapper .kfatal-headnote .kclose', 'click', this.close);

        /**
         * Register toggling to the elements.
         *
         * @event click
         *   Expands a krexx node when it is not expanded.
         *   When it is already expanded, it closes it.
         */
        this.eventHandler.addEvent('.kwrapper .kexpand', 'click', this.toggle);

        /**
         * Register functions for the local dev-settings.
         *
         * @event change
         *   Changes on the krexx html forms.
         *   All changes will automatically be written to the browser cookies.
         */
        this.eventHandler.addEvent('.kwrapper .keditable select, .kwrapper .keditable input:not(.ksearchfield)', 'change', this.kdt.setSetting);

        /**
         * Register cookie reset function on the reset button.
         *
         * @event click
         *   Resets the local settings in the settings cookie,
         *   when the reset button ic clicked.
         */
        this.eventHandler.addEvent('.kwrapper .kresetbutton', 'click', this.kdt.resetSetting);

        /**
         * Register the recursions resolving.
         *
         * @event click
         *   When a recursion is clicked, krexx tries to locate the
         *   first output of the object and highlight it.
         */
        this.eventHandler.addEvent('.kwrapper .kcopyFrom', 'click', this.copyFrom);

        /**
         * Register the displaying of the search menu
         *
         * @event click
         *   When the button is clicked, krexx will display the
         *   search menu associated this the same output window.
         */
        this.eventHandler.addEvent('.kwrapper .ksearchbutton, .kwrapper .ksearch .kclose', 'click', this.search.displaySearch);

        /**
         * Register the search event on the next button.
         *
         * @event click
         *   When the button is clicked, krexx will start searching.
         */
        this.eventHandler.addEvent('.kwrapper .ksearchnow', 'click', this.search.performSearch);

        /**
         * Register the Collapse-All functions on it's symbol
         *
         * @event click
         */
        this.eventHandler.addEvent('.kwrapper .kolps', 'click', this.kdt.collapse);

        /**
         * Register the code generator on the P symbol.
         *
         * @event click
         */
        this.eventHandler.addEvent('.kwrapper .kgencode', 'click', this.generateCode);

        /**
         * Prevents the click-event-bubbling on the generated code.
         *
         * @event click
         */
        this.eventHandler.addEvent('.kodsp', 'click', this.eventHandler.preventBubble);

        /**
         * Display the content of the info box.
         *
         * @event click
         */
        this.eventHandler.addEvent('.kwrapper .kchild .kinfobutton', 'click', this.displayInfoBox);

        // Disable form-buttons in case a logfile is opened local.
        if (window.location.protocol === 'file:') {
            this.disableForms();
        }

        // Move the output into the viewport. Debugging onepager is so annoying, otherwise.
        this.draxx.moveToViewport('.kouterwrapper');
    }

    /**
     * Initialize the draggable.
     */
    protected initDraxx() : void
    {
        this.draxx = new Draxx(
            '.kwrapper',
            '.kheadnote',
            function () {
                let searchWrapper:NodeList = document.querySelectorAll('.search-wrapper');
                let viewportOffset:ClientRect;
                for (let i = 0; i < searchWrapper.length; i++) {
                    viewportOffset = (searchWrapper[i] as HTMLElement).getBoundingClientRect();
                    (searchWrapper[i] as HTMLElement).style.position = 'fixed';
                    (searchWrapper[i] as HTMLElement).style.top = viewportOffset.top + 'px';
                }
            },
            function () {
                let searchWrapper = document.querySelectorAll('.search-wrapper');
                for (let i = 0; i < searchWrapper.length; i++) {
                    (searchWrapper[i] as HTMLElement).style.position = 'absolute';
                    (searchWrapper[i] as HTMLElement).style.top = '';
                }
            }
        );
    }

    /**
     * When clicked on s recursion, this function will
     * copy the original analysis result there and delete
     * the recursion.
     *
     * @param {Event} event
     *   The click event.
     * @param {HTMLElement} element
     *   The element that was clicked.
     */
    protected copyFrom = (event:Event, element:HTMLElement) : void =>
    {
        let i:number;

        // Get the DOM id of the original analysis.
        let domid:string = this.kdt.getDataset((element as Element), 'domid');
        // Get the analysis data.
        let orgNest:Node = document.querySelector('#' + domid);

        // Does the element exist?
        if (orgNest) {
            // Get the EL of the data (element with the arrow).
            let orgEl:Node = (orgNest as HTMLElement).previousElementSibling;
            // Clone the analysis data and insert it after the recursion EL.
            element.parentNode.insertBefore(orgNest.cloneNode(true), element.nextSibling);
            // Clone the EL of the analysis data and insert it after the recursion EL.
            let newEl:Element = (orgEl.cloneNode(true) as Element);
            element.parentNode.insertBefore(newEl, element.nextSibling);

            // Change the key of the just cloned EL to the one from the recursion.
            this.kdt.findInDomlistByClass(newEl.children, 'kname').innerHTML = this.kdt.findInDomlistByClass(element.children, 'kname').innerHTML;
            // We  need to remove the ids from the copy to avoid double ids.
            let allChildren = newEl.nextElementSibling.getElementsByTagName("*");
            for (i = 0; i < allChildren.length; i++) {
                allChildren[i].removeAttribute('id');
            }
            newEl.nextElementSibling.removeAttribute('id');

            // Now we add the dom-id to the clone, as a data-field. this way we can
            // make sure to always produce the right path to this value during source
            // generation.
            this.kdt.setDataset(newEl.parentNode, 'domid', domid);

            // Remove the infobox from the copy, if available and add the one from the
            // recursion.
            let newInfobox = newEl.querySelector('.khelp');
            let newButton = newEl.querySelector('.kinfobutton');
            let realInfobox = element.querySelector('.khelp');
            let realButton = element.querySelector('.kinfobutton');

            // We don't need the infobox on newEl, so we will remove it.
            if (newInfobox !== null) {
                newInfobox.parentNode.removeChild(newInfobox);
            }
            if (newButton !== null) {
                newButton.parentNode.removeChild(newButton);
            }

            // We copy the Infobox from the recursion to the newEl, if it exists.
            if (realInfobox !== null) {
                newEl.appendChild(realButton);
                newEl.appendChild(realInfobox);
            }

            // Remove the recursion EL.
            element.parentNode.removeChild(element);
        }
    };

    /**
     * Hides or displays the nest under an expandable element.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    protected toggle = (event, element) : void =>
    {
        this.kdt.toggleClass(element, 'kopened');
        this.kdt.toggleClass(element.nextElementSibling, 'khidden');
    };

    /**
     * "Jumps" to an element in the markup and highlights it.
     *
     * It is used when we are facing a recursion in our analysis.
     *
     * @param {Element} el
     *   The element you want to focus on.
     * @param {boolean} noHighlight
     *   Do we need to highlight the elenemt we arejuming to?
     */
    protected jumpTo = (el:Element, noHighlight:boolean) : void =>
    {
        let nests:Node[] = this.kdt.getParents(el, '.knest');
        let container:Element|null;
        let destination:number;
        let diff:number;
        let step:number;

        // Show them.
        this.kdt.removeClass(nests, 'khidden');
        // We need to expand them all.
        for (let i = 0; i < nests.length; i++) {
            this.kdt.addClass([(nests[i] as Element).previousElementSibling], 'kopened');
        }

        if (noHighlight !== true) {
            // Remove old highlighting.
            this.kdt.removeClass('.highlight-jumpto', 'highlight-jumpto');
            // Highlight new one.
           this.kdt.addClass([el], 'highlight-jumpto');
        }

        // Getting our scroll container
        container = document.querySelector('.kfatalwrapper-outer');

        if (container === null) {
            // Normal scrolling
            container = document.querySelector('html');
            // The html container may not accept any scrollTop value.
            ++container.scrollTop;
            if (container.scrollTop === 0 || container.scrollHeight <= container.clientHeight) {
                container = document.querySelector('body');
            }
            --container.scrollTop;
            destination = el.getBoundingClientRect().top + container.scrollTop - 50;
        } else {
            // Fatal Error scrolling.
            destination = el.getBoundingClientRect().top - container.getBoundingClientRect().top + container.scrollTop - 50;
        }

        diff = Math.abs(container.scrollTop - destination);
        if (diff < 250) {
            // No need to jump there
            return;
        }

        // Getting the direction
        if (container.scrollTop < destination) {
            // Forward.
            step = Math.round(diff / 12);
        } else {
            // Backward.
            step = Math.round(diff / 12) * -1;
        }

        // We also need to check if the setting of the new value was successful.
        let lastValue = container.scrollTop;
        let interval = setInterval(function () {

            container.scrollTop += step;
            if (Math.abs(container.scrollTop - destination) <= Math.abs(step) || container.scrollTop === lastValue) {
                // We are here now, the next step would take us too far.
                // So we jump there right now and then clear the interval.
                container.scrollTop = destination;
                clearInterval(interval);
            }
            lastValue = container.scrollTop;
        }, 10);
    };

    /**
     * Shows a "fast" closing animation and then removes the krexx window from the markup.
     *
     * @param {Event} event
     *   The click event.
     * @param {Element} element
     *   The element that was clicked.
     */
    protected close = (event:Event, element:Element) : void =>
    {
        let instance:string = this.kdt.getDataset(element, 'instance');
        let elInstance:HTMLElement = document.querySelector('#' + instance);

        // Remove it nice and "slow".
        let opacity:number = 1;
        let interval:number = setInterval(function () {
            if (opacity < 0) {
                // It's invisible now, so we clear the timer and remove it from the DOM.
                clearInterval(interval);
                elInstance.parentNode.removeChild(elInstance);
                return;
            }
            opacity -= 0.1;
            elInstance.style.opacity = opacity.toString();
        }, 20);
    };

    /**
     * Disables the editing functions, when a krexx output is loaded as a file.
     *
     * These local settings would actually do
     * nothing at all, because they would land inside a cookie
     * for that file, and not for the server.
     */
    protected disableForms = () : void =>
    {
        let elements:NodeList = document.querySelectorAll('.kwrapper .keditable input, .kwrapper .keditable select');
        for (let i = 0; i < elements.length; i++) {
            elements[i].disabled = true;
        }
    };

    /**
     * The kreXX code generator.
     *
     * @param {Event} event
     *   The click event.
     * @param {Element} element
     *   The element that was clicked.
     */
    protected generateCode = (event:Event, element:Element) : void =>
    {

        // We don't want to bubble the click any further.
        event.stop = true;

        let codedisplay:HTMLElement = (element.nextElementSibling as HTMLElement);
        let resultArray:string[] = [];
        let resultString:string = '';
        let sourcedata:string;
        let domid:string;
        let wrapperLeft:string = '';
        let wrapperRight:string = '';

        // Get the first element
        let el:Element|Node = (this.kdt.getParents(element, 'li.kchild')[0] as Element);

        // Start the loop to collect all the date
        while (el) {
            // Get the domid
            domid = this.kdt.getDataset((el as Element), 'domid');
            sourcedata = this.kdt.getDataset((el as Element), 'source');

            wrapperLeft = this.kdt.getDataset((el as Element), 'codewrapperLeft');
            wrapperRight = this.kdt.getDataset((el as Element), 'codewrapperRight');

            if (sourcedata === '. . .') {
                if (domid !== '') {
                    // We need to get a new el, because we are facing a recursion, and the
                    // current path is not really reachable.
                    el = document.querySelector('#' + domid).parentNode;
                    // Get the source, again.
                    resultArray.push(this.kdt.getDataset((el as Element), 'source'));
                }
            }
            if (sourcedata !== '') {
                resultArray.push(sourcedata);
            }
            // Get the next el.
            el = this.kdt.getParents(el, 'li.kchild')[0];
        }
        // Now we reverse our result, so that we can resolve it from the beginning.
        resultArray.reverse();

        for (let i = 0; i < resultArray.length; i++) {
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
                // We add our result so far into the "source template"
                resultString = resultArray[i].replace(';firstMarker;', resultString);
            } else {
                // Normal concatenation.
                resultString = resultString + resultArray[i];
            }
        }

        // Add the wrapper that we collected so far
        resultString = wrapperLeft + resultString + wrapperRight;

        // 3. Add the text
        codedisplay.innerHTML = '<div class="kcode-inner">' + resultString + '</div>';
        if (codedisplay.style.display === 'none') {
            codedisplay.style.display = '';
            this.kdt.selectText(codedisplay);
        } else {
            codedisplay.style.display = 'none';
        }
    };

    /**
     * Sets the max-height on the payload elements, depending on the viewport.
     */
    protected setPayloadMaxHeight = () : void =>
    {
        // Get the height.
        let height:number = Math.round(Math.max(document.documentElement.clientHeight, window.innerHeight || 0) * 0.60);

        if (height > 0) {
            let elements:NodeList = document.querySelectorAll('.krela-wrapper .kpayload');
            for (let i = 0; i < elements.length; i++) {
                (elements[i] as HTMLElement).style.maxHeight = height + 'px';
            }
        }
    };

    /**
     * Checks if the search form is inside the viewport. If not, fixes it on top.
     * Gets triggered on,y when scolling the fatal error handler.
     */
    protected checkSearchInViewport = () : void =>
    {
        // Get the search
        let search:HTMLElement = document.querySelector('.kfatalwrapper-outer .search-wrapper');
        // Reset the inline styles
        search.style.position = '';
        search.style.top = '';

        // Measure it!
        let rect = search.getBoundingClientRect();
        if (rect.top < 0) {
            // Set it to the top
            search.style.position = 'fixed';
            search.style.top = '0px';
        }
    }

    /**
     * Toggle the display of t he infobox.
     *
     * @param {Event} event
     * @param {Element} element
     *
     * @event keyUp
     */
    protected displayInfoBox = (event:Event, element:Element) : void =>
    {
        // We don't want to bubble the click any further.
        event.stop = true;

        // Find the corresponding info box.
        var box:HTMLElement = (element.nextElementSibling as HTMLElement);

        if (box.style.display === 'none') {
            box.style.display = '';
        } else {
            box.style.display = 'none';
        }
    };
}
