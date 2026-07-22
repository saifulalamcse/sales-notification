/**
 * Sales Notification — Admin JavaScript
 * Version: 1.0.0
 *
 * Handles:
 *  - Settings form AJAX save (all tabs)
 *  - Color picker initialisation (wp-color-picker / Iris)
 *  - Range ↔ number input sync
 *  - Live preview panel updates
 *  - Template / position selector visual states
 *  - Import / Export (file drag-and-drop + AJAX)
 *  - Reset to defaults
 *  - Demo notifications CRUD (add/edit/delete modal)
 *  - Analytics chart (simple canvas chart — no Chart.js dependency)
 *  - Admin notices
 */

/* global jQuery, snAdmin, wp */
( function ( $, config ) {
  'use strict';

  const nonce  = config.nonce;
  const ajax   = config.ajaxUrl;
  const s      = config.settings || {};
  const i18n   = config.i18n || {};

  /* ------------------------------------------------------------------ */
  /* Notices                                                              */
  /* ------------------------------------------------------------------ */
  function showNotice( message, type ) {
    const container = document.getElementById( 'sn-notices' );
    if ( ! container ) return;
    const div = document.createElement( 'div' );
    div.className   = 'sn-notice sn-notice--' + ( type || 'success' );
    div.textContent = message;
    container.innerHTML = '';
    container.appendChild( div );
    setTimeout( function () { div.style.opacity = '0'; div.style.transition = 'opacity 500ms'; }, 3000 );
    setTimeout( function () { div.remove(); }, 3600 );
  }

  /* ------------------------------------------------------------------ */
  /* Settings Form — AJAX Save                                            */
  /* ------------------------------------------------------------------ */
  $( document ).on( 'submit', '#sn-settings-form', function ( e ) {
    e.preventDefault();
    const $form = $( this );
    const $btn  = $form.find( '#sn-save-btn' );
    const data  = {};

    // Serialize form to object.
    $form.find( '[name]' ).each( function () {
      const el    = this;
      const key   = el.name.replace( /^settings\[/, '' ).replace( /\](\[.*)?$/, '' );
      const key2  = el.name; // full name for array handling.

      if ( el.type === 'checkbox' ) {
        if ( el.name.includes( '[]' ) ) {
          // Multi-checkbox arrays.
          const arrKey = el.name.replace( 'settings[', '' ).replace( '][]', '' );
          if ( ! Array.isArray( data[ arrKey ] ) ) data[ arrKey ] = [];
          if ( el.checked ) data[ arrKey ].push( el.value );
        } else {
          data[ key ] = el.checked ? 1 : 0;
        }
      } else if ( el.type === 'radio' ) {
        if ( el.checked ) data[ key ] = el.value;
      } else if ( el.tagName === 'SELECT' && el.multiple ) {
        const arrKey = el.name.replace( 'settings[', '' ).replace( '][]', '' );
        data[ arrKey ] = Array.from( el.selectedOptions ).map( function ( o ) { return o.value; } );
      } else {
        data[ key ] = el.value;
      }
    } );

    $btn.prop( 'disabled', true ).text( '…' );

    $.ajax( {
      url:    ajax,
      type:   'POST',
      data:   { action: 'sn_save_settings', nonce: nonce, settings: data },
      success: function ( res ) {
        if ( res.success ) {
          showNotice( res.data.message || i18n.saved, 'success' );
        } else {
          showNotice( ( res.data && res.data.message ) || i18n.error, 'error' );
        }
      },
      error: function () { showNotice( i18n.error, 'error' ); },
      complete: function () {
        $btn.prop( 'disabled', false ).html( '<span class="sn-btn__icon dashicons dashicons-saved"></span> ' + ( snAdmin.i18n.saved || 'Save Settings' ) );
      },
    } );
  } );

  /* ------------------------------------------------------------------ */
  /* Color Pickers (wp-color-picker / Iris)                              */
  /* ------------------------------------------------------------------ */
  if ( $.fn.wpColorPicker ) {
    $( '.sn-color-picker' ).wpColorPicker( {
      change: function ( event, ui ) {
        updatePreview();
      },
      clear: function () {
        updatePreview();
      },
    } );
  }

  /* ------------------------------------------------------------------ */
  /* Range ↔ Number Sync                                                  */
  /* ------------------------------------------------------------------ */
  $( document ).on( 'input', '.sn-range', function () {
    const targetId = $( this ).data( 'target' );
    const $target  = $( '#' + targetId );
    if ( $target.length ) {
      $target.val( this.value );
      updatePreview();
    }
  } );

  $( document ).on( 'input', '.sn-input-sm', function () {
    const id     = this.id;
    const $range = $( '.sn-range[data-target="' + id + '"]' );
    if ( $range.length ) {
      $range.val( this.value );
      updatePreview();
    }
  } );

  /* ------------------------------------------------------------------ */
  /* Template Selector — visual active state                              */
  /* ------------------------------------------------------------------ */
  $( document ).on( 'change', '[name="settings[template]"]', function () {
    $( '.sn-template-option' ).removeClass( 'sn-template-option--active' );
    $( this ).closest( '.sn-template-option' ).addClass( 'sn-template-option--active' );
    updatePreview();
  } );

  /* ------------------------------------------------------------------ */
  /* Position Selector — visual active state                              */
  /* ------------------------------------------------------------------ */
  $( document ).on( 'change', '[name="settings[position]"]', function () {
    $( '.sn-position-btn' ).removeClass( 'sn-position-btn--active' );
    $( this ).closest( '.sn-position-btn' ).addClass( 'sn-position-btn--active' );
    updatePreview();
  } );

  /* ------------------------------------------------------------------ */
  /* Live Preview Update                                                  */
  /* ------------------------------------------------------------------ */
  function updatePreview() {
    const preview = document.getElementById( 'sn-live-preview' );
    if ( ! preview ) return;

    const get = function ( id ) {
      const el = document.getElementById( id );
      return el ? el.value : null;
    };

    // Colors.
    const colorMap = {
      '--sn-bg':              'sn_color_bg',
      '--sn-color-text':      'sn_color_text',
      '--sn-color-secondary': 'sn_color_text_secondary',
      '--sn-color-accent':    'sn_color_accent',
      '--sn-color-border':    'sn_color_border',
    };

    Object.entries( colorMap ).forEach( function ( [prop, id] ) {
      const val = get( id );
      if ( val ) preview.style.setProperty( prop, val );
    } );

    // Shape.
    const br = get( 'sn_border_radius' );
    if ( br !== null ) preview.style.setProperty( '--sn-border-radius', br + 'px' );

    const bw = get( 'sn_border_width' );
    if ( bw !== null ) preview.style.borderWidth = bw + 'px';

    const mw = get( 'sn_max_width' );
    if ( mw !== null ) preview.style.setProperty( '--sn-max-width', mw + 'px' );

    const is = get( 'sn_image_size' );
    if ( is !== null ) preview.style.setProperty( '--sn-image-size', is + 'px' );

    // Shadow.
    const shadow = get( 'sn_box_shadow' );
    if ( shadow ) {
      preview.classList.remove( 'sn-shadow-none', 'sn-shadow-soft', 'sn-shadow-medium', 'sn-shadow-strong' );
      preview.classList.add( 'sn-shadow-' + shadow );
    }

    // Typography.
    const ff = get( 'sn_font_family' );
    if ( ff ) preview.style.setProperty( '--sn-font-family', ff );

    const fs = get( 'sn_font_size' );
    if ( fs ) preview.style.setProperty( '--sn-font-size', fs + 'px' );

    const fw = get( 'sn_font_weight' );
    if ( fw ) preview.style.setProperty( '--sn-font-weight', fw );

    // Template.
    const template = $( '[name="settings[template]"]:checked' ).val();
    if ( template ) {
      preview.classList.remove( 'sn-template-1', 'sn-template-2', 'sn-template-3' );
      preview.classList.add( 'sn-template-' + template );
    }
  }

  // Run on load.
  updatePreview();

  // Re-run on any form change.
  $( '#sn-settings-form' ).on( 'change', 'select, input[type="number"]', updatePreview );

  /* ------------------------------------------------------------------ */
  /* Import / Export                                                      */
  /* ------------------------------------------------------------------ */
  let importFileContent = null;

  // Export.
  $( document ).on( 'click', '#sn-export-btn', function () {
    $.ajax( {
      url:    ajax,
      type:   'POST',
      data:   { action: 'sn_export_settings', nonce: nonce },
      success: function ( res ) {
        if ( ! res.success ) { showNotice( i18n.error, 'error' ); return; }
        const blob = new Blob( [ res.data.json ], { type: 'application/json' } );
        const url  = URL.createObjectURL( blob );
        const a    = document.createElement( 'a' );
        a.href     = url;
        a.download = res.data.filename;
        a.click();
        URL.revokeObjectURL( url );
      },
    } );
  } );

  // File input.
  $( document ).on( 'change', '#sn-import-file', function () {
    const file = this.files[0];
    if ( ! file ) return;
    readImportFile( file );
  } );

  // Drag and drop.
  const dropZone = document.getElementById( 'sn-import-drop-zone' );
  if ( dropZone ) {
    dropZone.addEventListener( 'click', function () {
      document.getElementById( 'sn-import-file' ).click();
    } );
    dropZone.addEventListener( 'dragover', function ( e ) {
      e.preventDefault();
      this.classList.add( 'sn-drag-over' );
    } );
    dropZone.addEventListener( 'dragleave', function () {
      this.classList.remove( 'sn-drag-over' );
    } );
    dropZone.addEventListener( 'drop', function ( e ) {
      e.preventDefault();
      this.classList.remove( 'sn-drag-over' );
      const file = e.dataTransfer.files[0];
      if ( file ) readImportFile( file );
    } );
  }

  function readImportFile( file ) {
    const reader = new FileReader();
    reader.onload = function ( e ) {
      try {
        JSON.parse( e.target.result ); // Validate JSON.
        importFileContent = e.target.result;
        $( '#sn-import-preview' ).show();
        $( '.sn-import-filename' ).text( '📄 ' + file.name );
      } catch ( err ) {
        showNotice( i18n.importError, 'error' );
      }
    };
    reader.readAsText( file );
  }

  $( document ).on( 'click', '#sn-import-btn', function () {
    if ( ! importFileContent ) return;
    $.ajax( {
      url:    ajax,
      type:   'POST',
      data:   { action: 'sn_import_settings', nonce: nonce, json: importFileContent },
      success: function ( res ) {
        if ( res.success ) {
          showNotice( res.data.message || i18n.importSuccess, 'success' );
          setTimeout( function () { window.location.reload(); }, 1200 );
        } else {
          showNotice( ( res.data && res.data.message ) || i18n.importError, 'error' );
        }
      },
    } );
  } );

  $( document ).on( 'click', '#sn-import-cancel', function () {
    $( '#sn-import-preview' ).hide();
    $( '#sn-import-file' ).val( '' );
    importFileContent = null;
  } );

  // Reset.
  $( document ).on( 'click', '#sn-reset-btn', function () {
    if ( ! window.confirm( i18n.confirmReset ) ) return;
    $.ajax( {
      url:    ajax,
      type:   'POST',
      data:   { action: 'sn_reset_settings', nonce: nonce },
      success: function ( res ) {
        if ( res.success ) {
          showNotice( res.data.message, 'success' );
          setTimeout( function () { window.location.reload(); }, 1000 );
        }
      },
    } );
  } );

  /* ------------------------------------------------------------------ */
  /* Demo Notifications CRUD                                              */
  /* ------------------------------------------------------------------ */
  // Open modal — Add.
  $( document ).on( 'click', '#sn-add-demo-btn', function () {
    $( '#sn-demo-id' ).val( 0 );
    $( '#sn-demo-name' ).val( '' );
    $( '#sn-demo-product' ).val( '' );
    $( '#sn-demo-location' ).val( '' );
    $( '#sn-demo-avatar' ).val( '' );
    $( '#sn-demo-offset' ).val( 3600 );
    $( '#sn-modal-title' ).text( 'Add Demo Notification' );
    $( '#sn-demo-modal' ).show();
  } );

  // Open modal — Edit.
  $( document ).on( 'click', '.sn-edit-demo', function () {
    const d = $( this ).data();
    $( '#sn-demo-id' ).val( d.id );
    $( '#sn-demo-name' ).val( d.name );
    $( '#sn-demo-product' ).val( d.product );
    $( '#sn-demo-location' ).val( d.location );
    $( '#sn-demo-avatar' ).val( d.avatar );
    $( '#sn-demo-offset' ).val( d.offset );
    $( '#sn-modal-title' ).text( 'Edit Demo Notification' );
    $( '#sn-demo-modal' ).show();
  } );

  // Close modal.
  $( document ).on( 'click', '.sn-modal__close, .sn-modal__overlay', function () {
    $( '#sn-demo-modal' ).hide();
  } );

  // Save demo notification.
  $( document ).on( 'click', '#sn-demo-save', function () {
    const id       = $( '#sn-demo-id' ).val();
    const name     = $.trim( $( '#sn-demo-name' ).val() );
    const product  = $( '#sn-demo-product' ).val();
    if ( ! name || ! product ) {
      showNotice( 'Customer name and product are required.', 'error' );
      return;
    }
    $.ajax( {
      url:    ajax,
      type:   'POST',
      data:   {
        action:        'sn_save_demo_notification',
        nonce:         $( '#sn-nonce' ).val(),
        id:            id,
        customer_name: name,
        product_id:    product,
        location:      $( '#sn-demo-location' ).val(),
        avatar_url:    $( '#sn-demo-avatar' ).val(),
        time_offset:   $( '#sn-demo-offset' ).val(),
      },
      success: function ( res ) {
        if ( res.success ) {
          $( '#sn-demo-modal' ).hide();
          window.location.reload();
        } else {
          showNotice( i18n.error, 'error' );
        }
      },
    } );
  } );

  // Delete demo notification.
  $( document ).on( 'click', '.sn-delete-demo', function () {
    if ( ! window.confirm( i18n.confirmDelete ) ) return;
    const id  = $( this ).data( 'id' );
    const row = $( this ).closest( 'tr' );
    $.ajax( {
      url:    ajax,
      type:   'POST',
      data:   { action: 'sn_delete_demo_notification', nonce: $( '#sn-nonce' ).val(), id: id },
      success: function ( res ) {
        if ( res.success ) {
          row.fadeOut( 300, function () { row.remove(); } );
        }
      },
    } );
  } );

  /* ------------------------------------------------------------------ */
  /* Analytics Chart (vanilla canvas — no external dependency)           */
  /* ------------------------------------------------------------------ */
  const chartCanvas = document.getElementById( 'sn-analytics-chart' );
  if ( chartCanvas ) {
    const chartData = JSON.parse( chartCanvas.getAttribute( 'data-chart' ) || 'null' );
    if ( chartData && chartData.labels ) {
      drawSimpleChart( chartCanvas, chartData );
    }
  }

  function drawSimpleChart( canvas, data ) {
    const ctx    = canvas.getContext( '2d' );
    const W      = canvas.parentElement.clientWidth || 600;
    const H      = 160;
    canvas.width  = W;
    canvas.height = H;

    const labels     = data.labels || [];
    const impr       = data.impressions || [];
    const clicks     = data.clicks || [];
    const maxVal     = Math.max( ...impr, ...clicks, 1 );
    const padL       = 40;
    const padR       = 16;
    const padT       = 16;
    const padB       = 28;
    const chartW     = W - padL - padR;
    const chartH     = H - padT - padB;
    const step       = chartW / Math.max( labels.length - 1, 1 );

    const toX = function ( i ) { return padL + i * step; };
    const toY = function ( v ) { return padT + chartH - ( v / maxVal ) * chartH; };

    // Grid lines.
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth   = 1;
    for ( let i = 0; i <= 4; i++ ) {
      const y = padT + ( chartH / 4 ) * i;
      ctx.beginPath(); ctx.moveTo( padL, y ); ctx.lineTo( W - padR, y ); ctx.stroke();
      ctx.fillStyle   = '#94a3b8';
      ctx.font        = '10px sans-serif';
      ctx.textAlign   = 'right';
      ctx.fillText( Math.round( maxVal * ( 1 - i / 4 ) ), padL - 4, y + 4 );
    }

    // Draw a line series.
    function drawLine( values, color ) {
      ctx.beginPath();
      ctx.strokeStyle = color;
      ctx.lineWidth   = 2;
      ctx.lineJoin    = 'round';
      values.forEach( function ( v, i ) {
        if ( i === 0 ) ctx.moveTo( toX( i ), toY( v ) );
        else ctx.lineTo( toX( i ), toY( v ) );
      } );
      ctx.stroke();

      // Dots.
      values.forEach( function ( v, i ) {
        ctx.beginPath();
        ctx.arc( toX( i ), toY( v ), 3, 0, Math.PI * 2 );
        ctx.fillStyle = color;
        ctx.fill();
      } );
    }

    drawLine( impr, '#2563eb' );
    drawLine( clicks, '#16a34a' );

    // X axis labels (sparse).
    ctx.fillStyle  = '#94a3b8';
    ctx.font       = '10px sans-serif';
    ctx.textAlign  = 'center';
    const sparse   = Math.max( 1, Math.floor( labels.length / 7 ) );
    labels.forEach( function ( label, i ) {
      if ( i % sparse === 0 || i === labels.length - 1 ) {
        ctx.fillText( label, toX( i ), H - 6 );
      }
    } );

    // Legend.
    ctx.fillStyle = '#2563eb'; ctx.fillRect( padL, 4, 12, 8 );
    ctx.fillStyle = '#1e293b'; ctx.textAlign = 'left'; ctx.font = '11px sans-serif';
    ctx.fillText( 'Impressions', padL + 16, 12 );

    ctx.fillStyle = '#16a34a'; ctx.fillRect( padL + 100, 4, 12, 8 );
    ctx.fillStyle = '#1e293b';
    ctx.fillText( 'Clicks', padL + 116, 12 );
  }

  /* ------------------------------------------------------------------ */
  /* Source Radio — show/hide real-order fields                          */
  /* ------------------------------------------------------------------ */
  function updateSourceVisibility() {
    const source = $( '[name="settings[source]"]:checked' ).val();
    if ( source === 'real' ) {
      $( '.sn-depends-on-real' ).show();
    } else {
      $( '.sn-depends-on-real' ).hide();
    }
  }

  $( document ).on( 'change', '[name="settings[source]"]', updateSourceVisibility );
  updateSourceVisibility();

  /* ------------------------------------------------------------------ */
  /* Page Visibility — show/hide page selector                           */
  /* ------------------------------------------------------------------ */
  function updateVisibilitySelector() {
    const mode = $( '[name="settings[page_visibility_mode]"]:checked' ).val();
    $( '.sn-visibility-page-select' ).toggle( mode !== 'all' );
  }

  $( document ).on( 'change', '[name="settings[page_visibility_mode]"]', updateVisibilitySelector );
  updateVisibilitySelector();

} )( jQuery, window.snAdmin || {} );
