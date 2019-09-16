var Draxx = (function () {
    function Draxx(selector, handle, callbackUp, callbackDrag) {
        var _this = this;
        if (callbackUp === void 0) { callbackUp = null; }
        if (callbackDrag === void 0) { callbackDrag = null; }
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
        this.selector = selector;
        this.callbackUp = callbackUp;
        this.callbackDrag = callbackDrag;
        this.kdt = new Kdt();
        var elements = document.querySelectorAll(selector);
        for (var i = 0; i < elements.length; i++) {
            elements[i].addEventListener('mousedown', this.startDraxx);
        }
    }
    Draxx.prototype.moveToViewport = function (selector) {
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
    Draxx.prototype.getElementOffset = function (element) {
        var de = document.documentElement;
        var box = element.getBoundingClientRect();
        var top = box.top + window.pageYOffset - de.clientTop;
        var left = box.left + window.pageXOffset - de.clientLeft;
        return { top: top, left: left };
    };
    Draxx.prototype.outerWidth = function (element) {
        var width = element.offsetWidth;
        var style = getComputedStyle(element);
        width += parseInt(style.marginLeft, 10) + parseInt(style.marginRight, 10);
        return width;
    };
    return Draxx;
}());
var Eventhandler = (function () {
    function Eventhandler(selector) {
        this.storage = [];
        this.kdt = new Kdt();
        var elements = document.querySelectorAll(selector);
        for (var i = 0; i < elements.length; i++) {
            elements[i].addEventListener('click', this.handle);
        }
    }
    Eventhandler.prototype.addEvent = function (selector, eventName, callBack) {
        if (eventName === 'click') {
            this.addToStorage(selector, callBack);
        }
        else {
            var elements = document.querySelectorAll(selector);
            for (var i = 0; i < elements.length; i++) {
                elements[i].addEventListener(eventName, function () { return callBack; });
            }
        }
    };
    Eventhandler.prototype.preventBubble = function (event) {
        event.stop = true;
    };
    Eventhandler.prototype.addToStorage = function (selector, callback) {
        if (!(selector in this.storage)) {
            this.storage[selector] = [];
        }
        this.storage[selector].push(callback);
    };
    Eventhandler.prototype.handle = function (event) {
        event.stopPropagation();
        event.stop = false;
        var element = event.target;
        var selector;
        var i;
        var callbackArray = [];
        do {
            for (selector in this.storage) {
                if (element.matches(selector)) {
                    callbackArray = this.storage[selector];
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
    Eventhandler.prototype.triggerEvent = function (el, eventName) {
        var event = document.createEvent('HTMLEvents');
        event.initEvent(eventName, true, false);
        el.dispatchEvent(event);
    };
    return Eventhandler;
}());
var Hans = (function () {
    function Hans() {
        this.kdt = new Kdt();
        this.kdt.setKrexx(this);
        this.eventHandler = new Eventhandler('.kwrapper.kouterwrapper, .kfatalwrapper-outer');
        this.search = new Search(this.eventHandler, this.jumpTo);
        this.kdt.moveToBottom('.kouterwrapper');
        this.initDraxx();
        this.eventHandler.addEvent('.kwrapper .kheadnote-wrapper .kclose, .kwrapper .kfatal-headnote .kclose', 'click', this.close);
        this.eventHandler.addEvent('.kwrapper .kexpand', 'click', this.toggle);
        this.eventHandler.addEvent('.ktool-tabs .ktab:not(.ksearchbutton)', 'click', this.switchTab);
        this.eventHandler.addEvent('.kwrapper .keditable select, .kwrapper .keditable input:not(.ksearchfield)', 'change', this.kdt.setSetting);
        this.eventHandler.addEvent('.kwrapper .kresetbutton', 'click', this.kdt.resetSetting);
        this.eventHandler.addEvent('.kwrapper .kcopyFrom', 'click', this.copyFrom);
        this.eventHandler.addEvent('.kwrapper .ksearchbutton, .kwrapper .ksearch .kclose', 'click', this.search.displaySearch);
        this.eventHandler.addEvent('.kwrapper .ksearchnow', 'click', this.search.performSearch);
        this.eventHandler.addEvent('.kwrapper .ksearchfield', 'keyup', this.search.searchfieldReturn);
        this.eventHandler.addEvent('.kwrapper .kolps', 'click', this.kdt.collapse);
        this.eventHandler.addEvent('.kwrapper .kgencode', 'click', this.generateCode);
        this.eventHandler.addEvent('.kodsp', 'click', this.eventHandler.preventBubble);
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
    Hans.prototype.copyFrom = function (event, element) {
        var i;
        var domid = this.kdt.getDataset(element, 'domid');
        var orgNest = document.querySelector('#' + domid);
        if (orgNest) {
            var orgEl = orgNest.previousElementSibling;
            element.parentNode.insertBefore(orgNest.cloneNode(true), element.nextSibling);
            var newEl = orgEl.cloneNode(true);
            element.parentNode.insertBefore(newEl, element.nextSibling);
            this.kdt.findInDomlistByClass(newEl.children, 'kname').innerHTML = this.kdt.findInDomlistByClass(element.children, 'kname').innerHTML;
            var allChildren = newEl.nextElementSibling.getElementsByTagName("*");
            for (i = 0; i < allChildren.length; i++) {
                allChildren[i].removeAttribute('id');
            }
            newEl.nextElementSibling.removeAttribute('id');
            this.kdt.setDataset(newEl.parentNode, 'domid', domid);
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
    Hans.prototype.toggle = function (event, element) {
        this.kdt.toggleClass(element, 'kopened');
        this.kdt.toggleClass(element.nextElementSibling, 'khidden');
    };
    Hans.prototype.jumpTo = function (el, noHighlight) {
        var nests = this.kdt.getParents(el, '.knest');
        var container;
        var destination;
        var diff;
        var step;
        this.kdt.removeClass(nests, 'khidden');
        for (var i = 0; i < nests.length; i++) {
            this.kdt.addClass([nests[i].previousElementSibling], 'kopened');
        }
        if (noHighlight !== true) {
            this.kdt.removeClass('.highlight-jumpto', 'highlight-jumpto');
            this.kdt.addClass([el], 'highlight-jumpto');
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
    Hans.prototype.close = function (event, element) {
        var instance = this.kdt.getDataset(element, 'instance');
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
    Hans.prototype.disableForms = function () {
        var elements = document.querySelectorAll('.kwrapper .keditable input, .kwrapper .keditable select');
        for (var i = 0; i < elements.length; i++) {
            elements[i].disabled = true;
        }
    };
    Hans.prototype.generateCode = function (event, element) {
        event.stop = true;
        var codedisplay = element.nextElementSibling;
        var resultArray = [];
        var resultString = '';
        var sourcedata;
        var domid;
        var wrapperLeft = '';
        var wrapperRight = '';
        var el = this.kdt.getParents(element, 'li.kchild')[0];
        while (el) {
            domid = this.kdt.getDataset(el, 'domid');
            sourcedata = this.kdt.getDataset(el, 'source');
            wrapperLeft = this.kdt.getDataset(el, 'codewrapperLeft');
            wrapperRight = this.kdt.getDataset(el, 'codewrapperRight');
            if (sourcedata === '. . .') {
                if (domid !== '') {
                    el = document.querySelector('#' + domid).parentNode;
                    resultArray.push(this.kdt.getDataset(el, 'source'));
                }
            }
            if (sourcedata !== '') {
                resultArray.push(sourcedata);
            }
            el = this.kdt.getParents(el, 'li.kchild')[0];
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
            this.kdt.selectText(codedisplay);
        }
        else {
            codedisplay.style.display = 'none';
        }
    };
    Hans.prototype.switchTab = function (event, element) {
        var instance = this.kdt.getDataset(element.parentNode, 'instance');
        var what = this.kdt.getDataset(element, 'what');
        this.kdt.removeClass('#' + instance + ' .kactive:not(.ksearchbutton)', 'kactive');
        if (element.classList) {
            element.classList.add('kactive');
        }
        else {
            element.className += ' kactive';
        }
        this.kdt.addClass('#' + instance + ' .kpayload', 'khidden');
        this.kdt.removeClass('#' + instance + ' .' + what, 'khidden');
    };
    Hans.prototype.setPayloadMaxHeight = function () {
        var height = Math.round(Math.max(document.documentElement.clientHeight, window.innerHeight || 0) * 0.60);
        if (height > 0) {
            var elements = document.querySelectorAll('.krela-wrapper .kpayload');
            for (var i = 0; i < elements.length; i++) {
                elements[i].style.maxHeight = height + 'px';
            }
        }
    };
    Hans.prototype.checkSearchInViewport = function () {
        var search = document.querySelector('.kfatalwrapper-outer .search-wrapper');
        search.style.position = '';
        search.style.top = '';
        var rect = search.getBoundingClientRect();
        if (rect.top < 0) {
            search.style.position = 'fixed';
            search.style.top = '0px';
        }
    };
    return Hans;
}());
var Kdt = (function () {
    function Kdt() {
    }
    Kdt.prototype.setKrexx = function (krexx) {
        this.krexx = krexx;
    };
    Kdt.prototype.getParents = function (el, selector) {
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
    Kdt.prototype.hasClass = function (el, className) {
        if (el.classList) {
            return el.classList.contains(className);
        }
        else {
            return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
        }
    };
    Kdt.prototype.findInDomlistByClass = function (elements, className) {
        className = " " + className + " ";
        for (var i = 0; i < elements.length; i++) {
            if ((" " + elements[i].className + " ").replace(/[\n\t]/g, " ").indexOf(className) > -1) {
                return elements[i];
            }
        }
        return null;
    };
    Kdt.prototype.addClass = function (selector, className) {
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
    Kdt.prototype.removeClass = function (selector, className) {
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
    Kdt.prototype.toggleClass = function (el, className) {
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
    Kdt.prototype.getDataset = function (el, what, mustEscape) {
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
    Kdt.prototype.setDataset = function (el, what, value) {
        if (typeof el !== 'undefined') {
            el.setAttribute('data-' + what, value);
        }
    };
    Kdt.prototype.selectText = function (el) {
        var range = document.createRange();
        var selection = window.getSelection();
        range.selectNodeContents(el);
        selection.removeAllRanges();
        selection.addRange(range);
    };
    Kdt.prototype.readSettings = function (cookieName) {
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
    Kdt.prototype.setSetting = function (event) {
        event.preventDefault();
        event.stopPropagation();
        var settings = this.readSettings('KrexxDebugSettings');
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
    Kdt.prototype.resetSetting = function (event, element) {
        var settings = {};
        var date = new Date();
        date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
        var expires = 'expires=' + date.toUTCString();
        document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';
        alert('All local configuration have been reset.\n\nPlease reload the page to use the these settings.');
    };
    Kdt.prototype.parseJson = function (string) {
        try {
            return JSON.parse(string);
        }
        catch (error) {
            return false;
        }
    };
    Kdt.prototype.moveToBottom = function (selector) {
        var elements = document.querySelectorAll(selector);
        for (var i = 0; i < elements.length; i++) {
            if (elements[i].parentNode.nodeName.toUpperCase() !== 'BODY') {
                document.querySelector('body').appendChild(elements[i]);
            }
        }
    };
    Kdt.prototype.collapse = function (event, element) {
        event.stop = true;
        var wrapper = this.getParents(element, '.kwrapper')[0];
        this.removeClass(wrapper.querySelectorAll('.kfilterroot'), 'kfilterroot');
        this.removeClass(wrapper.querySelectorAll('.krootline'), 'krootline');
        this.removeClass(wrapper.querySelectorAll('.ktopline'), 'ktopline');
        if (!this.hasClass(element, 'kcollapsed')) {
            this.addClass(this.getParents(element, 'div.kbg-wrapper > ul'), 'kfilterroot');
            this.addClass(this.getParents(element, 'ul.knode, li.kchild'), 'krootline');
            this.addClass([this.getParents(element, '.krootline')[0]], 'ktopline');
            this.removeClass(wrapper.querySelectorAll('.kcollapsed'), 'kcollapsed');
            this.addClass([element], 'kcollapsed');
        }
        else {
            this.removeClass(wrapper.querySelectorAll('.kcollapsed'), 'kcollapsed');
        }
        var currentKrexx = this.krexx;
        setTimeout(function () {
            currentKrexx.jumpTo(element, true);
        }, 100);
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
        this.results = [];
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
    }
    Search.prototype.displaySearch = function (event, element) {
        var instance = this.kdt.getDataset(element, 'instance');
        var search = document.querySelector('#search-' + instance);
        var viewportOffset;
        if (this.kdt.hasClass(search, 'hidden')) {
            this.kdt.toggleClass(search, 'hidden');
            search.querySelector('.ksearchfield').focus();
            search.style.position = 'absolute';
            search.style.top = '';
            viewportOffset = search.getBoundingClientRect();
            search.style.position = 'fixed';
            search.style.top = viewportOffset.top + 'px';
        }
        else {
            this.kdt.toggleClass(search, 'hidden');
            this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
            search.style.position = 'absolute';
            search.style.top = '';
            this.results = [];
        }
    };
    Search.prototype.clearSearch = function (event) {
        this.results[this.kdt.getDataset(event.target, 'instance')] = [];
    };
    Search.prototype.displaySearchOptions = function (event, element) {
        this.kdt.toggleClass(element.parentNode.nextElementSibling, 'khidden');
    };
    Search.prototype.performSearch = function (event, element) {
        this.kdt.addClass([element.parentNode.nextElementSibling], 'khidden');
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
            config.instance = this.kdt.getDataset(element, 'instance');
            var direction = this.kdt.getDataset(element, 'direction');
            var payload = document.querySelector('#' + config.instance + ' .kbg-wrapper');
            var collapsed = payload.querySelectorAll('.kcollapsed');
            for (var i = 0; i < collapsed.length; i++) {
                this.eventHandler.triggerEvent(collapsed[i], 'click');
            }
            if (typeof this.results[config.instance] !== "undefined") {
                if (typeof this.results[config.instance][config.searchtext] === "undefined") {
                    this.refreshResultlist(config);
                }
            }
            else {
                this.refreshResultlist(config);
            }
            if (direction === 'forward') {
                this.results[config.instance][config.searchtext]['pointer']++;
            }
            else {
                this.results[config.instance][config.searchtext]['pointer']--;
            }
            if (typeof this.results[config.instance][config.searchtext]['data'][this.results[config.instance][config.searchtext]['pointer']] === "undefined") {
                if (direction === 'forward') {
                    this.results[config.instance][config.searchtext]['pointer'] = 0;
                }
                else {
                    this.results[config.instance][config.searchtext]['pointer'] = this.results[config.instance][config.searchtext]['data'].length - 1;
                }
            }
            element.parentNode.querySelector('.ksearch-state').textContent =
                (this.results[config.instance][config.searchtext]['pointer'] + 1) + ' / ' + (this.results[config.instance][config.searchtext]['data'].length);
            if (typeof this.results[config.instance][config.searchtext]['data'][this.results[config.instance][config.searchtext]['pointer']] !== 'undefined') {
                this.jumpTo(this.results[config.instance][config.searchtext]['data'][this.results[config.instance][config.searchtext]['pointer']]);
            }
        }
        else {
            element.parentNode.querySelector('.ksearch-state').textContent = '<- must be bigger than 3 characters';
        }
    };
    Search.prototype.refreshResultlist = function (config) {
        this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
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
        this.results[config.instance] = [];
        this.results[config.instance][config.searchtext] = [];
        this.results[config.instance][config.searchtext]['data'] = [];
        this.results[config.instance][config.searchtext]['pointer'] = [];
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
                        this.kdt.toggleClass(list[i], 'ksearch-found-highlight');
                        this.results[config.instance][config.searchtext]['data'].push(list[i]);
                    }
                }
                else {
                    if (textContent.indexOf(config.searchtext) > -1) {
                        this.kdt.toggleClass(list[i], 'ksearch-found-highlight');
                        this.results[config.instance][config.searchtext]['data'].push(list[i]);
                    }
                }
            }
        }
        this.results[config.instance][config.searchtext]['pointer'] = -1;
    };
    Search.prototype.searchfieldReturn = function (event) {
        event.preventDefault();
        event.stopPropagation();
        if (event.which !== 13) {
            return;
        }
        this.eventHandler.triggerEvent(event.target.parentNode.querySelectorAll('.ksearchnow')[1], 'click');
    };
    return Search;
}());
var SearchConfig = (function () {
    function SearchConfig() {
    }
    return SearchConfig;
}());
//# sourceMappingURL=krexx.js.map