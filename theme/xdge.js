'use strict';

const lat_grc = {
    "A": "Α",
    "a": "α",
    "B": "Β",
    "b": "β",
    "C": "Ξ",
    "c": "ξ",
    "D": "Δ",
    "d": "δ",
    "E": "Ε",
    "e": "ε",
    "Ê": "Η",
    "ê": "η",
    "é": "η",
    "è": "η",
    "F": "Φ",
    "f": "φ",
    "G": "Γ",
    "g": "γ",
    "H": "Η",
    "h": "η",
    "I": "Ι",
    "i": "ι",
    "J": "Σ",
    "j": "ς",
    "K": "Κ",
    "k": "κ",
    "L": "Λ",
    "l": "λ",
    "M": "Μ",
    "m": "μ",
    "N": "Ν",
    "n": "ν",
    "O": "Ο",
    "o": "ο",
    "Ô": "Ω",
    "ô": "ω",
    "P": "Π",
    "p": "π",
    "Q": "Θ",
    "q": "θ",
    "R": "Ρ",
    "r": "ρ",
    "S": "Σ",
    "s": "σ",
    "T": "Τ",
    "t": "τ",
    "U": "Υ",
    "u": "υ",
    "V": "Υ",
    "v": "υ",
    "w": "ω",
    "W": "Ω",
    "x": "χ",
    "X": "Χ",
    "y": "ψ",
    "Y": "Ψ",
    "Z": "Ζ",
    "z": "ζ"
};

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
     * @param {URL} url 
     * @param {function} callback 
     * @returns 
     */
    static loadLines(url, div, callback, sep = '\n') {
        return new Promise(function (resolve, reject) {
            if (typeof url === 'string') {
                url = new URL(url, document.location);
            }
            if (div.xhr) { 
                // already loading something
                /*
                // do not abort, this could be an effect of bad events, like onscroll
                div.xhr.abort();
                delete div.xhr;
                */
                // shall we queue or log something ?
                return false;
            }
            let xhr = new XMLHttpRequest();
            xhr.url = url;
            div.xhr = xhr;
            let indexStart = 0;
            xhr.onprogress = function () {
                let indexEnd;
                while ((indexEnd = xhr.response.indexOf(sep, indexStart)) >= 0) {
                    callback(xhr.response.slice(indexStart, indexEnd));
                    indexStart = indexEnd + sep.length;
                }
            };
            xhr.onload = function () {
                let part = xhr.response.slice(indexStart);
                if (part.trim()) callback(part);
                // last, send a message to callback
                callback(Formajax.EOF);
                delete div.xhr;
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

    /**
     * Load html from url
     * 
     * @param {URL} url source url for html
     * @param {Element} div destination container for html
     * @param {string} adjacent  afterbegin|beforeend, see insertAdjacentHTML
     * @param {callback} onload apply things when load is finished
     * @returns 
     */
    static loadHtml(url, div, adjacent = false, onload = null) {
        // May be optimized with streamHTML, BUT

        if (!url) return; // log an error ?
        if (!div) return; // disappeared ?
        // maybe repeated call like with onscroll event
        if (div.loading) return;
        div.loading = true;
        if (!adjacent) { // no append or prepend
            div.innerText = '';
            adjacent = 'beforeend';
        }
        // already loading
        fetch(url).then(function (response) {
            return response.text();
        }).then(function (html) {
            div.loading = false;
            div.insertAdjacentHTML(adjacent, html);
            if (onload) onload();
        }).catch(function (err) {
            div.loading = false;
            // There was an error
            console.warn('loadHtml error ' + url, err);
        });
    }
    
    /**
     * Streaming html is not OK afterbegin, is not used for lemmas
     * Use for big article or search results
     * @param {*} url 
     * @param {*} div 
     * @param {*} onload 
     */
    static streamHtml(url, div, onload) {
        if (!url) return; // log an error ?
        if (!div) return; // disappeared ?
        // maybe repeated call like with onscroll event
        if (div.loading) return;
        div.loading = true;
        const consume = responseReader => {
            return responseReader.read().then(result => {
                if (result.done) {
                    div.loading = false;
                    if (onload) onload();
                    return; 
                }
                const chunk = result.value;
                div.insertAdjacentHTML('beforeend', chunk);
                return consume(responseReader);
            });
        }
        fetch(url).then(response => {
            return consume(response.body.getReader());
        })
        .catch(function (err) {
            div.loading = false;
            console.warn('loadHtml error ' + url, err);
        });

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
(function () {
    function latGrc(text) {
        const chars = text.split('');
        for (let i = 0, len = text.length; i < len; i++) {
            let c = lat_grc[chars[i]];
            if (c) chars[i] = c;
        }
        return chars.join('');
    }
    // url parameters of page
    const pars = new URLSearchParams(location.search);

    const sugerir = document.forms['sugerir'];
    if (!sugerir) return;
    const inverso = sugerir['inverso'];
    const busqueda = document.forms['busqueda'];
    const lemmas = document.getElementById('lemmas');
    // no div to populate.
    if (!lemmas) return;
    const main = document.getElementById('main');
    if (!main) return;
    sugerir.addEventListener('submit', (e) => {
        e.preventDefault();
        const url = sugerir.action + "?" + Formajax.formQuery(sugerir);
        Formajax.loadHtml(url, lemmas, null, (e) => {
            // scroll active into view onload
            const a = lemmas.querySelector("a.lemma.active");
            if (a) {
                a.scrollIntoView({  block: "start" });
            }
        });
        return false;
    }, true);
    // send submit when suggest change
    sugerir.form.addEventListener('input', (e) => {
        sugerir.form.value = latGrc(sugerir.form.value);
        sugerir.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
    }, true);
    // document onload, populate lemmas
    // busqueda search, limit lemma to query result
    if (pars.get('q')) {
        const  url = new URL(sugerir.action);
        url.searchParams.append("q", pars.get('q'));
        pars.getAll('f').forEach((f) => {
            url.searchParams.append("f", f);
        });
        Formajax.loadHtml(url, lemmas);
    }
    // default, 
    else {
        sugerir.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
    }
    // click behavior on lemmas container
    lemmas.addEventListener('click', (e) => {
        let a = Formajax.selfOrAncestor(e.target, 'a');
        if (!a) return;
        if (!a.classList.contains('lemma')) return;
        e.preventDefault();
        // suppress active from a just loaded page
        const aSet = lemmas.querySelectorAll("a.lemma.active");
        aSet.forEach((a) => {
            a.classList.remove('active');
        });
        const lemma = a.getAttribute('href');
        const urlhist = new URL(lemma, window.location);
        if (pars.get('q')) {
            urlhist.searchParams.append("q", pars.get('q'));
        }
        window.history.pushState({}, '', urlhist);
        // build url for article
        let  urlart = new URL('article/' + lemma, window.location);
        if (pars.get('q')) {
            urlart.searchParams.append("q", pars.get('q'));
        }
        // stream maybe faster here
        Formajax.loadHtml(urlart, main, null, Tree.load);
    });
    // infinite scroll of lemmas
    lemmas.addEventListener('scroll', function() {
        // bottom scroll
        if (lemmas.scrollTop + lemmas.clientHeight >= lemmas.scrollHeight) {
            // get last rowid
            const last = lemmas.lastElementChild;
            if (!last) {
                return;
            }
            // infinite scroll not relevant, ex: query results
            if (!last.dataset.rowid) {
                return;
            }
            const rowid = Number(last.dataset.rowid);
            const url = new URL(sugerir.action);
            if (inverso.value) {
                url.searchParams.append("inverso", inverso.value);
            }
            url.searchParams.append("id_start", rowid + 1);
            Formajax.loadHtml(url, lemmas, 'beforeend');
        }
        if (lemmas.scrollTop <= 0) {
            const first = lemmas.firstElementChild;
            if (!first) {
                return;
            }
            // infinite scroll not relevant, ex: query results
            if (!first.dataset.rowid) {
                return;
            }
            const url = new URL(sugerir.action);
            if (inverso.value) {
                url.searchParams.append("inverso", inverso.value);
            }
            url.searchParams.append("id_end", first.dataset.rowid);
            Formajax.loadHtml(url, lemmas, 'afterbegin', (e) => {
                first.scrollIntoView({  block: "start" });
            });
        }
    });
    function tabing(e) {
        const tab = e.currentTarget;
        tab.parentNode.querySelectorAll('a').forEach((a) => {
            a.classList.remove("active");
        });
        tab.classList.add("active");
        e.preventDefault();
    }

    // events for tabs
    let tab = null;
    tab = document.getElementById('tab_indicar');
    if (tab) tab.addEventListener('click', (e) => {
        tabing(e);

        busqueda.style.display = 'none';
        sugerir.style.display = 'block';
        sugerir.classList.remove("inverso");
        lemmas.classList.remove("inverso");
        sugerir.inverso.value = null;
        // clean search params
        const url = new URL (window.location);
        url.search = '';
        window.history.pushState({}, '', url);
        sugerir.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));


        return false;
    });
    tab = document.getElementById('tab_inverso');
    if (tab) tab.addEventListener('click', (e) => {
        tabing(e);

        busqueda.style.display = 'none';
        sugerir.style.display = 'block';
        sugerir.classList.add("inverso");
        lemmas.classList.add("inverso");
        sugerir.inverso.value = true;
        // clean search params
        const url = new URL (window.location);
        url.search = '';
        window.history.pushState({}, '', url);
        sugerir.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
        return false;
    });
    tab = document.getElementById('tab_busqueda');
    if (tab) tab.addEventListener('click', (e) => {
        tabing(e);
        busqueda.style.display = 'block';
        sugerir.style.display = 'none';
        lemmas.classList.remove("inverso");
        lemmas.innerText = '';
        return false;
    });
})();

// init busqueda form
(function () {
    // click search in grc will unclick search in spa, and opposite
    const checkgrc = document.querySelectorAll("form #f1");
    const checkspa = document.querySelectorAll("form #f2, form #f3, form #f4");
    checkgrc.forEach((grc) => {
        grc.addEventListener('click', (e) => {
            // if click to uncheck do nothing
            if (!grc.checked) return; 
            checkspa.forEach((spa) => {
                spa.checked = false;
            });
        });
    });
    checkspa.forEach((spa) => {
        spa.addEventListener('click', (e) => {
            // if click to uncheck do nothing
            if (!spa.checked) return; 
            checkgrc.forEach((grc) => {
                grc.checked = false;
            });
        });
    });
})();

