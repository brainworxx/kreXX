var Draxx = (function () {
    function Draxx(selector, handle, callbackUp, callbackDrag) {
        var _this = this;
        if (callbackUp === void 0) { callbackUp = null; }
        if (callbackDrag === void 0) { callbackDrag = null; }
        this.moveToViewport = function (selector) {
            setTimeout(function () {
                var viewportTop = document.documentElement.scrollTop;
                if (viewportTop === 0) {
                    viewportTop = document.body.scrollTop;
                }
                var elements = document.querySelectorAll(selector);
                var oldOffset = 0;
                for (var i = 0; i < elements.length; i++) {
                    oldOffset = parseInt(elements[i].style.top.slice(0, -2), 10);
                    elements[i].style.top = (oldOffset + viewportTop) + 'px';
                }
            }, 500);
        };
        this.startDraxx = function (event) {
            var elContent = _this.kdt.getParents(event.target, _this.selector)[0];
            var offset = _this.getElementOffset(elContent);
            _this.offSetY = offset.top + elContent.offsetHeight - event.pageY - elContent.offsetHeight;
            _this.offSetX = offset.left + _this.outerWidth(elContent) - event.pageX - _this.outerWidth(elContent);
            _this.elContentStyle = elContent.style;
            var bodyStyle = getComputedStyle(document.querySelector('body'));
            if (bodyStyle.position === 'relative') {
                var relOffsetY = void 0;
                var relOffsetX = void 0;
                relOffsetY = parseInt(bodyStyle.marginTop, 10);
                relOffsetX = parseInt(bodyStyle.marginLeft, 10);
                if (relOffsetY > 0) {
                }
                else {
                    var prev = elContent.previousElementSibling;
                    do {
                        relOffsetY = parseInt(getComputedStyle(prev).marginTop, 10);
                        prev = prev.previousElementSibling;
                    } while (prev && relOffsetY === 0);
                }
                _this.offSetY -= relOffsetY;
                _this.offSetX -= relOffsetX;
            }
            document.addEventListener("mousemove", _this.drag);
            document.addEventListener("mouseup", _this.mouseUp);
            event.preventDefault();
            event.stopPropagation();
        };
        this.mouseUp = function (event) {
            event.preventDefault();
            event.stopPropagation();
            document.removeEventListener("mousemove", _this.drag);
            document.removeEventListener("mouseup", _this.mouseUp);
            _this.callbackUp();
        };
        this.drag = function (event) {
            event.preventDefault();
            event.stopPropagation();
            _this.elContentStyle.left = (event.pageX + _this.offSetX) + "px";
            _this.elContentStyle.top = (event.pageY + _this.offSetY) + "px";
            _this.callbackDrag();
        };
        this.getElementOffset = function (element) {
            var de = document.documentElement;
            var box = element.getBoundingClientRect();
            var top = box.top + window.pageYOffset - de.clientTop;
            var left = box.left + window.pageXOffset - de.clientLeft;
            return { top: top, left: left };
        };
        this.outerWidth = function (element) {
            var width = element.offsetWidth;
            var style = getComputedStyle(element);
            width += parseInt(style.marginLeft, 10) + parseInt(style.marginRight, 10);
            return width;
        };
        this.selector = selector;
        this.callbackUp = callbackUp;
        this.callbackDrag = callbackDrag;
        this.kdt = new Kdt();
        var elements = document.querySelectorAll(selector);
        for (var i = 0; i < elements.length; i++) {
            elements[i].addEventListener('mousedown', this.startDraxx);
        }
    }
    return Draxx;
}());
var Eventhandler = (function () {
    function Eventhandler(selector) {
        var _this = this;
        this.storage = [];
        this.addEvent = function (selector, eventName, callBack) {
            if (eventName === 'click') {
                _this.addToStorage(selector, callBack);
            }
            else {
                var elements = document.querySelectorAll(selector);
                for (var i = 0; i < elements.length; i++) {
                    elements[i].addEventListener(eventName, callBack);
                }
            }
        };
        this.preventBubble = function (event) {
            event.stop = true;
        };
        this.addToStorage = function (selector, callback) {
            if (!(selector in _this.storage)) {
                _this.storage[selector] = [];
            }
            _this.storage[selector].push(callback);
        };
        this.handle = function (event) {
            event.stopPropagation();
            event.stop = false;
            var element = event.target;
            var selector;
            var i;
            var callbackArray = [];
            do {
                for (selector in _this.storage) {
                    if (element.matches(selector)) {
                        callbackArray = _this.storage[selector];
                        for (i = 0; i < callbackArray.length; i++) {
                            callbackArray[i](event, element);
                            if (event.stop) {
                                return;
                            }
                        }
                    }
                }
                element = element.parentNode;
                if (element === event.currentTarget) {
                    element = null;
                }
            } while (element !== null);
        };
        this.triggerEvent = function (el, eventName) {
            var event = document.createEvent('HTMLEvents');
            event.initEvent(eventName, true, false);
            el.dispatchEvent(event);
        };
        this.kdt = new Kdt();
        var elements = document.querySelectorAll(selector);
        for (var i = 0; i < elements.length; i++) {
            elements[i].addEventListener('click', this.handle);
        }
    }
    return Eventhandler;
}());
var Hans = (function () {
    function Hans() {
        var _this = this;
        this.copyFrom = function (event, element) {
            var i;
            var domid = _this.kdt.getDataset(element, 'domid');
            var orgNest = document.querySelector('#' + domid);
            if (orgNest) {
                var orgEl = orgNest.previousElementSibling;
                element.parentNode.insertBefore(orgNest.cloneNode(true), element.nextSibling);
                var newEl = orgEl.cloneNode(true);
                element.parentNode.insertBefore(newEl, element.nextSibling);
                _this.kdt.findInDomlistByClass(newEl.children, 'kname').innerHTML = _this.kdt.findInDomlistByClass(element.children, 'kname').innerHTML;
                var allChildren = newEl.nextElementSibling.getElementsByTagName("*");
                for (i = 0; i < allChildren.length; i++) {
                    allChildren[i].removeAttribute('id');
                }
                newEl.nextElementSibling.removeAttribute('id');
                _this.kdt.setDataset(newEl.parentNode, 'domid', domid);
                var newInfobox = newEl.querySelector('.khelp');
                var newButton = newEl.querySelector('.kinfobutton');
                var realInfobox = element.querySelector('.khelp');
                var realButton = element.querySelector('.kinfobutton');
                if (newInfobox !== null) {
                    newInfobox.parentNode.removeChild(newInfobox);
                }
                if (newButton !== null) {
                    newButton.parentNode.removeChild(newButton);
                }
                if (realInfobox !== null) {
                    newEl.appendChild(realButton);
                    newEl.appendChild(realInfobox);
                }
                element.parentNode.removeChild(element);
            }
        };
        this.toggle = function (event, element) {
            _this.kdt.toggleClass(element, 'kopened');
            _this.kdt.toggleClass(element.nextElementSibling, 'khidden');
        };
        this.jumpTo = function (el, noHighlight) {
            var nests = _this.kdt.getParents(el, '.knest');
            var container;
            var destination;
            var diff;
            var step;
            _this.kdt.removeClass(nests, 'khidden');
            for (var i = 0; i < nests.length; i++) {
                _this.kdt.addClass([nests[i].previousElementSibling], 'kopened');
            }
            if (noHighlight !== true) {
                _this.kdt.removeClass('.highlight-jumpto', 'highlight-jumpto');
                _this.kdt.addClass([el], 'highlight-jumpto');
            }
            container = document.querySelector('.kfatalwrapper-outer');
            if (container === null) {
                container = document.querySelector('html');
                ++container.scrollTop;
                if (container.scrollTop === 0 || container.scrollHeight <= container.clientHeight) {
                    container = document.querySelector('body');
                }
                --container.scrollTop;
                destination = el.getBoundingClientRect().top + container.scrollTop - 50;
            }
            else {
                destination = el.getBoundingClientRect().top - container.getBoundingClientRect().top + container.scrollTop - 50;
            }
            diff = Math.abs(container.scrollTop - destination);
            if (diff < 250) {
                return;
            }
            if (container.scrollTop < destination) {
                step = Math.round(diff / 12);
            }
            else {
                step = Math.round(diff / 12) * -1;
            }
            var lastValue = container.scrollTop;
            var interval = setInterval(function () {
                container.scrollTop += step;
                if (Math.abs(container.scrollTop - destination) <= Math.abs(step) || container.scrollTop === lastValue) {
                    container.scrollTop = destination;
                    clearInterval(interval);
                }
                lastValue = container.scrollTop;
            }, 10);
        };
        this.close = function (event, element) {
            var instance = _this.kdt.getDataset(element, 'instance');
            var elInstance = document.querySelector('#' + instance);
            var opacity = 1;
            var interval = setInterval(function () {
                if (opacity < 0) {
                    clearInterval(interval);
                    elInstance.parentNode.removeChild(elInstance);
                    return;
                }
                opacity -= 0.1;
                elInstance.style.opacity = opacity.toString();
            }, 20);
        };
        this.disableForms = function () {
            var elements = document.querySelectorAll('.kwrapper .keditable input, .kwrapper .keditable select');
            for (var i = 0; i < elements.length; i++) {
                elements[i].disabled = true;
            }
        };
        this.generateCode = function (event, element) {
            event.stop = true;
            var codedisplay = element.nextElementSibling;
            var resultArray = [];
            var resultString = '';
            var sourcedata;
            var domid;
            var wrapperLeft = '';
            var wrapperRight = '';
            var el = _this.kdt.getParents(element, 'li.kchild')[0];
            while (el) {
                domid = _this.kdt.getDataset(el, 'domid');
                sourcedata = _this.kdt.getDataset(el, 'source');
                wrapperLeft = _this.kdt.getDataset(el, 'codewrapperLeft');
                wrapperRight = _this.kdt.getDataset(el, 'codewrapperRight');
                if (sourcedata === '. . .') {
                    if (domid !== '') {
                        el = document.querySelector('#' + domid).parentNode;
                        resultArray.push(_this.kdt.getDataset(el, 'source'));
                    }
                }
                if (sourcedata !== '') {
                    resultArray.push(sourcedata);
                }
                el = _this.kdt.getParents(el, 'li.kchild')[0];
            }
            resultArray.reverse();
            for (var i = 0; i < resultArray.length; i++) {
                if (resultArray[i] === '. . .') {
                    resultString = '// Value is either protected or private.<br /> // Sorry . . ';
                    break;
                }
                if (resultArray[i] === ';stop;') {
                    resultString = '';
                    resultArray[i] = '';
                }
                if (resultArray[i].indexOf(';firstMarker;') !== -1) {
                    resultString = resultArray[i].replace(';firstMarker;', resultString);
                }
                else {
                    resultString = resultString + resultArray[i];
                }
            }
            resultString = wrapperLeft + resultString + wrapperRight;
            codedisplay.innerHTML = '<div class="kcode-inner">' + resultString + '</div>';
            if (codedisplay.style.display === 'none') {
                codedisplay.style.display = '';
                _this.kdt.selectText(codedisplay);
            }
            else {
                codedisplay.style.display = 'none';
            }
        };
        this.setPayloadMaxHeight = function () {
            var height = Math.round(Math.max(document.documentElement.clientHeight, window.innerHeight || 0) * 0.60);
            if (height > 0) {
                var elements = document.querySelectorAll('.krela-wrapper .kpayload');
                for (var i = 0; i < elements.length; i++) {
                    elements[i].style.maxHeight = height + 'px';
                }
            }
        };
        this.checkSearchInViewport = function () {
            var search = document.querySelector('.kfatalwrapper-outer .search-wrapper');
            search.style.position = '';
            search.style.top = '';
            var rect = search.getBoundingClientRect();
            if (rect.top < 0) {
                search.style.position = 'fixed';
                search.style.top = '0px';
            }
        };
        this.displayInfoBox = function (event, element) {
            event.stop = true;
            var box = element.nextElementSibling;
            if (box.style.display === 'none') {
                box.style.display = '';
            }
            else {
                box.style.display = 'none';
            }
        };
        this.kdt = new Kdt();
        this.kdt.setKrexx(this);
        this.eventHandler = new Eventhandler('.kwrapper.kouterwrapper, .kfatalwrapper-outer');
        this.search = new Search(this.eventHandler, this.jumpTo);
        this.kdt.moveToBottom('.kouterwrapper');
        this.initDraxx();
        this.eventHandler.addEvent('.kwrapper .kheadnote-wrapper .kclose, .kwrapper .kfatal-headnote .kclose', 'click', this.close);
        this.eventHandler.addEvent('.kwrapper .kexpand', 'click', this.toggle);
        this.eventHandler.addEvent('.kwrapper .keditable select, .kwrapper .keditable input:not(.ksearchfield)', 'change', this.kdt.setSetting);
        this.eventHandler.addEvent('.kwrapper .kresetbutton', 'click', this.kdt.resetSetting);
        this.eventHandler.addEvent('.kwrapper .kcopyFrom', 'click', this.copyFrom);
        this.eventHandler.addEvent('.kwrapper .ksearchbutton, .kwrapper .ksearch .kclose', 'click', this.search.displaySearch);
        this.eventHandler.addEvent('.kwrapper .ksearchnow', 'click', this.search.performSearch);
        this.eventHandler.addEvent('.kwrapper .kolps', 'click', this.kdt.collapse);
        this.eventHandler.addEvent('.kwrapper .kgencode', 'click', this.generateCode);
        this.eventHandler.addEvent('.kodsp', 'click', this.eventHandler.preventBubble);
        this.eventHandler.addEvent('.kwrapper .kchild .kinfobutton', 'click', this.displayInfoBox);
        if (window.location.protocol === 'file:') {
            this.disableForms();
        }
        this.draxx.moveToViewport('.kouterwrapper');
    }
    Hans.prototype.initDraxx = function () {
        this.draxx = new Draxx('.kwrapper', '.kheadnote', function () {
            var searchWrapper = document.querySelectorAll('.search-wrapper');
            var viewportOffset;
            for (var i = 0; i < searchWrapper.length; i++) {
                viewportOffset = searchWrapper[i].getBoundingClientRect();
                searchWrapper[i].style.position = 'fixed';
                searchWrapper[i].style.top = viewportOffset.top + 'px';
            }
        }, function () {
            var searchWrapper = document.querySelectorAll('.search-wrapper');
            for (var i = 0; i < searchWrapper.length; i++) {
                searchWrapper[i].style.position = 'absolute';
                searchWrapper[i].style.top = '';
            }
        });
    };
    return Hans;
}());
var Kdt = (function () {
    function Kdt() {
        var _this = this;
        this.setKrexx = function (krexx) {
            _this.krexx = krexx;
        };
        this.getParents = function (el, selector) {
            var result = [];
            var parent = el.parentNode;
            var body = document.querySelector('body');
            while (parent !== null) {
                if (parent.matches(selector)) {
                    result.push(parent);
                }
                parent = parent.parentNode;
                if (parent === body) {
                    parent = null;
                }
            }
            return result;
        };
        this.hasClass = function (el, className) {
            if (el.classList) {
                return el.classList.contains(className);
            }
            else {
                return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
            }
        };
        this.findInDomlistByClass = function (elements, className) {
            className = " " + className + " ";
            for (var i = 0; i < elements.length; i++) {
                if ((" " + elements[i].className + " ").replace(/[\n\t]/g, " ").indexOf(className) > -1) {
                    return elements[i];
                }
            }
            return null;
        };
        this.addClass = function (selector, className) {
            var elements;
            if (typeof selector === 'string') {
                elements = document.querySelectorAll(selector);
            }
            else {
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
        this.removeClass = function (selector, className) {
            var elements;
            if (typeof selector === 'string') {
                elements = document.querySelectorAll(selector);
            }
            else {
                elements = selector;
            }
            for (var i = 0; i < elements.length; i++) {
                if (elements[i].classList) {
                    elements[i].classList.remove(className);
                }
                else {
                    elements[i].className = elements[i].className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
                }
            }
        };
        this.toggleClass = function (el, className) {
            if (el.classList) {
                el.classList.toggle(className);
            }
            else {
                var classes = el.className.split(' ');
                var existingIndex = classes.indexOf(className);
                if (existingIndex >= 0) {
                    classes.splice(existingIndex, 1);
                }
                else {
                    classes.push(className);
                }
                el.className = classes.join(' ');
            }
        };
        this.getDataset = function (el, what, mustEscape) {
            if (mustEscape === void 0) { mustEscape = false; }
            var result;
            if (typeof el === 'undefined' ||
                typeof el.getAttribute !== 'function') {
                return '';
            }
            result = el.getAttribute('data-' + what);
            if (result !== null) {
                if (mustEscape === false) {
                    return result;
                }
                else {
                    return result.replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;")
                        .replace(/'/g, "&#039;")
                        .replace('&lt;small&gt;', '<small>')
                        .replace('&lt;/small&gt;', '</small>');
                }
            }
            return '';
        };
        this.setDataset = function (el, what, value) {
            if (typeof el !== 'undefined') {
                el.setAttribute('data-' + what, value);
            }
        };
        this.selectText = function (el) {
            var range = document.createRange();
            var selection = window.getSelection();
            range.selectNodeContents(el);
            selection.removeAllRanges();
            selection.addRange(range);
        };
        this.readSettings = function (cookieName) {
            cookieName = cookieName + "=";
            var cookieArray = document.cookie.split(';');
            var result = '';
            var c;
            for (var i = 0; i < cookieArray.length; i++) {
                c = cookieArray[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(cookieName) === 0) {
                    try {
                        result = JSON.parse(c.substring(cookieName.length, c.length));
                    }
                    catch (error) {
                        result = c.substring(cookieName.length, c.length);
                    }
                }
            }
            return result;
        };
        this.setSetting = function (event) {
            event.preventDefault();
            event.stopPropagation();
            var settings = _this.readSettings('KrexxDebugSettings');
            var newValue = event.target.value.replace('"', '').replace("'", '');
            var valueName = event.target.name.replace('"', '').replace("'", '');
            settings[valueName] = newValue;
            var date = new Date();
            date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
            var expires = 'expires=' + date.toUTCString();
            document.cookie = 'KrexxDebugSettings=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';
            alert(valueName + ' --> ' + newValue + '\n\nPlease reload the page to use the new local settings.');
        };
        this.resetSetting = function (event, element) {
            var settings = {};
            var date = new Date();
            date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
            var expires = 'expires=' + date.toUTCString();
            document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';
            alert('All local configuration have been reset.\n\nPlease reload the page to use the these settings.');
        };
        this.moveToBottom = function (selector) {
            var elements = document.querySelectorAll(selector);
            for (var i = 0; i < elements.length; i++) {
                if (elements[i].parentNode.nodeName.toUpperCase() !== 'BODY') {
                    document.querySelector('body').appendChild(elements[i]);
                }
            }
        };
        this.collapse = function (event, element) {
            event.stop = true;
            var wrapper = _this.getParents(element, '.kwrapper')[0];
            _this.removeClass(wrapper.querySelectorAll('.kfilterroot'), 'kfilterroot');
            _this.removeClass(wrapper.querySelectorAll('.krootline'), 'krootline');
            _this.removeClass(wrapper.querySelectorAll('.ktopline'), 'ktopline');
            if (!_this.hasClass(element, 'kcollapsed')) {
                _this.addClass(_this.getParents(element, 'div.kbg-wrapper > ul'), 'kfilterroot');
                _this.addClass(_this.getParents(element, 'ul.knode, li.kchild'), 'krootline');
                _this.addClass([_this.getParents(element, '.krootline')[0]], 'ktopline');
                _this.removeClass(wrapper.querySelectorAll('.kcollapsed'), 'kcollapsed');
                _this.addClass([element], 'kcollapsed');
            }
            else {
                _this.removeClass(wrapper.querySelectorAll('.kcollapsed'), 'kcollapsed');
            }
            var currentKrexx = _this.krexx;
            setTimeout(function () {
                currentKrexx.jumpTo(element, true);
            }, 100);
        };
    }
    Kdt.prototype.parseJson = function (string) {
        try {
            return JSON.parse(string);
        }
        catch (error) {
            return false;
        }
    };
    return Kdt;
}());
(function () {
    document.addEventListener("DOMContentLoaded", function () {
        var hans = new Hans();
    });
})();
var Search = (function () {
    function Search(eventHandler, jumpTo) {
        var _this = this;
        this.results = [];
        this.stopClickEvents = function () {
            var allSeachWindows = document.querySelectorAll('.ksearch .ksearchfield');
            var i;
            for (i = 0; i < allSeachWindows.length; i++) {
                allSeachWindows[i].addEventListener('click', function (event) {
                    this.focus();
                });
            }
            allSeachWindows = document.querySelectorAll('.ksearch');
            for (i = 0; i < allSeachWindows.length; i++) {
                allSeachWindows[i].addEventListener('mousedown', function (event) {
                    event.stopPropagation();
                });
            }
        };
        this.displaySearch = function (event, element) {
            var instance = _this.kdt.getDataset(element, 'instance');
            var search = document.querySelector('#search-' + instance);
            var viewportOffset;
            if (_this.kdt.hasClass(search, 'hidden')) {
                _this.kdt.toggleClass(search, 'hidden');
                search.querySelector('.ksearchfield').focus();
                search.style.position = 'absolute';
                search.style.top = '';
                viewportOffset = search.getBoundingClientRect();
                search.style.position = 'fixed';
                search.style.top = viewportOffset.top + 'px';
            }
            else {
                _this.kdt.toggleClass(search, 'hidden');
                _this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
                search.style.position = 'absolute';
                search.style.top = '';
                _this.results = [];
            }
        };
        this.clearSearch = function (event) {
            _this.results[_this.kdt.getDataset(event.target, 'instance')] = [];
        };
        this.displaySearchOptions = function (event, element) {
            _this.kdt.toggleClass(element.parentNode.nextElementSibling, 'khidden');
        };
        this.performSearch = function (event, element) {
            _this.kdt.addClass([element.parentNode.nextElementSibling], 'khidden');
            var config = new SearchConfig();
            config.searchtext = element.parentNode.querySelector('.ksearchfield').value;
            config.caseSensitive = element.parentNode.parentNode.querySelector('.ksearchcase').checked;
            config.searchKeys = element.parentNode.parentNode.querySelector('.ksearchkeys').checked;
            config.searchShort = element.parentNode.parentNode.querySelector('.ksearchshort').checked;
            config.searchLong = element.parentNode.parentNode.querySelector('.ksearchlong').checked;
            config.searchWhole = element.parentNode.parentNode.querySelector('.ksearchwhole').checked;
            if (config.caseSensitive === false) {
                config.searchtext = config.searchtext.toLowerCase();
            }
            if (config.searchtext.length === 0) {
                element.parentNode.querySelector('.ksearch-state').textContent = '<- Please enter a search text.';
                return;
            }
            if (config.searchtext.length > 2 || config.searchWhole) {
                config.instance = _this.kdt.getDataset(element, 'instance');
                var direction = _this.kdt.getDataset(element, 'direction');
                config.payload = document.querySelector('#' + config.instance + ' .kbg-wrapper');
                var collapsed = config.payload.querySelectorAll('.kcollapsed');
                for (var i = 0; i < collapsed.length; i++) {
                    _this.eventHandler.triggerEvent(collapsed[i], 'click');
                }
                if (typeof _this.results[config.instance] !== "undefined") {
                    if (typeof _this.results[config.instance][config.searchtext] === "undefined") {
                        _this.refreshResultlist(config);
                    }
                }
                else {
                    _this.refreshResultlist(config);
                }
                if (direction === 'forward') {
                    _this.results[config.instance][config.searchtext]['pointer']++;
                }
                else {
                    _this.results[config.instance][config.searchtext]['pointer']--;
                }
                if (typeof _this.results[config.instance][config.searchtext]['data'][_this.results[config.instance][config.searchtext]['pointer']] === "undefined") {
                    if (direction === 'forward') {
                        _this.results[config.instance][config.searchtext]['pointer'] = 0;
                    }
                    else {
                        _this.results[config.instance][config.searchtext]['pointer'] = _this.results[config.instance][config.searchtext]['data'].length - 1;
                    }
                }
                element.parentNode.querySelector('.ksearch-state').textContent =
                    (_this.results[config.instance][config.searchtext]['pointer'] + 1) + ' / ' + (_this.results[config.instance][config.searchtext]['data'].length);
                if (typeof _this.results[config.instance][config.searchtext]['data'][_this.results[config.instance][config.searchtext]['pointer']] !== 'undefined') {
                    _this.jumpTo(_this.results[config.instance][config.searchtext]['data'][_this.results[config.instance][config.searchtext]['pointer']]);
                }
            }
            else {
                element.parentNode.querySelector('.ksearch-state').textContent = '<- must be bigger than 3 characters';
            }
        };
        this.refreshResultlist = function (config) {
            _this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
            var selector = [];
            if (config.searchKeys === true) {
                selector.push('li.kchild span.kname');
            }
            if (config.searchShort === true) {
                selector.push('li.kchild span.kshort');
            }
            if (config.searchLong === true) {
                selector.push('li div.kpreview');
            }
            _this.results[config.instance] = [];
            _this.results[config.instance][config.searchtext] = [];
            _this.results[config.instance][config.searchtext]['data'] = [];
            _this.results[config.instance][config.searchtext]['pointer'] = [];
            if (selector.length > 0) {
                var list = void 0;
                list = config.payload.querySelectorAll(selector.join(', '));
                var textContent = '';
                for (var i = 0; i < list.length; ++i) {
                    textContent = list[i].textContent;
                    if (config.caseSensitive === false) {
                        textContent = textContent.toLowerCase();
                    }
                    if (config.searchWhole) {
                        if (textContent === config.searchtext) {
                            _this.kdt.toggleClass(list[i], 'ksearch-found-highlight');
                            _this.results[config.instance][config.searchtext]['data'].push(list[i]);
                        }
                    }
                    else {
                        if (textContent.indexOf(config.searchtext) > -1) {
                            _this.kdt.toggleClass(list[i], 'ksearch-found-highlight');
                            _this.results[config.instance][config.searchtext]['data'].push(list[i]);
                        }
                    }
                }
            }
            _this.results[config.instance][config.searchtext]['pointer'] = -1;
        };
        this.searchfieldReturn = function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (event.which !== 13) {
                return;
            }
            _this.eventHandler.triggerEvent(event.target.parentNode.querySelectorAll('.ksearchnow')[1], 'click');
        };
        this.kdt = new Kdt();
        this.eventHandler = eventHandler;
        this.jumpTo = jumpTo;
        this.eventHandler.addEvent('.ksearchcase', 'change', this.clearSearch);
        this.eventHandler.addEvent('.ksearchkeys', 'change', this.clearSearch);
        this.eventHandler.addEvent('.ksearchshort', 'change', this.clearSearch);
        this.eventHandler.addEvent('.ksearchlong', 'change', this.clearSearch);
        this.eventHandler.addEvent('.ksearchwhole', 'change', this.clearSearch);
        this.eventHandler.addEvent('.koptions', 'click', this.displaySearchOptions);
        this.eventHandler.addEvent('.kwrapper .ksearchfield', 'keyup', this.searchfieldReturn);
        this.stopClickEvents();
    }
    return Search;
}());
var SearchConfig = (function () {
    function SearchConfig() {
    }
    return SearchConfig;
}());
