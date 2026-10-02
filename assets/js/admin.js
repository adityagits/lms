/* tiny HTML toolbar for <textarea data-editor> fields */
document.querySelectorAll('textarea[data-editor]').forEach(function (ta) {
  var bar = document.createElement('div');
  bar.style.cssText = 'display:flex;gap:4px;margin-bottom:4px;flex-wrap:wrap';
  [['B', 'strong'], ['I', 'em'], ['H2', 'h2'], ['H3', 'h3'], ['• List', 'ul'], ['1. List', 'ol'], ['Link', 'a'], ['Code', 'code']].forEach(function (b) {
    var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'btn sm'; btn.textContent = b[0];
    btn.onclick = function () {
      var s = ta.selectionStart, e = ta.selectionEnd, sel = ta.value.slice(s, e), t = b[1], out;
      if (t === 'ul' || t === 'ol') out = '<' + t + '>\n' + (sel || 'Item').split('\n').map(function (l) { return '  <li>' + l + '</li>'; }).join('\n') + '\n</' + t + '>';
      else if (t === 'a') { var u = prompt('Link URL', 'https://'); if (!u) return; out = '<a href="' + u + '">' + (sel || u) + '</a>'; }
      else out = '<' + t + '>' + (sel || 'text') + '</' + t + '>';
      ta.setRangeText(out, s, e, 'end'); ta.focus();
    };
    bar.appendChild(btn);
  });
  ta.parentNode.insertBefore(bar, ta);
});
