'use strict';



/**
 * Toolkit for ajax around a form
 */
class Formajax {
    /** Message send to a callback loader to say end of file */
    static EOF = '\u000A';
    /** Used as a separator between mutiline <div> */
    static LF = '&#10;';
    /** {HTMLFormElement} form with params to send for queries like conc */
    form;
    /** Attach a form to get values */
    constructor(form) {
        this.form = form;
    }

    /**
     * Get URL and send line by line to a callback function.
     * “Line” separator could be configured with any string,
     * this allow to load multiline html chunks 
     * 
     * @param {String} url 
     * @param {function} callback 
     * @returns 
     */
    static loadLines(div, url, callback, sep = '\n') {
        return new Promise(function (resolve, reject) {
            if (div.xhr) { // still loading, abort
                div.xhr.abort();
                delete div.xhr;
            }
            var xhr = new XMLHttpRequest();
            div.xhr = xhr;
            var start = 0;
            xhr.onprogress = function () {
                // loop on separator
                var end;
                while ((end = xhr.response.indexOf(sep, start)) >= 0) {
                    callback(xhr.response.slice(start, end));
                    start = end + sep.length;
                }
            };
            xhr.onload = function () {
                let part = xhr.response.slice(start);
                if (part.trim()) callback(part);
                // last, send a message to callback
                callback(Formajax.EOF);
                resolve();
            };
            xhr.onerror = function () {
                reject(Error('Connection failed'));
            };
            xhr.responseType = 'text';
            xhr.open('GET', url);
            xhr.send();
        });
    }


    /**
     * Load Json complete, not in slices
     * @param {*} url 
     * @param {*} callback 
     */
    static loadJson(url, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.responseType = 'json';
        xhr.onload = function () {
            var status = xhr.status;
            if (status === 200) {
                callback(xhr.response, null);
            } else { // in case of error ?
                callback(xhr.response, status);
            }
        };
        xhr.send();
    }

    /**
     * Get form values as url pars
     */
    static formQuery(form, include, exclude) {
        // ensure array
        if (!include);
        else if (!Array.isArray(include)) include = [include];
        if (!exclude);
        else if (!Array.isArray(exclude)) exclude = [exclude];


        const formData = new FormData(form);
        // delete empty values for nice minimal url
        // take a copy of keys, formData.keys will change
        const keys = Array.from(formData.keys());
        for (const key of keys) {
            if (include && !include.find(k => k === key)) {
                formData.delete(key);
                continue;
            }
            if (exclude && exclude.find(k => k === key)) {
                formData.delete(key);
                continue;
            }
            // 1) delete, 2) append non empty
            let values = formData.getAll(key);
            formData.delete(key);
            const len = values.length;
            if (len < 1) continue;
            for (let i = 0; i < len; i++) {
                if (!values[i]) continue;
                formData.append(key, values[i]);
            }
        }
        return new URLSearchParams(formData);
    }

    /**
     * Intitialize an input with suggest
     * @param {HTMLInputElement} input 
     * @returns 
     */
    static suggestInit(input) {
        if (!input) {
            console.log("[Formajax] No <input> to equip");
            return;
        }
        if (input.list) { // create a list
            console.log("[Formajax] <datalist> is bad for filtering\n" + input);
        }
        if (!input.dataset.url) {
            console.log("[Formajax] No @data-url to get data from\n" + input);
            return;
        }
        if (!input.dataset.name) {
            console.log("[Formajax] No @data-name to create params\n" + input);
            return;
        }
        input.autocomplete = 'off';
        // create suggest
        const suggest = document.createElement("div");
        suggest.className = "suggest " + input.dataset.name;
        input.parentNode.insertBefore(suggest, input.nextSibling);
        input.suggest = suggest;
        suggest.input = input;
        suggest.hide = suggestHide;
        suggest.show = suggestShow;
        // global click hide current suggest
        window.addEventListener('click', (e) => {
            if (window.suggest) window.suggest.hide();
        });
        // click in suggest, avoid hide effect at body level
        input.parentNode.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        // control suggests, 
        input.addEventListener('click', function (e) {
            if (suggest.style.display != 'block') {
                suggest.show();
            } else {
                suggest.hide();
            }
        });

        input.addEventListener('click', suggestFill);
        input.addEventListener('input', suggestFill);
        input.addEventListener('input', function (e) { suggest.show(); });

        suggest.addEventListener("touchstart", function (e) {
            // si on défile la liste de résultats sur du tactile, désafficher le clavier
            input.blur();
        });
        input.addEventListener('keyup', function (e) {
            e = e || window.event;
            if (e.key == 'Esc' || e.key == 'Escape') {
                suggest.hide();
            } else if (e.key == 'Backspace') {
                if (input.value) return;
                suggest.hide();
            } else if (e.key == 'ArrowDown') {
                if (input.value) return;
                suggest.show();
            } else if (e.key == 'ArrowUp') {
                // focus ?
            }
        });
    }



    /**
     * Append a line record to a suggest
     * @param {HTMLDivElement} suggest block where to append suggestions 
     * @param {*} line 
     */
    static suggestLine(suggest, json) {
        if (!json.trim()) { // sometimes empty
            return;
        }
        try {
            var data = JSON.parse(json);
        } catch (err) {
            console.log(Error('parsing: "' + json + "\"\n" + err));
            return;
        }
        // maybe meta
        if (!data.text || !data.id) {
            return;
        }

        let facet = document.createElement('div');
        facet.className = "facet";
        const hits = (data.hits) ? " (" + data.hits + ")" : "";
        if (data.html) {
            facet.innerHTML = data.html + hits;
        } else if (data.text) {
            facet.innerHTML = data.text + hits;
        } else { // ?? bad !
            facet.innerHTML = data.id + hits;
        }
        facet.dataset.id = data.id;
        facet.addEventListener('click', facetPush);
        facet.input = suggest.input;
        suggest.appendChild(facet);
    }

    /**
     * Start population of a suggester 
     * @param {Event} e 
     */
    static suggestFill(e) {
        const input = e.currentTarget;
        const suggest = input.suggest;
        // get forms params
        const formData = new FormData(input.form);
        const pars = new URLSearchParams(formData);
        pars.set("glob", input.value); // add the suggest query

        // search form sender and receiver
        const url = input.dataset.url + "?" + pars;
        suggest.innerText = '';
        this.loadLines(suggest, url, function (json) {
            this.suggestLine(suggest, json);
        });
    }

    /**
     * Delete an hidden field
     * @param {Event} e 
     */
    static inputDel(e) {
        const label = e.currentTarget.parentNode;
        label.parentNode.removeChild(label);
        update(true);
    }

    /**
     * Push a value for a facet
     * @param {Event} e 
     */
    static facetPush(e) {
        const facet = e.currentTarget;
        const label = document.createElement("label");
        label.className = 'facet';
        const a = document.createElement("a");
        a.innerText = '🞭';
        a.className = 'inputDel';
        a.addEventListener('click', inputDel);
        label.appendChild(a);
        const input = document.createElement("input");
        input.name = facet.input.dataset.name;
        input.type = 'hidden';
        input.value = facet.dataset.id;
        label.appendChild(input);
        const text = document.createTextNode(facet.textContent.replace(/ *\(\d+\) *$/, ''));
        label.appendChild(text);
        facet.input.parentNode.insertBefore(label, facet.input);
        facet.input.focus();
        facet.input.suggest.hide();
        update(true); // update interface
    }

    /**
     * Attached to a suggest pannel, hide
     */
    static suggestHide() {
        const suggest = this;
        suggest.blur();
        suggest.style.display = 'none';
        suggest.input.value = '';
        window.suggest = null;
    }

    /**
     * Attached to a suggest pannel, show
     */
    static suggestShow() {
        const suggest = this;
        if (window.suggest && window.suggest != suggest) {
            window.suggest.hide();
        }
        window.suggest = suggest;
        suggest.style.display = 'block';
    }

    static loadHtml(div, url, append = false)
    {
        if (!url) return; // log an error ?
        if (!div) return; // disappeared ?
        if (!append) {
            div.innerText = '';
        }
        this.loadLines(div, url, function (html) {
            if (!div) { // what ?
                return false;
            }
            // last line, liberate div for next load
            if (html == Formajax.EOF) {
                div.loading = false;
                return;
            }
            if (!html) { // always end, or pb ?
                return;
            }
            div.insertAdjacentHTML('beforeend', html);
        }, Formajax.LF);
    }

    static selfOrAncestor(el, name) {
        while (el.tagName.toLowerCase() != name) {
            el = el.parentNode;
            if (!el) return false;
            let tag = el.tagName.toLowerCase();
            if (tag == 'div' || tag == 'nav' || tag == 'body') return false;
        }
        return el;
    }
};

// init lemma column
(function() {
    const form = document.forms['lemmas'];
    if (!form) return;
    const lemmas = document.getElementById('lemmas');
    // no div to populate.
    if (!lemmas) return;
    const article =  document.getElementById('article');
    if (!article) return;
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const url = form.action + "?" + Formajax.formQuery(form);
        Formajax.loadHtml(lemmas, url);
        return false;
    }, true);
    // send submit when suggest change
    form.form.addEventListener('input', (e) => {
        form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
    }, true);
    // attach event to lemmas container
    lemmas.addEventListener('click', (e) => {
        let a = Formajax.selfOrAncestor(e.target, 'a');
        if (!a) return;
        if (!a.classList.contains('lemma')) return;
        e.preventDefault();
        // load url
        const url = 'article/' + a.getAttribute('href');
        Formajax.loadHtml(article, url);
    });
})();

/**
 * Behaviours of tabs
 */
function tab(a) {
    // desactivate last tab and hilite current
    var indicar = document.getElementById('indicar');
    if (indicar) indicar.className = "";
    var inverso = document.getElementById('inverso');
    if (inverso) inverso.className = "";
    a.className = "active";
    // inform server a new tab has been chosen
    Cookie.set("tab", a.id);
    // input, be careful to IE id model, use var
    var q = document.getElementById('q');
    if (!q) return;
    if (a.id == 'inverso') {
        q.className = "inverso";
    }
    else {
        // was inverso, reverse it
        q.className = "";
    }
    a.href = a.href.replace(/\/[^\/\?]*$/, '/' + q.value);
    if (!a.target) return true;
    window.frames[a.target].location.replace(a.href);
    return false;
}
/**
 * Load lemmas in nav column
 */
