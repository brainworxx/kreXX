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

class SmokyGrey extends Hans
{
    /**
     * Adjusting the selectors for the Smoky Grey skin.
     */
    constructor()
    {
        // Get the definitions from Hans.
        super();

        this.selectors.close = '.kwrapper .ktool-tabs .kclose, .kwrapper .kheadnote-wrapper .kclose';
    }

    /**
     * Getting our act together.
     */
    public run()
    {
        super.run.call(this);

        // Get viewport height to set kreXX data payload to max 75% for debug.
        // The payload for the fatal error handler is set to the remaining space.
        this.setPayloadMaxHeight();

        /**
         * Register the click on the tabs.
         *
         * @event click
         */
        this.eventHandler.addEvent('.ktool-tabs .ktab:not(.ksearchbutton)', 'click', this.switchTab);

         /**
         * Add the additional data to the footer.
         *
         * @event click
         */
        this.eventHandler.addEvent('.kwrapper .kel', 'click', this.setAdditionalData);
    }

    /**
     * Initialize the draggable.
     */
    protected initDraxx = () : void =>
    {
        this.draxx = new Draxx('.kwrapper', '.khandle', function (){},function (){});
    };

    /**
     * Sets the kactive on the clicked element and removes it from the others.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    protected switchTab = (event:Event, element:Element) : void =>
    {
        var instance = this.kdt.getDataset(element.parentNode, 'instance');
        var what = this.kdt.getDataset(element, 'what');

        // Toggle the highlighting.
        this.kdt.removeClass('#' + instance + ' .kactive:not(.ksearchbutton)', 'kactive');

        if (element.classList) {
            element.classList.add('kactive');
        } else {
            element.className += ' kactive';
        }

        // Toggle what is displayed
        this.kdt.addClass('#' + instance + ' .kpayload', 'khidden');
        this.kdt.removeClass('#' + instance + ' .' + what, 'khidden');
    };

    /**
     * Displays the additional data and marks the row that is displayed.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    protected setAdditionalData = (event:Event, element:Node) : void =>
    {
        let kdt:Kdt = this.kdt;
        // When dealing with 400MB output, or more, this one takes more time than anything else.
        // We will delay it, so that is does not slow down other stuff.
        setTimeout(function() {
            let wrapper = kdt.getParents(element, '.kwrapper')[0];
            if (typeof wrapper === 'undefined') {
                // This only happens, when we are facing a recursion. There is no
                // additional json data, anyway.
                return;
            }

            let body = wrapper.querySelector('.kdatabody');
            let html = '';
            let counter = 0;
            let regex = /\\u([\d\w]{4})/gi;

            // Mark the clicked el, clear the others.
            kdt.removeClass(wrapper.querySelectorAll('.kcurrent-additional'), 'kcurrent-additional');
            kdt.addClass([element], 'kcurrent-additional');

            // Load the Json.
            let json = kdt.parseJson(kdt.getDataset(element, 'addjson', false));

            if (typeof json === 'object') {
                // We've got data!
                for (var prop in json) {
                    if (json[prop].length > 0) {
                        json[prop] = json[prop].replace(regex, function (match, grp) {
                            return String.fromCharCode(parseInt(grp, 16));
                        });
                        json[prop] = decodeURI(json[prop]);
                        html += '<tr><td class="kinfo">' + prop + '</td><td class="kdesc">' + json[prop] + '</td></tr>';
                        counter++;
                    }
                }
            }
            if (counter === 0) {
                // We have no data. Tell the user that there is nothing to see.
                html = '<tr><td class="kinfo">No data available for this item.</td><td class="kdesc">Sorry.</td></tr>';
            }

            // Add it to the DOM.
            html = '<table><caption class="kheadline">Additional data</caption><tbody class="kdatabody">' + html + '</tbody></table>';
            // Meh, IE9 does not allow me to edit the contents of a table. I have to
            // redraw the whole thing.  :-(
            body.parentNode.parentNode.innerHTML = html;

            // Since the additional data table might now be larger or smaller than,
            // we need to recalculate the height of the payload.
            this.setPayloadMaxHeight();

        }, 100);
    };

    /**
     * Display the search dialog
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    protected displaySearch = (event, element) =>
    {

        let instance:string = this.kdt.getDataset(element.parentNode, 'instance');
        let search:HTMLElement = document.querySelector('#search-' + instance);
        let searchtab:HTMLElement = document.querySelector('#' + instance + ' .ksearchbutton');

        // Toggle display / hidden.
        if (this.kdt.hasClass(search, 'khidden')) {
            // Display it.
            this.kdt.toggleClass(search, 'khidden');
            this.kdt.toggleClass(searchtab, 'kactive');
            (search.querySelector('.ksearchfield') as HTMLElement).focus();
        } else {
            // Hide it.
            this.kdt.toggleClass(search, 'khidden');
            this.kdt.toggleClass(searchtab, 'kactive');
            // Clear the results.
            this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
        }
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
    protected jumpTo = (el:Element, noHighlight:boolean) =>
    {
        let nests:Node[] = this.kdt.getParents(el, '.knest');
        let container:Node[];

        // Show them.
        this.kdt.removeClass(nests, 'khidden');
        // We need to expand them all.
        for (var i = 0; i < nests.length; i++) {
            this.kdt.addClass([(nests[i] as Element).previousElementSibling], 'kopened');
        }

        if (noHighlight !== true) {
            // Remove old highlighting.
            this.kdt.removeClass('.highlight-jumpto', 'highlight-jumpto');
            // Highlight new one.
            this.kdt.addClass([el], 'highlight-jumpto');
        }

        // Getting our scroll container
        container = this.kdt.getParents(el, '.kpayload');

        container.push(document.querySelector('.kfatalwrapper-outer'));
        if (container.length > 0) {
            // We need to find out in which direction we must go.
            // We also must determine the speed we want to travel.
            let step:number;
            let destination:number = el.getBoundingClientRect().top - (container[0] as Element).getBoundingClientRect().top + (container[0] as Element).scrollTop - 50;
            let diff:number = Math.abs((container[0] as Element).scrollTop - destination);
            if ((container[0] as Element).scrollTop < destination) {
                // Forward.
                step = Math.round(diff / 12);
            } else {
                // Backward.
                step = Math.round(diff / 12) * -1;
            }

            // We also need to check if the setting of the new valkue was successful.
            let lastValue:number = (container[0] as Element).scrollTop;
            let interval = setInterval(function () {
                (container[0] as Element).scrollTop += step;
                if (Math.abs((container[0] as Element).scrollTop - destination) <= Math.abs(step) || (container[0] as Element).scrollTop === lastValue) {
                    // We are here now, the next step would take us too far.
                    // So we jump there right now and then clear the interval.
                    (container[0] as Element).scrollTop = destination;
                    clearInterval(interval);
                }
                lastValue = (container[0] as Element).scrollTop;
            }, 1);
        }
    }
}