(function () {
  "use strict";

  window.addEventListener("DOMContentLoaded", function () {
    var yearEl = document.getElementById("year");
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    var navToggle = document.getElementById("navToggle");
    var nav = document.querySelector(".nav");
    if (navToggle && nav) {
      navToggle.addEventListener("click", function () {
        nav.classList.toggle("open");
      });
    }

    var toastEl = document.getElementById("toast");
    var toastTimer = null;
    function showToast(message, isError) {
      if (!toastEl) return;
      toastEl.textContent = message;
      toastEl.classList.toggle("error", !!isError);
      toastEl.classList.add("show");
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () {
        toastEl.classList.remove("show");
      }, 3500);
    }

    // ---- Upload box (drawing form) ----
    var MAX_FILE_SIZE = 5 * 1024 * 1024;
    var uploadInput = document.getElementById("drwImage");
    var uploadBox = document.getElementById("drwUpload");
    var resetUploadUI = null;

    if (uploadBox) {
      var uploadContent = uploadBox.querySelector(".upload-content");
      var uploadPreview = uploadBox.querySelector(".upload-preview");
      var previewImg = document.getElementById("drwImagePreview");
      var fileNameEl = document.getElementById("drwImageName");
      var removeBtn = document.getElementById("drwRemoveImage");

      uploadInput.addEventListener("change", function () {
        var file = uploadInput.files && uploadInput.files[0];
        if (!file) return;

        if (!file.type.startsWith("image/")) {
          showToast("Please select an image file.", true);
          uploadInput.value = "";
          return;
        }
        if (file.size > MAX_FILE_SIZE) {
          showToast("Image is larger than 5 MB.", true);
          uploadInput.value = "";
          return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
          previewImg.src = e.target.result;
          fileNameEl.textContent = file.name;
          uploadContent.classList.add("hidden");
          uploadPreview.classList.remove("hidden");
        };
        reader.readAsDataURL(file);
      });

      resetUploadUI = function () {
        uploadInput.value = "";
        previewImg.src = "";
        fileNameEl.textContent = "";
        uploadContent.classList.remove("hidden");
        uploadPreview.classList.add("hidden");
      };

      removeBtn.addEventListener("click", function () { resetUploadUI(); });

      var drawingForm = document.getElementById("drawingForm");
      if (drawingForm) {
        drawingForm.addEventListener("reset", function () { resetUploadUI(); });
      }
    }

    // ---- Validation helpers ----
    function errorMsg(name) {
      return {
        applicant: "Please enter your full name.",
        phone: "Please enter a valid phone number.",
        address: "Please enter the service address.",
        purpose: "Please select the purpose of supply.",
        load: "Please enter a valid requested load.",
        phase: "Please select the phase.",
        project: "Please enter the project name.",
        site: "Please enter the site location.",
        type: "Please select a drawing type.",
        contractor: "Please enter the installer / contractor name.",
        premise: "Please enter the installation premise.",
        date: "Please select the inspection date.",
        name: "Please enter your name.",
        email: "Please enter a valid email address.",
        subject: "Please enter a subject.",
        message: "Please write a message."
      }[name];
    }

    function setFieldError(field, msg) {
      field.classList.add("error");
      var hint = document.createElement("span");
      hint.className = "error-msg";
      hint.textContent = msg;
      field.parentNode.appendChild(hint);
    }

    function clearFieldErrors(form) {
      form.querySelectorAll(".field .error").forEach(function (el) {
        el.classList.remove("error");
      });
      form.querySelectorAll(".field .error-msg").forEach(function (el) {
        el.remove();
      });
    }

    function validPhone(value) {
      var digits = value.replace(/[^0-9]/g, "");
      return digits.length >= 9;
    }

    function validEmail(value) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function validateForm(form) {
      clearFieldErrors(form);
      var ok = true;

      form.querySelectorAll("input[required], select[required], textarea[required]").forEach(function (el) {
        var value = (el.value || "").trim();
        var name = el.getAttribute("name");
        var valid = true;

        if (!value) {
          valid = false;
        } else if (el.type === "tel" && !validPhone(value)) {
          valid = false;
        } else if (el.type === "email" && !validEmail(value)) {
          valid = false;
        } else if (el.type === "number") {
          var n = parseFloat(value);
          var min = parseFloat(el.getAttribute("min"));
          if (isNaN(n) || (el.min && n < min)) {
            valid = false;
          }
        }

        if (!valid) {
          setFieldError(el, errorMsg(name) || "Please fill this field correctly.");
          ok = false;
        }
      });

      return ok;
    }

    // ---- Attach validation to all forms (let the browser submit normally) ----
    document.querySelectorAll("form[data-validate]").forEach(function (form) {
      form.addEventListener("submit", function (e) {
        if (!validateForm(form)) {
          e.preventDefault();
        }
      });
    });
  });
})();
