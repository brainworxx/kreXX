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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

class Search {
  /**
   * Here we save the search results
   *
   * This is multidimensional array:
   * results[kreXX-instance][search text][search results][pointer]
   * The [pointer] is the key of the [search result] where
   * you would jump to when you click "next"
   *
   */
  protected results: SearchResultsByInstance = {};

  /**
   * The kreXX dom tools.
   *
   * @var {Kdt}
   */
  protected kdt: Kdt;

  /**
   * The kreXX dom tools.
   *
   * @var {Eventhandler}
   */
  protected eventHandler: Eventhandler;

  /**
   * The jump-to implementation.
   */
  protected jumpTo: Function;

  /**
   * Inject the event handler.
   *
   * @param {Eventhandler} eventHandler
   * @param {Function} jumpTo
   */
  constructor(eventHandler: Eventhandler, jumpTo: Function) {
    this.kdt = new Kdt();
    this.eventHandler = eventHandler;
    this.jumpTo = jumpTo;

    // Clear our search results, because we now have new options.
    this.eventHandler.addEvent('.kwrapper .ksearchcase', 'change', this.clearSearch);
    // Clear our search results, because we now have new options.
    this.eventHandler.addEvent('.kwrapper .ksearchkeys', 'change', this.clearSearch);
    // Clear our search results, because we now have new options.
    this.eventHandler.addEvent('.kwrapper .ksearchshort', 'change', this.clearSearch);
    // Clear our search results, because we now have new options.
    this.eventHandler.addEvent('.kwrapper .ksearchlong', 'change', this.clearSearch);
    // Clear our search results, because we now have new options.
    this.eventHandler.addEvent('.kwrapper .ksearchwhole', 'change', this.clearSearch);
    // Clear the search results, because we now have a new tab selected.
    this.eventHandler.addEvent('.kwrapper .ktab', 'click', this.clearSearch);
    // Display our search options.
    this.eventHandler.addEvent('.kwrapper .koptions', 'click', this.displaySearchOptions);
    // Listen for a return key in the seach field.
    this.eventHandler.addEvent('.kwrapper .ksearchfield', 'keyup', this.searchfieldReturn);
  }

  /**
   * Reset the search results, because we now have new search options.
   *
   * @var {Event} event
   *
   * @event change
   */
  protected clearSearch = (event: Event): void => {
    // Wipe our instance data, nothing more
    this.results[this.kdt.getDataset((event.target as Element), 'instance')] = {};
  };

  /**
   * Toggle the display of the search options.
   *
   * @event click
   * @param {Event} event
   *   The click event.
   * @param {Node} element
   *   The element that was clicked.
   */
  protected displaySearchOptions = (event: Event, element: Node): void => {
    // Get the options and switch the display class.
    if (element.parentNode === null) {
      return;
    }
    let nextElementSibling = (element.parentNode as Element).nextElementSibling;
    if (nextElementSibling === null) {
      return;
    }
    nextElementSibling.classList.toggle('khidden');
  };

  /**
   * Prepare the search configuration.
   *
   * @param {Element} element
   * @param {Element} instanceElement
   *
   * @return {SearchConfig}
   */
  protected prepareConfig = (element: Element, instanceElement: Element): SearchConfig => {
    let config: SearchConfig = new SearchConfig();
    config.node = element;
    config.searchtext = (element.querySelector('.ksearchfield') as HTMLInputElement).value;
    config.caseSensitive = (element.querySelector('.ksearchcase') as HTMLInputElement).checked;
    config.searchKeys = (element.querySelector('.ksearchkeys') as HTMLInputElement).checked;
    config.searchShort = (element.querySelector('.ksearchshort') as HTMLInputElement).checked;
    config.searchLong = (element.querySelector('.ksearchlong') as HTMLInputElement).checked;
    config.searchWhole = (element.querySelector('.ksearchwhole') as HTMLInputElement).checked;
    config.instance = this.kdt.getDataset(instanceElement, 'instance');

    // Apply our configuration.
    if (!config.caseSensitive) {
      config.searchtext = config.searchtext.toLowerCase();
    }

    return config;
  }

  /**
   * Check if we have enough information to perform a search.
   *
   * @param config
   *
   * @return {boolean}
   */
  protected isSearchable = (config: SearchConfig): boolean => {
    let searchStateElement = config.node?.querySelector('.ksearch-state');
    if (searchStateElement === null || searchStateElement === undefined) {
      return false;
    }
    if (config.searchtext.length === 0) {
      // Not enough chars as a searchtext!
      searchStateElement.textContent = this.kdt.translations.translate('tsEnterText');
      return false;
    }

    // We only search for more than 3 chars.
    if (config.searchtext.length < 3 && !config.searchWhole) {
      // Not enough chars as a searchtext!
      searchStateElement.textContent = this.kdt.translations.translate('tsTooSmall');
      return false;
    }

    return true;
  }

  /**
   * Prepare the nodes for the search.
   *
   * @param element
   *
   * @return {Element|null}
   */
  protected prepareNodes = (element: Element): Element|null => {
    let parentNode: Element = element.parentNode as Element;
    if (parentNode === null) {
      return null;
    }
    let grandParentNode = parentNode.parentNode as Element;
    if (grandParentNode === null) {
      return null;
    }
    let patentSibling = (parentNode as HTMLElement).nextElementSibling
    if (patentSibling !== null) {
      // Hide the search options.
      this.kdt.addClass([patentSibling], 'khidden');
    }

    return grandParentNode;
  }

  /**
   * Initiates the search.
   *
   * @param {Event} event
   *   The click event.
   * @param {Element} element
   *   The element that was clicked.
   */
  public performSearch = (event: Event, element: Element): void => {
    let grandParentNode = this.prepareNodes(element);
    if (grandParentNode === null) {
      return;
    }

    // Stitching together our configuration.
    let config = this.prepareConfig(grandParentNode, element);

    // Nothing to search for.
    if (!this.isSearchable(config)) {
      return;
    }

    this.retrievePayload(config);

    // We need to un-collapse everything, in case it is collapsed.
    let collapsed: NodeList|undefined = config.payload?.querySelectorAll('.kcollapsed');
    if (collapsed !== undefined) {
      for (let i: number = 0; i < collapsed.length; i++) {
        this.eventHandler.triggerEvent((collapsed[i] as Element), 'click');
      }
    }

    this.refreshResultlist(config);

    let pointer: number = this.results[config.instance][config.searchtext]['pointer'];

    // Set the pointer to the next or previous element
    let direction: string = this.kdt.getDataset(element, 'direction');
    if (direction === 'forward') {
      pointer++;
    } else {
      pointer--;
    }

    // Do we have an element? We may need to adjust the pointer.
    if (typeof this.results[config.instance][config.searchtext]['data'][pointer] === "undefined") {
      if (direction === 'forward') {
        // There is no next element, we go back to the first one.
        pointer = 0;
      } else {
        // There is no previous element, we go forward to the last one.
        pointer = this.results[config.instance][config.searchtext]['data'].length - 1;
      }
    }

    // Check again.
    if (this.results[config.instance][config.searchtext]['data'][pointer]) {
      // Now we simply jump to the element in the array.
      this.jumpTo(this.results[config.instance][config.searchtext]['data'][pointer]);
    }

    // Feedback about where we are
    let searchStateElement = config.node?.querySelector('.ksearch-state');
    if (searchStateElement === null || searchStateElement === undefined) {
      return;
    }
    searchStateElement.textContent =
      (pointer + 1) + ' / ' + (this.results[config.instance][config.searchtext]['data'].length);

    this.results[config.instance][config.searchtext]['pointer'] = pointer;
  };

  /**
   * Retrieve the payload for the search.
   *
   * @param {SearchConfig} config
   */
  protected retrievePayload = (config: SearchConfig): void => {
    // We may need to search in a specific part of the payload.
    let tab: Element|null = document.querySelector('#' + config.instance + ' .ktab.kactive');
    let additionalClasses: string = '';

    if (tab !== null) {
      additionalClasses = ' .' + this.kdt.getDataset(tab, 'what');
    }

    config.payload = document.querySelector('#' + config.instance + ' .kbg-wrapper' + additionalClasses);
  }

  /**
   * Resets our searchlist and fills it with results.
   *
   * @param {SearchConfig} config
   */
  protected refreshResultlist = (config: SearchConfig): void => {
    if (typeof this.results[config.instance] !== "undefined"
      && typeof this.results[config.instance][config.searchtext] !== "undefined"
    ) {
      return;
    }

    // Remove all previous highlights
    this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');

    // Apply our configuration.
    let selector = [];
    if (config.searchKeys) {
      selector.push('li.kchild span.kname');
    }
    if (config.searchShort) {
      selector.push('li.kchild span.kshort')
    }
    if (config.searchLong) {
      selector.push('li div.kpreview');
    }

    // Get a new list of elements
    this.results[config.instance] = {};
    this.results[config.instance][config.searchtext] = {data: [], pointer: 0};
    this.results[config.instance][config.searchtext]['data'] = [];
    this.results[config.instance][config.searchtext]['pointer'] = 0;

    // Poll out payload for elements to search
    if (selector.length > 0) {
      let list: NodeList | undefined;
      list = config.payload?.querySelectorAll(selector.join(', '));
      if (typeof list === "undefined") {
        return;
      }
      let textContent: string = '';
      for (let i: number = 0; i < list.length; ++i) {
        // Does it contain our search string?
        textContent = list[i].textContent ?? '';
        if (!config.caseSensitive) {
          textContent = textContent.toLowerCase();
        }
        if (
          (config.searchWhole && textContent === config.searchtext)
          || (!config.searchWhole && textContent.indexOf(config.searchtext) > -1)
        ) {
          (list[i] as Element).classList.toggle('ksearch-found-highlight');
          this.results[config.instance][config.searchtext]['data'].push(list[i]);
        }
      }
    }

    // Reset our index.
    // When nothing is found, the pointer is toggeling -1, to show that there is something happening.
    this.results[config.instance][config.searchtext]['pointer'] = -1;
  };

  /**
   * Listens for a <RETURN> in the search field.
   *
   * @param {KeyboardEvent} event
   * @event keyUp
   */
  public searchfieldReturn = (event: KeyboardEvent): void => {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    // If this is no <RETURN> key, do nothing.
    if (event.key !== 'Enter') {
      return;
    }

    if (event.target === null) {
      return;
    }
    let parentNode = (event.target as Node).parentNode;
    if (parentNode === null) {
      return;
    }
    this.eventHandler.triggerEvent(parentNode.querySelectorAll('.ksearchnow')[1], 'click');
  };
}
