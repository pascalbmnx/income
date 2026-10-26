(function () {
  "use strict";

  window.addEventListener("DOMContentLoaded", function () {
    var yearEl = document.getElementById("year");
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    var forms = document.querySelectorAll('form[action="login.php"]');
    forms.forEach(function (f) {
      f.addEventListener("submit", function (e) {
        var u = f.querySelector('[name="username"]');
        var p = f.querySelector('[name="password"]');
        if (u && u.value.trim() === "") {
          alert("Please enter your username.");
          e.preventDefault();
          u.focus();
          return;
        }
        if (p && (p.value || "") === "") {
          alert("Please enter your password.");
          e.preventDefault();
          p.focus();
          return;
        }
      });
    });

    var delBtns = document.querySelectorAll('input[type="hidden"][name="action"]');
    delBtns.forEach(function (h) {
      if (h.value === "delete") {
        var form = h.closest("form");
        if (form) form.querySelector('button[type="submit"]').addEventListener("click", function () {});
      }
    });
  });
})();
