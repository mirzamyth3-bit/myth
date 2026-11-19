(function () {
  'use strict';

  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('main-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-confirm]');
    if (b && !window.confirm(b.getAttribute('data-confirm'))) { ev.preventDefault(); }
    var p = ev.target.closest('[data-print]');
    if (p) { window.print(); }
  });

  var form = document.querySelector('[data-admission-form]');
  if (form) {
    var phone = form.querySelector('#phone');
    phone.addEventListener('input', function () { phone.value = phone.value.replace(/[^0-9+\-\s()]/g, ''); });
    form.addEventListener('submit', function (ev) {
      var bad = null;
      form.querySelectorAll('.field-error.js').forEach(function (n) { n.remove(); });
      form.querySelectorAll('[required]').forEach(function (inp) {
        var msg = '';
        if (!inp.value.trim()) { msg = 'This field is required.'; }
        else if (inp.id === 'phone' && !/^(\+?88)?01[3-9][0-9]{8}$/.test(inp.value.replace(/[\s\-().]/g, ''))) { msg = 'Enter a valid mobile number, e.g. 01712345678.'; }
        else if (inp.type === 'email' && !inp.checkValidity()) { msg = 'Enter a valid email address.'; }
        if (msg) {
          var s = document.createElement('span');
          s.className = 'field-error js'; s.textContent = msg;
          inp.parentNode.appendChild(s);
          inp.setAttribute('aria-invalid', 'true');
          bad = bad || inp;
        } else { inp.removeAttribute('aria-invalid'); }
      });
      if (bad) { ev.preventDefault(); bad.focus(); }
    });
  }

  var grid = document.querySelector('[data-lightbox]');
  if (grid) {
    var box = document.createElement('div');
    box.className = 'lightbox'; box.hidden = true;
    box.innerHTML = '<button type="button" class="lb-close" aria-label="Close">&times;</button><img alt=""><p></p>';
    document.body.appendChild(box);
    var img = box.querySelector('img'), cap = box.querySelector('p');
    function close() { box.hidden = true; img.removeAttribute('src'); }
    grid.addEventListener('click', function (ev) {
      var a = ev.target.closest('a[data-caption]');
      if (!a) { return; }
      ev.preventDefault();
      img.src = a.href; img.alt = a.getAttribute('data-caption'); cap.textContent = a.getAttribute('data-caption');
      box.hidden = false; box.querySelector('.lb-close').focus();
    });
    box.addEventListener('click', function (ev) { if (ev.target !== img) { close(); } });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && !box.hidden) { close(); } });
  }
})();
