/**
 * Sales Notification — Frontend JavaScript Engine
 * Version: 1.0.0
 * Pure vanilla JS — zero dependencies.
 *
 * Responsibilities:
 *  - Apply design tokens from settings to CSS custom properties
 *  - Build the notification popup DOM
 *  - Manage the display queue (delay, duration, interval, loop, max)
 *  - Handle cookie deduplication
 *  - GDPR consent detection
 *  - Batch analytics reporting via AJAX
 *  - Dispatch custom JS events on document
 *  - Device visibility (desktop/mobile breakpoint)
 */

( function () {
  'use strict';

  /* ------------------------------------------------------------------ */
  /* 1. Guard & Data                                                      */
  /* ------------------------------------------------------------------ */
  if ( typeof window.snData === 'undefined' ) return;

  const D   = window.snData;
  const S   = D.settings   || {};
  const N   = D.notifications || [];

  if ( ! N.length ) return;

  /* ------------------------------------------------------------------ */
  /* 2. Device Visibility Check                                           */
  /* ------------------------------------------------------------------ */
  const isMobile = window.innerWidth < ( S.mobile_breakpoint || 768 );
  if ( isMobile && ! S.show_mobile )  return;
  if ( ! isMobile && ! S.show_desktop ) return;

  /* ------------------------------------------------------------------ */
  /* 3. GDPR Consent Gate                                                 */
  /* ------------------------------------------------------------------ */
  let consentGranted = ! S.gdpr_mode;

  function checkConsent() {
    if ( consentGranted ) return true;

    // CookieYes
    if ( S.consent_plugins.includes( 'cookieyes' ) && window.getCkyConsent ) {
      consentGranted = window.getCkyConsent().categories.analytics === true;
    }
    // Complianz
    if ( S.consent_plugins.includes( 'complianz' ) && window.cmplz_has_consent ) {
      consentGranted = window.cmplz_has_consent( 'statistics' );
    }
    // GDPR Cookie Consent (WebToffee)
    if ( S.consent_plugins.includes( 'gdpr-cookie-consent' ) && window.getCookie ) {
      consentGranted = document.cookie.includes( 'cookielawinfo-checkbox-analytics=yes' );
    }

    return consentGranted;
  }

  /* ------------------------------------------------------------------ */
  /* 4. Cookie Helpers                                                    */
  /* ------------------------------------------------------------------ */
  function setCookie( name, value, days ) {
    if ( S.gdpr_mode && ! checkConsent() ) return;
    let expires = '';
    if ( days && days !== 'session' ) {
      const d = new Date();
      d.setTime( d.getTime() + ( parseInt( days, 10 ) * 86400000 ) );
      expires = '; expires=' + d.toUTCString();
    }
    document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
  }

  function getCookie( name ) {
    const value = '; ' + document.cookie;
    const parts = value.split( '; ' + name + '=' );
    if ( parts.length === 2 ) return parts.pop().split( ';' ).shift();
    return null;
  }

  function wasShown( notificationId ) {
    return getCookie( 'sn_shown_' + notificationId ) === '1';
  }

  function markShown( notificationId ) {
    const expiry = S.cookie_expiry === 'session' ? null : S.cookie_expiry;
    setCookie( 'sn_shown_' + notificationId, '1', expiry );
  }

  /* ------------------------------------------------------------------ */
  /* 5. Analytics Batch                                                   */
  /* ------------------------------------------------------------------ */
  const pendingEvents = [];
  let batchTimer     = null;

  function trackEvent( type, notification ) {
    if ( ! S.enable_analytics ) return;

    pendingEvents.push( {
      notification_id: notification.id,
      product_id:      notification.product_id || 0,
      event_type:      type,
      page_url:        window.location.href,
    } );

    clearTimeout( batchTimer );
    batchTimer = setTimeout( flushAnalytics, 10000 );
  }

  function flushAnalytics() {
    if ( ! pendingEvents.length ) return;
    const events = pendingEvents.splice( 0 );

    const fd = new FormData();
    fd.append( 'action', 'sn_track_event' );
    fd.append( 'nonce', D.nonce );
    events.forEach( function ( ev, i ) {
      Object.entries( ev ).forEach( function ( [k, v] ) {
        fd.append( 'events[' + i + '][' + k + ']', v );
      } );
    } );

    navigator.sendBeacon
      ? navigator.sendBeacon( D.ajaxUrl, fd )
      : fetch( D.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } );
  }

  // Flush on page unload.
  window.addEventListener( 'pagehide', flushAnalytics );
  window.addEventListener( 'beforeunload', flushAnalytics );

  /* ------------------------------------------------------------------ */
  /* 6. Dispatch Custom Events                                            */
  /* ------------------------------------------------------------------ */
  function dispatch( name, detail ) {
    document.dispatchEvent( new CustomEvent( 'sn:' + name, { detail: detail, bubbles: true } ) );
  }

  /* ------------------------------------------------------------------ */
  /* 7. CSS Design Tokens Application                                     */
  /* ------------------------------------------------------------------ */
  function applyDesignTokens() {
    const root = document.documentElement;
    const map  = {
      '--sn-bg':              S.color_bg,
      '--sn-color-text':      S.color_text,
      '--sn-color-secondary': S.color_text_secondary,
      '--sn-color-accent':    S.color_accent,
      '--sn-color-border':    S.color_border,
      '--sn-border-width':    S.border_width + 'px',
      '--sn-border-radius':   S.border_radius + 'px',
      '--sn-font-family':     S.font_family,
      '--sn-font-size':       S.font_size + 'px',
      '--sn-font-weight':     S.font_weight,
      '--sn-image-size':      S.image_size + 'px',
      '--sn-max-width':       S.max_width + 'px',
    };
    Object.entries( map ).forEach( function ( [prop, val] ) {
      if ( val ) root.style.setProperty( prop, val );
    } );
  }

  /* ------------------------------------------------------------------ */
  /* 8. DOM Builder                                                       */
  /* ------------------------------------------------------------------ */
  function buildPopup( notification ) {
    const popup = document.createElement( 'div' );
    popup.className = [
      'sn-popup',
      'sn-template-' + S.template,
      'sn-pos-' + S.position,
      'sn-shadow-' + ( S.box_shadow || 'soft' ),
    ].join( ' ' );
    popup.setAttribute( 'role', 'alert' );
    popup.setAttribute( 'aria-live', 'polite' );
    popup.setAttribute( 'data-id', notification.id );
    popup.setAttribute( 'data-product-id', notification.product_id || 0 );

    // Close button.
    if ( S.show_close ) {
      const closeBtn = document.createElement( 'button' );
      closeBtn.className   = 'sn-popup__close';
      closeBtn.textContent = '×';
      closeBtn.setAttribute( 'aria-label', 'Close notification' );
      closeBtn.addEventListener( 'click', function ( e ) {
        e.stopPropagation();
        hidePopup( popup, 'dismiss', notification );
      } );
      popup.appendChild( closeBtn );
    }

    // Inner wrapper.
    const inner = document.createElement( 'div' );
    inner.className = 'sn-popup__inner';

    // Image.
    if ( S.show_image && notification.product_image ) {
      const imgWrap = document.createElement( 'div' );
      imgWrap.className = 'sn-popup__image-wrap';
      const img = document.createElement( 'img' );
      img.className = 'sn-popup__image';
      img.src       = notification.product_image;
      img.alt       = notification.product_name || '';
      img.width     = S.image_size;
      img.height    = S.image_size;
      img.loading   = 'lazy';
      img.addEventListener( 'error', function () { imgWrap.style.display = 'none'; } );
      imgWrap.appendChild( img );
      inner.appendChild( imgWrap );
    }

    // Avatar.
    if ( S.show_avatar && notification.avatar_url ) {
      const avatarImg = document.createElement( 'img' );
      avatarImg.className = 'sn-popup__avatar';
      avatarImg.src       = notification.avatar_url;
      avatarImg.alt       = '';
      avatarImg.width     = S.image_size;
      avatarImg.height    = S.image_size;
      avatarImg.addEventListener( 'error', function () {
        // Replace with initials on error.
        const initials  = document.createElement( 'div' );
        initials.className   = 'sn-popup__avatar-initials';
        initials.textContent = ( notification.name || 'S' ).charAt( 0 ).toUpperCase();
        avatarImg.replaceWith( initials );
      } );
      inner.insertBefore( avatarImg, inner.firstChild );
    }

    // Content.
    const content = document.createElement( 'div' );
    content.className = 'sn-popup__content';

    if ( S.show_name ) {
      const p = document.createElement( 'p' );
      p.className = 'sn-popup__name';
      p.innerHTML = '<strong>' + escapeHtml( notification.name ) + '</strong> purchased';
      content.appendChild( p );
    }

    if ( notification.product_name ) {
      const prod = document.createElement( 'p' );
      prod.className = 'sn-popup__product';
      if ( notification.product_url && notification.product_url !== '#' ) {
        const a = document.createElement( 'a' );
        a.href       = notification.product_url;
        a.textContent = notification.product_name;
        a.addEventListener( 'click', function ( e ) { e.stopPropagation(); } );
        prod.appendChild( a );
      } else {
        prod.textContent = notification.product_name;
      }
      content.appendChild( prod );
    }

    const meta = document.createElement( 'p' );
    meta.className = 'sn-popup__meta';

    if ( S.show_location && notification.location ) {
      const loc = document.createElement( 'span' );
      loc.className   = 'sn-popup__location';
      loc.textContent = notification.location;
      meta.appendChild( loc );
    }

    if ( S.show_time && notification.time_ago ) {
      const time = document.createElement( 'span' );
      time.className   = 'sn-popup__time';
      time.textContent = notification.time_ago;
      meta.appendChild( time );
    }

    if ( meta.children.length ) {
      content.appendChild( meta );
    }

    inner.appendChild( content );
    popup.appendChild( inner );

    // Click anywhere on popup → navigate to product.
    popup.addEventListener( 'click', function () {
      trackEvent( 'click', notification );
      dispatch( 'click', { notification: notification, target: popup } );
      if ( notification.product_url && notification.product_url !== '#' ) {
        window.location.href = notification.product_url;
      }
    } );

    return popup;
  }

  /* ------------------------------------------------------------------ */
  /* 9. Show / Hide                                                       */
  /* ------------------------------------------------------------------ */
  function showPopup( popup, notification, index ) {
    applyAnimationClass( popup, 'in' );
    document.body.appendChild( popup );
    dispatch( 'show', { notification: notification, index: index } );

    // Record impression after 1s of visibility.
    setTimeout( function () {
      if ( document.body.contains( popup ) ) {
        trackEvent( 'impression', notification );
        markShown( notification.id );
      }
    }, 1000 );
  }

  function hidePopup( popup, reason, notification ) {
    popup.classList.add( 'sn-exiting' );
    applyAnimationClass( popup, 'out' );
    dispatch( reason === 'dismiss' ? 'dismiss' : 'hide', { notification: notification, reason: reason } );

    const dur = reason === 'dismiss' ? 250 : 250;
    setTimeout( function () {
      if ( document.body.contains( popup ) ) {
        document.body.removeChild( popup );
      }
    }, dur + 50 );
  }

  function applyAnimationClass( popup, direction ) {
    const anim = direction === 'in' ? S.animation_in : S.animation_out;
    if ( ! anim || anim === 'none' ) return;
    popup.classList.add( 'sn-anim-' + direction + '-' + anim );
  }

  /* ------------------------------------------------------------------ */
  /* 10. Queue Engine                                                      */
  /* ------------------------------------------------------------------ */
  let queue         = [];
  let queueIndex    = 0;
  let shownCount    = 0;
  let currentPopup  = null;
  let running       = false;

  function buildQueue() {
    // Filter already-shown notifications (cookie check).
    return N.filter( function ( n ) { return ! wasShown( n.id ); } );
  }

  function next() {
    if ( ! running ) return;
    if ( shownCount >= S.max_count ) {
      dispatch( 'queue_end', { total_shown: shownCount } );
      return;
    }

    if ( queueIndex >= queue.length ) {
      if ( S.loop ) {
        queueIndex = 0;
        queue = N.slice(); // Reset queue (include all on loop).
      } else {
        dispatch( 'queue_end', { total_shown: shownCount } );
        return;
      }
    }

    const notification = queue[ queueIndex++ ];
    if ( ! notification ) {
      next();
      return;
    }

    shownCount++;
    const popup = buildPopup( notification );
    currentPopup = popup;
    showPopup( popup, notification, shownCount );

    // Auto-hide after duration.
    setTimeout( function () {
      if ( document.body.contains( popup ) ) {
        hidePopup( popup, 'auto', notification );
        setTimeout( next, ( S.interval || 10 ) * 1000 );
      }
    }, ( S.duration || 6 ) * 1000 );
  }

  function start() {
    if ( S.gdpr_mode && ! checkConsent() ) {
      // Retry after a short delay to wait for CMP to initialise.
      setTimeout( start, 2000 );
      return;
    }

    queue   = buildQueue();
    running = true;
    dispatch( 'ready', { count: queue.length, settings: S } );

    if ( S.debug_mode ) {
      console.log( '[SalesNotification] Queue ready. Notifications:', queue.length );
    }

    setTimeout( next, ( S.initial_delay || 5 ) * 1000 );
  }

  /* ------------------------------------------------------------------ */
  /* 11. Init                                                             */
  /* ------------------------------------------------------------------ */
  function escapeHtml( str ) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String( str || '' ).replace( /[&<>"']/g, function ( m ) { return map[ m ]; } );
  }

  applyDesignTokens();
  start();

} )();
