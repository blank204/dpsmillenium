/* DPS Millennium CMS block editor. Plain ES2017, no build step. Styling is Claude Design's job. */
(function () {
  'use strict';

  var form = document.getElementById('post-form');
  var clearKey = document.querySelector('[data-clear-autosave]');
  if (clearKey) {
    try { localStorage.removeItem('cms-autosave:' + clearKey.getAttribute('data-clear-autosave')); } catch (e) {}
  }
  if (!form) { return; }

  var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  var isSuper = form.getAttribute('data-super-admin') === '1';
  var list = document.getElementById('block-list');
  var mediaCache = JSON.parse(document.getElementById('media-data').textContent || '{}');
  var blocks = JSON.parse(document.getElementById('blocks-data').textContent || '[]');
  var autosaveKey = 'cms-autosave:' + form.getAttribute('data-autosave-key');
  var statusEl = document.querySelector('[data-region="autosave-status"]');
  var dirty = false;
  form.addEventListener('input', function () { dirty = true; });
  form.addEventListener('change', function () { dirty = true; });

  function el(tag, attrs, children) {
    var node = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') { node.textContent = attrs[k]; }
      else if (k.indexOf('on') === 0) { node.addEventListener(k.slice(2), attrs[k]); }
      else if (attrs[k] !== null && attrs[k] !== undefined) { node.setAttribute(k, attrs[k]); }
    });
    (children || []).forEach(function (c) { if (c) { node.appendChild(c); } });
    return node;
  }

  function say(message) { if (statusEl) { statusEl.textContent = message; } }

  /* ---------- uploads ---------- */
  function upload(file, kind) {
    var data = new FormData();
    data.append('file', file);
    data.append('kind', kind);
    say('Uploading ' + file.name + '…');
    return fetch(form.getAttribute('data-upload-url'), {
      method: 'POST', body: data, credentials: 'same-origin',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
    }).then(function (res) {
      return res.json().catch(function () { return { message: 'Upload failed. Try again.' }; }).then(function (json) {
        if (!res.ok) { throw new Error(json.message || 'Upload failed. Try again.'); }
        mediaCache[json.id] = json;
        say('Uploaded ' + json.original_name + '.');
        return json;
      });
    }).catch(function (err) { say(''); alertInline(err.message); throw err; });
  }

  function alertInline(message) {
    var box = el('p', { 'class': 'error', role: 'alert', text: message });
    list.parentNode.insertBefore(box, list);
    setTimeout(function () { box.remove(); }, 12000);
  }

  function mediaPreview(id) {
    var m = mediaCache[id];
    if (!m) { return el('span', { text: id ? 'File #' + id : 'Nothing uploaded yet' }); }
    if (m.kind === 'image') { return el('img', { src: m.preview_url, alt: m.original_name, width: '160' }); }
    return el('a', { href: m.preview_url, target: '_blank', rel: 'noopener', text: m.original_name });
  }

  /* ---------- single-field uploads (cover photo, newsletter PDF) ---------- */
  Array.prototype.forEach.call(document.querySelectorAll('input[data-upload-for]'), function (input) {
    var target = form.querySelector('input[name="' + input.getAttribute('data-upload-for') + '"]');
    var preview = form.querySelector('[data-media-preview="' + input.getAttribute('data-upload-for') + '"]');
    function show() { preview.innerHTML = ''; preview.appendChild(mediaPreview(target.value)); }
    show();
    input.addEventListener('change', function () {
      if (!input.files[0]) { return; }
      upload(input.files[0], input.getAttribute('data-kind')).then(function (m) { target.value = m.id; show(); input.value = ''; }).catch(function () {});
    });
  });

  /* ---------- grade mode toggle ---------- */
  function syncGrades() {
    var checked = form.querySelector('input[name="grades[mode]"]:checked');
    var mode = checked ? checked.value : 'none';
    Array.prototype.forEach.call(document.querySelectorAll('[data-grade-mode]'), function (box) {
      box.hidden = box.getAttribute('data-grade-mode') !== mode;
      Array.prototype.forEach.call(box.querySelectorAll('input,select'), function (i) { i.disabled = box.hidden; });
    });
  }
  Array.prototype.forEach.call(form.querySelectorAll('input[name="grades[mode]"]'), function (r) { r.addEventListener('change', syncGrades); });
  syncGrades();

  /* ---------- block defaults ---------- */
  var LABELS = { text: 'Text', image: 'Photo', gallery: 'Photo gallery', pdf: 'PDF file(s)', link: 'Link button', youtube: 'YouTube video', video: 'Short video', table: 'Table', html_page: 'Custom HTML page' };

  function blank(type) {
    switch (type) {
      case 'text': return { type: 'text', html: '' };
      case 'image': return { type: 'image', media_id: null, caption: '' };
      case 'gallery': return { type: 'gallery', items: [] };
      case 'pdf': return { type: 'pdf', files: [] };
      case 'link': return { type: 'link', label: '', url: '' };
      case 'youtube': return { type: 'youtube', url: '', mode: 'link' };
      case 'video': return { type: 'video', media_id: null, caption: '' };
      case 'table': return { type: 'table', has_header: true, rows: [['', ''], ['', '']] };
      case 'html_page': return { type: 'html_page', html_page_id: null, mode: 'link' };
    }
    return null;
  }

  /* ---------- per-type editors: each returns a DOM node and mutates block in place ---------- */
  function textEditor(block) {
    var area = el('div', { contenteditable: 'true', 'data-region': 'rich-text', role: 'textbox', 'aria-multiline': 'true' });
    area.innerHTML = block.html || '<p></p>';
    area.addEventListener('input', function () { block.html = area.innerHTML; });
    function cmd(name, value) { return function () { area.focus(); document.execCommand(name, false, value); block.html = area.innerHTML; }; }
    var bar = el('div', { 'data-region': 'text-toolbar' }, [
      el('button', { type: 'button', text: 'Bold', onclick: cmd('bold') }),
      el('button', { type: 'button', text: 'Italic', onclick: cmd('italic') }),
      el('button', { type: 'button', text: '• List', onclick: cmd('insertUnorderedList') }),
      el('button', { type: 'button', text: '1. List', onclick: cmd('insertOrderedList') }),
      el('button', { type: 'button', text: 'Link', onclick: function () {
        var url = prompt('Paste the full web address (https://…)');
        if (url) { cmd('createLink', url)(); }
      } }),
      el('button', { type: 'button', text: 'Remove link', onclick: cmd('unlink') })
    ]);
    area.addEventListener('paste', function (e) {
      e.preventDefault();
      var text = (e.clipboardData || window.clipboardData).getData('text/plain');
      document.execCommand('insertText', false, text);
    });
    return el('div', {}, [bar, area]);
  }

  function fileInput(kind, multiple, onFiles) {
    var accept = { image: 'image/jpeg,image/png,image/webp,image/heic', pdf: 'application/pdf', video: 'video/mp4' }[kind];
    var input = el('input', { type: 'file', accept: accept });
    if (multiple) { input.multiple = true; }
    input.addEventListener('change', function () {
      var files = Array.prototype.slice.call(input.files);
      input.value = '';
      files.reduce(function (p, f) { return p.then(function () { return upload(f, kind).then(onFiles).catch(function () {}); }); }, Promise.resolve());
    });
    return input;
  }

  function cropControls(block) {
    var fields = { x: 0, y: 0, w: 0, h: 0, max_width: '' };
    var inputs = Object.keys(fields).map(function (k) {
      var i = el('input', { type: 'number', min: '0', 'aria-label': k, placeholder: k === 'max_width' ? 'max width px' : k });
      i.addEventListener('input', function () { fields[k] = i.value; });
      return el('label', { text: k === 'max_width' ? 'Max width ' : k.toUpperCase() + ' ' }, [i]);
    });
    var apply = el('button', { type: 'button', text: 'Apply crop / resize', onclick: function () {
      if (!block.media_id) { return; }
      var nameInput = form.querySelector('input[name="actor_name"]');
      // Name is read at click time and never stored; the input stays as typed for the later Save.
      var body = { actor_name: nameInput ? nameInput.value : '' };
      if (+fields.w > 0 && +fields.h > 0) { body.crop = { x: +fields.x, y: +fields.y, w: +fields.w, h: +fields.h }; }
      if (+fields.max_width > 0) { body.max_width = +fields.max_width; }
      fetch(form.getAttribute('data-media-edit-url').replace('__ID__', block.media_id), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      }).then(function (r) { return r.json().then(function (j) { if (!r.ok) { throw new Error(j.message || 'Could not edit the photo.'); } return j; }); })
        .then(function (j) {
          // A new cropped copy: the original stays unchanged until this post is saved.
          mediaCache[j.id] = j; block.media_id = j.id; dirty = true; render();
          say('Cropped copy ready. Save the post to use it on the site.');
        })
        .catch(function (err) { alertInline(err.message); });
    } });
    var m = mediaCache[block.media_id];
    return el('details', { 'data-region': 'crop' }, [el('summary', { text: 'Crop or resize' }),
      el('p', { text: m && m.width ? 'Photo is ' + m.width + ' × ' + m.height + ' px.' : '' })].concat(inputs).concat([apply]));
  }

  function captionInput(obj) {
    var i = el('input', { type: 'text', maxlength: '300', placeholder: 'Caption (optional)' });
    i.value = obj.caption || '';
    i.addEventListener('input', function () { obj.caption = i.value; });
    return i;
  }

  function imageEditor(block, kind) {
    var wrap = el('div');
    wrap.appendChild(mediaPreview(block.media_id));
    wrap.appendChild(el('label', { text: block.media_id ? 'Replace ' : 'Choose ' }, [fileInput(kind, false, function (m) { block.media_id = m.id; render(); })]));
    wrap.appendChild(captionInput(block));
    if (kind === 'image' && block.media_id) { wrap.appendChild(cropControls(block)); }
    return wrap;
  }

  function galleryEditor(block) {
    var wrap = el('div');
    var ul = el('ul');
    block.items.forEach(function (item, idx) {
      ul.appendChild(el('li', {}, [mediaPreview(item.media_id), captionInput(item),
        el('button', { type: 'button', text: 'Remove photo', onclick: function () { block.items.splice(idx, 1); render(); } })]));
    });
    wrap.appendChild(ul);
    wrap.appendChild(el('label', { text: 'Add photos (you can select many) ' }, [fileInput('image', true, function (m) { block.items.push({ media_id: m.id, caption: '' }); render(); })]));
    return wrap;
  }

  function pdfEditor(block) {
    var wrap = el('div');
    block.files.forEach(function (file, idx) {
      var label = el('input', { type: 'text', maxlength: '150', placeholder: 'Label shown to parents' });
      label.value = file.label || '';
      label.addEventListener('input', function () { file.label = label.value; });
      wrap.appendChild(el('div', {}, [mediaPreview(file.media_id), label,
        el('button', { type: 'button', text: 'Remove PDF', onclick: function () { block.files.splice(idx, 1); render(); } })]));
    });
    wrap.appendChild(el('label', { text: 'Add PDF(s) ' }, [fileInput('pdf', true, function (m) { block.files.push({ media_id: m.id, label: m.original_name.replace(/\.pdf$/i, '') }); render(); })]));
    return wrap;
  }

  function textField(obj, key, attrs) {
    var i = el('input', attrs);
    i.value = obj[key] || '';
    i.addEventListener('input', function () { obj[key] = i.value; });
    return i;
  }

  function radioPair(obj, key, options) {
    var name = 'r' + Math.random().toString(36).slice(2);
    return el('div', {}, options.map(function (o) {
      var r = el('input', { type: 'radio', name: name, value: o[0] });
      r.checked = obj[key] === o[0];
      r.addEventListener('change', function () { obj[key] = o[0]; });
      return el('label', { text: ' ' + o[1] + ' ' }, [r]);
    }));
  }

  function parsePasted(text) {
    var rows = [], row = [], cell = '', i = 0, quoted = false;
    text = text.replace(/\r\n?/g, '\n');
    for (; i < text.length; i++) {
      var ch = text[i];
      if (quoted) {
        if (ch === '"' && text[i + 1] === '"') { cell += '"'; i++; }
        else if (ch === '"') { quoted = false; }
        else { cell += ch; }
      } else if (ch === '"' && cell === '') { quoted = true; }
      else if (ch === '\t') { row.push(cell); cell = ''; }
      else if (ch === '\n') { row.push(cell); rows.push(row); row = []; cell = ''; }
      else { cell += ch; }
    }
    if (cell !== '' || row.length) { row.push(cell); rows.push(row); }
    var width = rows.reduce(function (w, r) { return Math.max(w, r.length); }, 0);
    return rows.map(function (r) { while (r.length < width) { r.push(''); } return r; });
  }

  function tableEditor(block) {
    var wrap = el('div');
    var header = el('input', { type: 'checkbox' });
    header.checked = !!block.has_header;
    header.addEventListener('change', function () { block.has_header = header.checked; });
    wrap.appendChild(el('label', { text: ' First row is a heading' }, [header]));

    var paste = el('textarea', { rows: '3', placeholder: 'Copy cells in Excel or Google Sheets, click here and paste (Ctrl+V). This replaces the table below.' });
    paste.addEventListener('paste', function (e) {
      e.preventDefault();
      var rows = parsePasted((e.clipboardData || window.clipboardData).getData('text/plain'));
      if (rows.length) { block.rows = rows; render(); say('Table pasted: ' + rows.length + ' rows.'); }
    });
    wrap.appendChild(paste);

    var table = el('table', { 'data-region': 'table-grid' });
    block.rows.forEach(function (row, r) {
      var tr = el('tr');
      row.forEach(function (value, c) {
        var input = el('input', { type: 'text', 'aria-label': 'Row ' + (r + 1) + ' column ' + (c + 1) });
        input.value = value;
        input.addEventListener('input', function () { block.rows[r][c] = input.value; });
        tr.appendChild(el('td', {}, [input]));
      });
      table.appendChild(tr);
    });
    wrap.appendChild(el('div', { 'data-region': 'table-scroll' }, [table]));
    wrap.appendChild(el('button', { type: 'button', text: '+ Row', onclick: function () { block.rows.push(block.rows[0].map(function () { return ''; })); render(); } }));
    wrap.appendChild(el('button', { type: 'button', text: '+ Column', onclick: function () { block.rows.forEach(function (r) { r.push(''); }); render(); } }));
    wrap.appendChild(el('button', { type: 'button', text: '− Last row', onclick: function () { if (block.rows.length > 1) { block.rows.pop(); render(); } } }));
    wrap.appendChild(el('button', { type: 'button', text: '− Last column', onclick: function () { if (block.rows[0].length > 1) { block.rows.forEach(function (r) { r.pop(); }); render(); } } }));
    return wrap;
  }

  function editorFor(block) {
    switch (block.type) {
      case 'text': return textEditor(block);
      case 'image': return imageEditor(block, 'image');
      case 'video': return imageEditor(block, 'video');
      case 'gallery': return galleryEditor(block);
      case 'pdf': return pdfEditor(block);
      case 'link': return el('div', {}, [
        el('label', { text: 'Button label ' }, [textField(block, 'label', { type: 'text', maxlength: '100' })]),
        el('label', { text: 'Web address ' }, [textField(block, 'url', { type: 'url', placeholder: 'https://' })])]);
      case 'youtube': return el('div', {}, [
        el('label', { text: 'YouTube link ' }, [textField(block, 'url', { type: 'url', placeholder: 'https://youtu.be/…' })]),
        radioPair(block, 'mode', [['link', 'Show as a link'], ['player', 'Show the video player']])]);
      case 'html_page':
        if (!isSuper) { return el('p', { text: 'Custom page #' + block.html_page_id + ' (only IT can change this block; you can move or remove it).' }); }
        return el('div', {}, [
          el('label', { text: 'Custom page ID ' }, [textField(block, 'html_page_id', { type: 'number', min: '1' })]),
          radioPair(block, 'mode', [['link', 'Link'], ['embed', 'Embed on the page']])]);
    }
    return el('p', { text: 'Unknown block' });
  }

  /* ---------- list rendering ---------- */
  function render(silent) {
    if (!silent) { dirty = true; }
    list.innerHTML = '';
    if (!blocks.length) { list.appendChild(el('p', { 'data-state': 'empty', text: 'No content yet. Add a block below.' })); }
    blocks.forEach(function (block, idx) {
      var controls = el('div', { 'data-region': 'block-controls' }, [
        el('strong', { text: (idx + 1) + '. ' + (LABELS[block.type] || block.type) }),
        el('button', { type: 'button', text: '↑ Up', disabled: idx === 0 ? 'disabled' : null, onclick: function () { move(idx, -1); } }),
        el('button', { type: 'button', text: '↓ Down', disabled: idx === blocks.length - 1 ? 'disabled' : null, onclick: function () { move(idx, 1); } }),
        el('button', { type: 'button', text: 'Remove', onclick: function () { if (confirm('Remove this ' + (LABELS[block.type] || 'block') + '?')) { blocks.splice(idx, 1); render(); } } })
      ]);
      list.appendChild(el('section', { 'data-block-editor': block.type }, [controls, editorFor(block)]));
    });
  }

  function move(idx, delta) {
    var target = idx + delta;
    if (target < 0 || target >= blocks.length) { return; }
    var tmp = blocks[idx]; blocks[idx] = blocks[target]; blocks[target] = tmp;
    render();
  }

  document.getElementById('add-block').addEventListener('click', function () {
    var select = document.getElementById('add-block-type');
    var block = blank(select.value);
    if (!block) { select.focus(); return; }
    blocks.push(block);
    select.value = '';
    render();
    list.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  /* ---------- submit / preview ---------- */
  function serialize() { document.getElementById('blocks_json').value = JSON.stringify(blocks); }

  form.addEventListener('submit', serialize);

  document.getElementById('preview-button').addEventListener('click', function () {
    serialize();
    var temp = el('form', { method: 'post', action: this.getAttribute('data-preview-url'), target: '_blank' });
    ['_token', 'section', 'title', 'summary', 'blocks_json', 'post_id'].forEach(function (name) {
      var src = form.querySelector('[name="' + name + '"]');
      if (src) { temp.appendChild(el('input', { type: 'hidden', name: name, value: src.value })); }
    });
    document.body.appendChild(temp);
    temp.submit();
    temp.remove();
  });

  /* ---------- autosave to this browser (not the server) ---------- */
  function snapshot() {
    serialize();
    var fields = {};
    Array.prototype.forEach.call(form.elements, function (f) {
      // Buttons (including the two <button name="intent" value="draft"|"publish">
      // submit buttons) are never part of the saved/restored state: they have no
      // meaningful "current value" to persist, and writing a button's value back
      // on restore would silently flip which submit button looks "chosen".
      if (f.tagName === 'BUTTON' || f.type === 'submit' || f.type === 'button') { return; }
      if (!f.name || f.name === 'actor_name' || f.name === '_token' || f.name === '_method' || f.type === 'file') { return; }
      if ((f.type === 'checkbox' || f.type === 'radio') && !f.checked) { return; }
      fields[f.name] = fields[f.name] === undefined ? f.value : [].concat(fields[f.name], f.value);
    });
    return { savedAt: Math.floor(Date.now() / 1000), fields: fields, media: mediaCache };
  }

  function autosave() {
    if (!dirty) { return; }
    try {
      localStorage.setItem(autosaveKey, JSON.stringify(snapshot()));
      dirty = false;
      say('Unsaved changes kept on this computer at ' + new Date().toLocaleTimeString() + '. Remember to Save draft or Publish.');
    } catch (e) { /* storage full or blocked: ignore */ }
  }

  function restore(saved) {
    Object.keys(saved.fields).forEach(function (name) {
      var values = [].concat(saved.fields[name]);
      Array.prototype.forEach.call(form.querySelectorAll('[name="' + name + '"]'), function (f) {
        if (f.tagName === 'BUTTON' || f.type === 'submit' || f.type === 'button') { return; }
        if (f.type === 'checkbox' || f.type === 'radio') { f.checked = values.indexOf(f.value) !== -1; }
        else if (name !== 'blocks_json') { f.value = values[0]; }
      });
    });
    Object.keys(saved.media || {}).forEach(function (id) { mediaCache[id] = saved.media[id]; });
    blocks = JSON.parse(saved.fields.blocks_json || '[]');
    syncGrades();
    render(true);
    dirty = false;
    say('Unsaved changes restored.');
  }

  try {
    var saved = JSON.parse(localStorage.getItem(autosaveKey) || 'null');
    if (saved && saved.savedAt > +form.getAttribute('data-updated-at')) {
      var bar = el('p', { role: 'status', 'data-region': 'restore-autosave' }, [
        el('span', { text: 'You have unsaved changes from ' + new Date(saved.savedAt * 1000).toLocaleString() + '. ' }),
        el('button', { type: 'button', text: 'Restore them', onclick: function () { restore(saved); bar.remove(); } }),
        el('button', { type: 'button', text: 'Discard', onclick: function () { localStorage.removeItem(autosaveKey); bar.remove(); } })
      ]);
      form.insertBefore(bar, form.firstChild);
    }
  } catch (e) {}

  setInterval(autosave, 10000);
  form.addEventListener('focusout', autosave);

  render(true);
})();
