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

class CodeGen {
  /**
   * kreXX dom tools.
   *
   * @var {Kdt}
   */
  protected kdt: Kdt;

  /**
   * Not much to do, we simply grab the KDT.
   */
  constructor() {
    this.kdt = new Kdt();
  }

  /**
   * The kreXX code generator.
   *
   * @event click
   * @param {Event} event
   *   The click event.
   * @param {Element} element
   *   The element that was clicked.
   */
  public generateCode = (event: Event, element: Element): void => {
    // We don't want to bubble the click any further.
    (event as StoppableEvent).stop = true;

    let codedisplay: HTMLElement = (element.nextElementSibling as HTMLElement);
    let resultArray: string[] = [];
    let resultString: string = '';
    let sourcedata: string;
    let domid: string;
    let wrapperLeft: string = '';
    let wrapperRight: string = '';

    // Get the first element
    let el: Element | Node | ParentNode | null | undefined = (this.kdt.getParents(element, 'li.kchild')[0] as Element);

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
          el = document.querySelector('#' + domid)?.parentNode;
          if (!el) {
            break;
          }
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
}
