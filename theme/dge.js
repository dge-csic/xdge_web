/**
 * Different Javascript functions
 */

/**
 * persistency in nav columns
 */
function navGo(a, e) {
  if (!a) {
    if (!e) var e = window.event;
    if (e.target) a = e.target;
    else if (e.srcElement) a = e.srcElement;
    if (a.nodeType == 3) a = a.parentNode;
  }
  if (!a.href) return true;
  if (document.lastIndex) document.links[document.lastIndex].className=''; // no more focus
  if (document.lastActive) document.lastActive.className='';
  else if (last=document.getElementById('active')) last.className='';
  a.className='active';
  document.lastActive=a;
  if(a.text)text=a.text;
  else if(a.innerText)text=a.innerText;
  else if(a.textContent)text=a.textContent;
  q=window.top.document.getElementById('q');
  if (q) q.value=text;
  // change the index for key move
  document.lastIndex=linkIndex(a);
}
function navMove(input, dif) {
  last=document.lastIndex;
  if (!last) last=linkIndex(document.getElementById('active'));
  if (last<1) last=1;
  document.links[last].className=document.links[last].className.replace(/ *focus */g, '');
  last=last+dif;
  if (last >= document.links.length || last < 0) return false;
  document.lastIndex=last;
  document.links[last].className+=' focus';
  // give focus to link can mimick scrollIntoView
  // document.links[last].focus();
  // input.focus();
  // document.links[last].scrollIntoView(); // no scroll
  text=document.links[last].text;
  if(text) input.value=text;
  return false;
}
function linkIndex(o) {
  for (i=0;i<document.links.length;i++) if (o==document.links[i]) return i;
  return -1;
}

function getScrollbarWidth() {
  var scr = null;
  var inn = null;
  var wNoScroll = 0;
  var wScroll = 0;
  // Outer scrolling div
  scr = document.createElement('div');
  scr.style.position = 'absolute';
  scr.style.top = '-1000px';
  scr.style.left = '-1000px';
  scr.style.width = '100px';
  scr.style.height = '50px';
  // Start with no scrollbar
  scr.style.overflow = 'hidden';

  // Inner content div
  inn = document.createElement('div');
  inn.style.width = '100%';
  inn.style.height = '200px';

  // Put the inner div in the scrolling div
  scr.appendChild(inn);
  // Append the scrolling div to the doc

  document.body.appendChild(scr);

  // Width of the inner div sans scrollbar
  wNoScroll = inn.offsetWidth;
  // Add the scrollbar
  scr.style.overflow = 'auto';
  // Width of the inner div width scrollbar
  wScroll = inn.offsetWidth;

  // Remove the scrolling div from the doc
  document.body.removeChild(
  document.body.lastChild);

  // Pixel width of the scroller
  return (wNoScroll - wScroll);
}


/**
 * Keys behavior in the query field
 */
function qKey(input, e) {
  e = e || window.event;
  var keyCode=e.keyCode? e.keyCode : e.charCode;
  // TODO, arrow keys
  // no modification of content, cursors ?
  win=window.frames['suggest'];
  if (!win);
  // enter
  else if (keyCode==13) {
    var b = win.document.getElementsByTagName('base');
    if (b && b[0] && b[0].target) var target=b[0].target;
    
    if (win.document.lastIndex) win.document.links[win.document.lastIndex].click();
    else {
      var a=win.document.getElementById('active');
      if (a) {
        a.click();
      }
    }
  }
  // down
  else if (keyCode==40) win.navMove(input, +1);
  // downdown
  else if (keyCode==34) win.navMove(input, +10);
  // up
  else if (keyCode==38) win.navMove(input, -1);
  // upup
  else if (keyCode==33) win.navMove(input, -10);
  
  // no modification of value, nothing more to do here
  if (input.last == input.value) return false;
  // keep track of last value
  input.last=input.value;
  // no reload needed in case of navigation in column
  if (keyCode==40 || keyCode==34 || keyCode==38 || keyCode==33 || keyCode==13) return false;
  
  ret=true;
  keyString=String.fromCharCode(keyCode);
  keyString=keyString.toLowerCase();
  value=lat_grc[keyString]; 
  // change the key event is idiot (is it a greek keyboard?)
  // a latin letter possible to transliterate
  if (value) {
    // break propagation of key event in these cases
    ret=false;
    // insert a char at the cursor position
    if (input.selectionStart || input.selectionStart == '0') { // FF
      var startPos = input.selectionStart;
      var endPos = input.selectionEnd;
      input.value = latGrc(input.value);
      input.focus();
      input.setSelectionRange(startPos, startPos);
    }
    else if (document.selection && document.selection.createRange) { // IE
      sel = document.selection.createRange();
      sel.moveStart('character', -1); // select one char back
      sel.text = value; // replace this latin char by a greek char
    }
    else { // replace complete value, caret position is lost
      input.value = latGrc(input.value);
    }
  }
  // return propagation status
  return true;
}

function latGrc(text) {
  text=text.split('');
  max=text.length;
  for (var i=0; i<max; i++) {
    c=lat_grc[text[i]];
    if (c) text[i]=c;
  }
  return text.join('');
}


function xdgeTab(a, lemma) {
  // desactivate last tab and hilite current
  if (document.anchors['lemmas']) document.anchors['lemmas'].className="";
  if (document.anchors['inverso']) document.anchors['inverso'].className="";
  if (document.anchors['busqueda']) document.anchors['busqueda'].className="";
  a.className="active";
  // inform server a new tab has been chosen
  Cookie.set("tab", a.name);
  form=document.forms['suggest'];
  if (a.name == "textos") form.q.disabled=true;
  else form.q.disabled=false;
  if (a.name=="busqueda") return true;
  // do things
  if (a.name=='inverso') {
    if (lemma) form.q.value=lemma;
    form.inverso.value=1;
    form.q.className="inverso";
    form.q.value=form.q.value.split('').reverse().join('');
  }
  else {
    // was inverso, reverse it
    if (form.q.className == "inverso") form.q.value=form.q.value.split('').reverse().join('');
    form.inverso.value=null;
    form.q.className="";
  }
  form.submit();
  return false;
}

/**
 * hide/show <cit>
 */
function butCit(a) {
  var parent=a;
  while (parent.className.indexOf("entry") == -1) {
    parent=parent.parentNode;
    if (!parent) return true;
  }
  if (parent.className.indexOf("short") != -1) parent.className="entry";
  else parent.className="entry short";
  return false;
}

/**
 * Play with hash URIs '#'
 */
var Anchor = {
  /**
   * get anchor object
   */
  get: function(id) {
    if (!id) {
      id=window.location.hash;
      if(id.indexOf('#') != 0) return false;
      id=id.substring(1);
    }
    var o=document.getElementById(id);
    // if (!o) take from anchors array
    return o;
  },
  /**
   * Hilite an anchor element
   */
  hi: function () {
    // if another element has been hilited
    if (!this.window.Anchor_last);
    else {
      // var o=document.getElementById(this.window.Anchor_last);
      // strip mentions of hi in className
      this.window.Anchor_last.className=o.className.replace(/ *hilite */g, '');
    }
    o=Anchor.get();
    if(!o) return false;
    if (o.className.indexOf("hilite") > -1) return false;
    o.className += " hilite";
    // TODO, scroll to headword (if not upper than the screen)
    var parent=o;
    while(parent) {
      if (Scroll.y(parent)) break;
      parent=parent.parentNode;
    }
    if (parent.scrollBy) parent.scrollBy(0, -100);
    else parent.scrollTop=parent.scrollTop - 100;
    // here it's OK, but an event scroll the page to its right place after
    return false;
  },
  /**
   * What to do whith that when document is loaded
   */
  load: function() {

    if (window.addEventListener) {
      window.addEventListener('scroll', Anchor.hi, false);
      window.addEventListener('load', Anchor.hi, false);
    }
    else if(window.attachEvent) {
      window.attachEvent('onscroll', Anchor.hi);
      window.attachEvent('onload', Anchor.hi);
    }
  }
}
Anchor.load();
/**
 * Keep memory of scroll in a window.
 * To start the scroll setter on a page, write Scroll.set() somewhere in a page.
 */
var Scroll = {
  key: function() {
    return window.location.pathname+"#y";
  },
  set: function() {
    if (window.addEventListener) {
      window.addEventListener('load', Scroll.load, false);
      window.addEventListener('beforeunload', Scroll.save, false);
    }
    else if(window.attachEvent) {
      window.attachEvent('onload', Scroll.load);
      window.attachEvent('onbeforeunload', Scroll.save);
    }
    return;
  },
  y: function(o) {
    if (!o) o=window;
    return o.pageYOffset
      || o.scrollTop
      || (o.document && o.document.body && o.document.body.scrollTop)
      || (o.document && o.document.documentElement && o.document.documentElement.scrollTop);
  },
  save: function() {
    Cookie.set(Scroll.key(), Scroll.y());
  },
  load: function() {
    var scroll=Cookie.get(Scroll.key());
    if(!scroll) return;
    if (window.location.hash) return;
    window.scrollTo(0, scroll);
  }
}
var Cookie = {
  /**
   * Set a cookie
   *
   * @param name  ! the name of the cookie
   * @param value ! the value of the cookie
   * @param days  ? duration of the cookie
   */
  set: function(name, value, days, path) {
    if (days) {
      var date = new Date();
      date.setTime(date.getTime()+(days*24*60*60*1000));
      var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    // par défaut, cookie valable pour la seule page

    document.cookie = name+"="+value+expires+";"; // ? utile ? path=/";
  },
  /**
   * Get a cookie
   */
  get: function(name, value) {
    if (document.cookie.length < 1) return "";
    i=document.cookie.indexOf(name + "=");
    if (i<0) return "";
    i=i+name.length+1;
    j=(document.cookie +";").indexOf(";",i);
    return document.cookie.substring(i,j);
  },
  /**
   * Delete a cookie
   */
  del: function (name) {
    Cookie.set(name,"",-1);
  }
}
