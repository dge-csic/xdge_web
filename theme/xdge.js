/**
 * Behaviours of tabs
 */
function tab(a) {
  // desactivate last tab and hilite current
  var indicar=document.getElementById('indicar');
  if (indicar) indicar.className="";
  var inverso=document.getElementById('inverso');
  if (inverso) inverso.className="";
  a.className="active";
  // inform server a new tab has been chosen
  Cookie.set("tab", a.id);
  // input, be careful to IE id model, use var
  var q=document.getElementById('q');
  if (!q) return;
  if (a.id=='inverso') {
    q.className="inverso";
  }
  else {
    // was inverso, reverse it
    q.className="";
  }
  a.href=a.href.replace(/\/[^\/\?]*$/, '/'+q.value);
  if (!a.target) return true;
  window.frames[a.target].location.replace(a.href);
  return false;
}
/**
 * Load lemmas in nav column
 */
 