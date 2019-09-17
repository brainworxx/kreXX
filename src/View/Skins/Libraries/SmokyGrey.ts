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

class SmokyGrey extends Hans {

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
        console.log('Schmoki Gräi');
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
    }
}