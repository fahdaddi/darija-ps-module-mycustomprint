/**
 * Custom Print Studio — vanilla-JS port of the design source's CustomStudio.jsx
 * interaction model: blank/placement/method selection, drag-drop artwork
 * preview, live price estimate. No React dependency — the production theme
 * has no client-side framework runtime.
 */
(function () {
  'use strict';

  var PLACEMENT_BOX = {
    front: { top: '26%', left: '50%', widthFactor: 1 },
    back: { top: '22%', left: '50%', widthFactor: 1.1 },
    pocket: { top: '34%', left: '36%', widthFactor: 0.3 },
    sleeve: { top: '30%', left: '16%', widthFactor: 0.24 },
  };

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('mcp-studio-form');
    if (!form) {
      return;
    }

    var blanks = JSON.parse(form.dataset.blanks || '[]');
    var methods = JSON.parse(form.dataset.methods || '[]');
    var backFee = parseFloat(form.dataset.backFee || '0');
    var currency = form.dataset.currency || '';

    var state = {
      blank: blanks[0] || null,
      placement: 'front',
      method: methods[0] ? methods[0].id : null,
      qty: 1,
      scale: 62,
    };

    var els = {
      frame: form.querySelector('[data-mcp-frame]'),
      blankPlaceholder: form.querySelector('[data-mcp-blank-placeholder]'),
      blankPhotos: form.querySelectorAll('[data-mcp-blank-photo]'),
      artworkPreview: form.querySelector('[data-mcp-artwork-preview]'),
      printArea: form.querySelector('[data-mcp-print-area]'),
      placementLabel: form.querySelector('[data-mcp-placement-label]'),
      scaleInput: form.querySelector('[data-mcp-scale]'),

      dropzone: form.querySelector('[data-mcp-dropzone]'),
      dropzoneLabel: form.querySelector('[data-mcp-dropzone-label]'),
      dropzoneHint: form.querySelector('[data-mcp-dropzone-hint]'),
      fileInput: form.querySelector('[data-mcp-file-input]'),

      blankGrid: form.querySelector('[data-mcp-blank-grid]'),
      blankValue: form.querySelector('[data-mcp-blank-value]'),
      sizeRow: form.querySelector('[data-mcp-size-row]'),
      sizeValue: form.querySelector('[data-mcp-size-value]'),

      placementRow: form.querySelector('[data-mcp-placement-row]'),
      placementValue: form.querySelector('[data-mcp-placement-value]'),
      methodList: form.querySelector('[data-mcp-method-list]'),
      methodValue: form.querySelector('[data-mcp-method-value]'),

      qtyValue: form.querySelector('[data-mcp-qty-value]'),
      qtyInput: form.querySelector('[data-mcp-qty-input]'),
      qtyDecrement: form.querySelector('[data-mcp-qty-decrement]'),
      qtyIncrement: form.querySelector('[data-mcp-qty-increment]'),
      qtyHint: form.querySelector('[data-mcp-qty-hint]'),

      summaryBlank: form.querySelector('[data-mcp-summary-blank]'),
      summaryBlankPrice: form.querySelector('[data-mcp-summary-blank-price]'),
      summaryMethodFee: form.querySelector('[data-mcp-summary-method-fee]'),
      summaryPlacementFee: form.querySelector('[data-mcp-summary-placement-fee]'),
      summaryQty: form.querySelector('[data-mcp-summary-qty]'),
      summaryTotal: form.querySelector('[data-mcp-summary-total]'),
      estimatedTotalInput: form.querySelector('[data-mcp-estimated-total-input]'),
    };

    function findBlank(id) {
      for (var i = 0; i < blanks.length; i++) {
        if (blanks[i].id === id) {
          return blanks[i];
        }
      }
      return blanks[0] || null;
    }

    function findMethod(id) {
      for (var i = 0; i < methods.length; i++) {
        if (methods[i].id === id) {
          return methods[i];
        }
      }
      return methods[0] || null;
    }

    function updateBlankPhoto() {
      // Only front/back photos are configurable; pocket/sleeve placements
      // reuse the front photo since there's no dedicated angle for them.
      var side = state.placement === 'back' ? 'back' : 'front';

      var activeImg = null;
      els.blankPhotos.forEach(function (img) {
        var isActive = img.dataset.mcpBlankPhoto === state.blank.id && img.dataset.mcpBlankSide === side;
        img.style.display = isActive ? '' : 'none';
        if (isActive) {
          activeImg = img;
        }
      });

      if (els.blankPlaceholder) {
        els.blankPlaceholder.textContent = 'No ' + side + ' photo yet for ' + state.blank.label.toLowerCase();
      }

      if (!activeImg) {
        if (els.blankPlaceholder) {
          els.blankPlaceholder.style.display = '';
        }
        return;
      }

      // The photo may not exist yet (no asset dropped in for this blank).
      // naturalWidth is 0 both before load and after a failed load, but by
      // this point the browser has already attempted the fetch (src was set
      // in markup), so a synchronous check after "load"/"error" have had a
      // chance to fire is reliable without polling.
      var reveal = function () {
        var loaded = activeImg.complete && activeImg.naturalWidth > 0;
        activeImg.hidden = !loaded;
        if (els.blankPlaceholder) {
          els.blankPlaceholder.style.display = loaded ? 'none' : '';
        }
      };

      if (activeImg.complete) {
        reveal();
      } else {
        activeImg.addEventListener('load', reveal, { once: true });
        activeImg.addEventListener('error', reveal, { once: true });
      }
    }

    function updatePrintArea() {
      var box = PLACEMENT_BOX[state.placement] || PLACEMENT_BOX.front;
      var width = state.scale * box.widthFactor;
      if (els.printArea) {
        els.printArea.style.top = box.top;
        els.printArea.style.left = box.left;
        els.printArea.style.width = width + '%';
      }
      if (els.artworkPreview && !els.artworkPreview.hidden) {
        els.artworkPreview.style.top = box.top;
        els.artworkPreview.style.left = box.left;
        els.artworkPreview.style.width = width + '%';
      }
    }

    function updatePlacementLabel() {
      var row = els.placementRow ? els.placementRow.querySelectorAll('[data-mcp-placement-option]') : [];
      var label = '';
      row.forEach(function (btn) {
        if (btn.dataset.placementId === state.placement) {
          label = btn.textContent;
        }
      });
      if (els.placementLabel) {
        els.placementLabel.textContent = 'Preview · ' + label;
      }
    }

    function formatMoney(n) {
      return Math.round(n * 100) / 100;
    }

    function recomputeEstimate() {
      if (!state.blank) {
        return;
      }
      var method = findMethod(state.method) || { fee: 0 };
      var placementFee = state.placement === 'back' ? backFee : 0;
      var unit = state.blank.base + (method.fee || 0) + placementFee;
      var total = unit * state.qty;

      if (els.summaryBlank) {
        els.summaryBlank.textContent = state.blank.label;
      }
      if (els.summaryBlankPrice) {
        els.summaryBlankPrice.textContent = formatMoney(state.blank.base) + ' ' + currency;
      }
      if (els.summaryMethodFee) {
        els.summaryMethodFee.textContent = method.fee ? '+' + method.fee + ' ' + currency : 'Included';
      }
      if (els.summaryPlacementFee) {
        els.summaryPlacementFee.textContent = placementFee ? '+' + placementFee + ' ' + currency : 'Included';
      }
      if (els.summaryQty) {
        els.summaryQty.textContent = state.qty;
      }
      if (els.summaryTotal) {
        els.summaryTotal.textContent = formatMoney(total);
      }
      if (els.estimatedTotalInput) {
        els.estimatedTotalInput.value = formatMoney(total);
      }
      if (els.qtyHint) {
        els.qtyHint.textContent = state.qty >= 10 ? 'Bulk rate applied at 10+' : '10+ gets a bulk rate';
      }
    }

    function selectBlank(blankId, buttonEl) {
      state.blank = findBlank(blankId);
      if (els.blankValue) {
        els.blankValue.value = state.blank.id;
      }
      if (els.blankGrid) {
        els.blankGrid.querySelectorAll('[data-mcp-blank-option]').forEach(function (btn) {
          btn.classList.toggle('mcp-studio__blank-option--active', btn === buttonEl);
        });
      }

      var isMug = state.blank.id === 'mug';
      if (els.sizeRow) {
        els.sizeRow.querySelectorAll('[data-mcp-size-option]').forEach(function (btn) {
          btn.disabled = isMug;
        });
      }
      if (isMug) {
        selectPlacement('front', null);
        if (els.placementRow) {
          els.placementRow.querySelectorAll('[data-mcp-placement-option]').forEach(function (btn) {
            btn.style.display = btn.dataset.placementId === 'front' ? '' : 'none';
          });
        }
      } else if (els.placementRow) {
        els.placementRow.querySelectorAll('[data-mcp-placement-option]').forEach(function (btn) {
          btn.style.display = '';
        });
      }

      updateBlankPhoto();
      recomputeEstimate();
    }

    function selectPlacement(placementId, buttonEl) {
      state.placement = placementId;
      if (els.placementValue) {
        els.placementValue.value = placementId;
      }
      if (els.placementRow && buttonEl) {
        els.placementRow.querySelectorAll('[data-mcp-placement-option]').forEach(function (btn) {
          btn.classList.toggle('mcp-studio__placement-option--active', btn === buttonEl);
        });
      } else if (els.placementRow) {
        els.placementRow.querySelectorAll('[data-mcp-placement-option]').forEach(function (btn) {
          btn.classList.toggle('mcp-studio__placement-option--active', btn.dataset.placementId === placementId);
        });
      }
      updateBlankPhoto();
      updatePlacementLabel();
      updatePrintArea();
      recomputeEstimate();
    }

    function selectMethod(methodId, labelEl) {
      state.method = methodId;
      if (els.methodValue) {
        els.methodValue.value = methodId;
      }
      if (els.methodList) {
        els.methodList.querySelectorAll('[data-mcp-method-option]').forEach(function (label) {
          label.classList.toggle('mcp-studio__method-option--active', label === labelEl);
        });
      }
      recomputeEstimate();
    }

    function selectSize(size, buttonEl) {
      if (els.sizeValue) {
        els.sizeValue.value = size;
      }
      if (els.sizeRow) {
        els.sizeRow.querySelectorAll('[data-mcp-size-option]').forEach(function (btn) {
          btn.classList.toggle('mcp-studio__size-option--active', btn === buttonEl);
        });
      }
    }

    function setQty(qty) {
      state.qty = Math.max(1, qty);
      if (els.qtyValue) {
        els.qtyValue.textContent = state.qty;
      }
      if (els.qtyInput) {
        els.qtyInput.value = state.qty;
      }
      recomputeEstimate();
    }

    function loadArtwork(file) {
      if (!file) {
        return;
      }
      var url = URL.createObjectURL(file);
      if (els.artworkPreview) {
        els.artworkPreview.src = url;
        els.artworkPreview.hidden = false;
      }
      if (els.printArea) {
        els.printArea.style.display = 'none';
      }
      if (els.dropzoneLabel) {
        els.dropzoneLabel.textContent = file.name;
      }
      if (els.dropzoneHint) {
        els.dropzoneHint.textContent = ((file.size / 1048576).toFixed(1)) + ' MB · click to replace';
      }
      updatePrintArea();
    }

    // Blank picker
    if (els.blankGrid) {
      els.blankGrid.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-mcp-blank-option]');
        if (btn) {
          selectBlank(btn.dataset.blankId, btn);
        }
      });
    }

    // Size picker
    if (els.sizeRow) {
      els.sizeRow.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-mcp-size-option]');
        if (btn && !btn.disabled) {
          selectSize(btn.dataset.size, btn);
        }
      });
    }

    // Placement picker
    if (els.placementRow) {
      els.placementRow.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-mcp-placement-option]');
        if (btn) {
          selectPlacement(btn.dataset.placementId, btn);
        }
      });
    }

    // Method picker
    if (els.methodList) {
      els.methodList.addEventListener('click', function (e) {
        var label = e.target.closest('[data-mcp-method-option]');
        if (label) {
          selectMethod(label.dataset.methodId, label);
        }
      });
    }

    // Quantity stepper
    if (els.qtyDecrement) {
      els.qtyDecrement.addEventListener('click', function () {
        setQty(state.qty - 1);
      });
    }
    if (els.qtyIncrement) {
      els.qtyIncrement.addEventListener('click', function () {
        setQty(state.qty + 1);
      });
    }

    // Scale slider
    if (els.scaleInput) {
      els.scaleInput.addEventListener('input', function (e) {
        state.scale = parseInt(e.target.value, 10);
        updatePrintArea();
      });
    }

    // Dropzone: click-to-browse + drag & drop
    if (els.dropzone && els.fileInput) {
      els.dropzone.addEventListener('click', function () {
        els.fileInput.click();
      });
      els.fileInput.addEventListener('change', function (e) {
        loadArtwork(e.target.files[0]);
      });
      ['dragenter', 'dragover'].forEach(function (evt) {
        els.dropzone.addEventListener(evt, function (e) {
          e.preventDefault();
          els.dropzone.classList.add('mcp-studio__dropzone--active');
        });
      });
      ['dragleave', 'drop'].forEach(function (evt) {
        els.dropzone.addEventListener(evt, function (e) {
          e.preventDefault();
          els.dropzone.classList.remove('mcp-studio__dropzone--active');
        });
      });
      els.dropzone.addEventListener('drop', function (e) {
        var file = e.dataTransfer.files[0];
        if (file) {
          els.fileInput.files = e.dataTransfer.files;
          loadArtwork(file);
        }
      });
    }

    // Initial render
    if (state.blank) {
      updateBlankPhoto();
    }
    updatePlacementLabel();
    updatePrintArea();
    recomputeEstimate();
  });
})();
