<?php
/**
 * Plugin Name: Split CAPTCHA
 * Plugin URI:  https://dascent.github.io/4ai/source/plugins/split-captcha/
 * Description: Two modes: Form Guard + Content Gate (AES-GCM client-side unlock). Honeypot, noise canvas, timing checks, IP lockout.
 * Version:     2.2.0
 * Author:      Dascent
 * License:     GPL-2.0+
 * Text Domain: split-captcha
 */

defined( 'ABSPATH' ) || exit;

define( 'SC_VER',          '2.2.0' );
define( 'SC_SLUG',         'split-captcha' );
define( 'SC_OPT',          'sc_options' );
define( 'SC_META',         'sc_enabled' );
define( 'SC_TRANS_PFX',    'sc_tok_' );
define( 'SC_WRAP_CLASS',   'sc-wrap' );
define( 'SC_BOX_CLASS',    'sc-box' );
define( 'SC_ERR_CLASS',    'sc-err' );
define( 'SC_LABEL_CLASS',  'sc-label' );
define( 'SC_INPUT_NAME',   'sc_input' );
define( 'SC_NONCE_ACT',    'sc_verify' );
define( 'SC_NONCE_FLD',    'sc_nonce' );
define( 'SC_POST_KEY',     'sc_answer' );
define( 'SC_HP_NAME',      'sc_hp_field' );
define( 'SC_TIME_KEY',     'sc_ts' );
define( 'SC_MIN_TIME',     2 );
define( 'SC_GATE_WRAP',    'sc-gate' );
define( 'SC_GATE_OVERLAY', 'sc-gate-overlay' );
define( 'SC_GATE_CONTENT', 'sc-gate-content' );
define( 'SC_GATE_BTN',     'sc-gate-btn' );
define( 'SC_GATE_MSG',     'sc-gate-msg' );
define( 'SC_GATE_TRIES',   'sc-gate-tries' );
define( 'SC_GATE_OPT',     'sc_gate_options' );

function sc_opts() {
    return wp_parse_args( get_option( SC_OPT, [] ), [
        'on_comments' => 1, 'on_cf7' => 0, 'on_login' => 0, 'on_register' => 0,
        'length' => 5, 'charset' => 'mixed', 'ttl' => 600, 'max_fail' => 5,
    ] );
}

function sc_gate_opts() {
    return wp_parse_args( get_option( SC_GATE_OPT, [] ), [
        'lock_style' => 'frosted', 'anim' => 'flicker', 'relock' => 0,
        'wrong_style' => 'shake', 'max_attempts' => 5,
        'hint_text' => 'Enter the code to unlock this content',
        'length' => 5, 'charset' => 'mixed',
    ] );
}

function sc_charset_pool( $t ) {
    return $t === 'alpha' ? 'ABCDEFGHJKLMNPQRSTUVWXYZ' : ( $t === 'numeric' ? '23456789' : 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789' );
}

function sc_generate( $length, $charset ) {
    $pool = sc_charset_pool( $charset );
    $code = '';
    $max  = strlen( $pool ) - 1;
    for ( $i = 0; $i < $length; $i++ ) $code .= $pool[ random_int( 0, $max ) ];
    return $code;
}

function sc_store_token( $code ) {
    $token = wp_generate_password( 32, false );
    set_transient( SC_TRANS_PFX . $token, strtoupper( $code ), sc_opts()['ttl'] );
    return $token;
}

function sc_verify_token( $token, $answer ) {
    $key    = SC_TRANS_PFX . sanitize_key( $token );
    $stored = get_transient( $key );
    if ( false === $stored ) return false;
    delete_transient( $key );
    return strtoupper( trim( $answer ) ) === $stored;
}

function sc_fail_key() { return 'sc_fails_' . md5( $_SERVER['REMOTE_ADDR'] ?? 'x' ); }

function sc_increment_fail() {
    $k = sc_fail_key(); $n = (int) get_transient( $k );
    set_transient( $k, $n + 1, 900 ); return $n + 1;
}

function sc_is_locked() {
    $max = (int) sc_opts()['max_fail'];
    return $max > 0 && (int) get_transient( sc_fail_key() ) >= $max;
}

function sc_post_enabled( $post_id ) {
    $meta = get_post_meta( $post_id, SC_META, true );
    if ( $meta === '' || $meta === false ) return null;
    return (bool) $meta;
}

function sc_gate_salt() {
    $salt = get_option( 'sc_gate_salt' );
    if ( ! $salt ) { $salt = bin2hex( random_bytes( 16 ) ); update_option( 'sc_gate_salt', $salt ); }
    return $salt;
}

function sc_gate_encrypt( $plaintext, $code ) {
    $key = hash( 'sha256', strtoupper( $code ) . sc_gate_salt(), true );
    $iv  = random_bytes( 12 );
    $tag = '';
    $enc = openssl_encrypt( $plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16 );
    if ( $enc === false ) return [ 'error' => true ];
    return [ 'blob' => base64_encode( $enc . $tag ), 'iv' => base64_encode( $iv ) ];
}

function sc_noise_svg() {
    $paths = '';
    for ( $i = 0; $i < 18; $i++ ) {
        $x1 = random_int( 0, 280 ); $y1 = random_int( 0, 80 );
        $x2 = random_int( 0, 280 ); $y2 = random_int( 0, 80 );
        $op = number_format( random_int( 8, 22 ) / 100, 2 );
        $paths .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="rgba(0,0,0,'.$op.')" stroke-width="1"/>';
    }
    for ( $i = 0; $i < 28; $i++ ) {
        $cx = random_int( 0, 280 ); $cy = random_int( 0, 80 );
        $r  = random_int( 1, 3 );
        $op = number_format( random_int( 10, 30 ) / 100, 2 );
        $paths .= '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="rgba(0,0,0,'.$op.')"/>';
    }
    for ( $i = 0; $i < 8; $i++ ) {
        $x   = random_int( 4, 270 ); $y = random_int( 10, 70 );
        $op  = number_format( random_int( 5, 15 ) / 100, 2 );
        $len = random_int( 12, 40 );
        $paths .= '<rect x="'.$x.'" y="'.$y.'" width="'.$len.'" height="2" fill="rgba(0,0,0,'.$op.')" rx="1"/>';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 280 80" preserveAspectRatio="xMidYMid slice" style="position:absolute;inset:0;pointer-events:none;z-index:2">'.$paths.'</svg>';
}

function sc_render_widget( $context = 'comment', $length = null, $charset = null, $code = null ) {
    if ( $context !== 'gate' && sc_is_locked() ) {
        return '<p class="'.SC_ERR_CLASS.'">'.esc_html__( 'Too many failed attempts. Please wait.', 'split-captcha' ).'</p>';
    }
    $o    = sc_opts();
    $len  = $length  ?? (int) $o['length'];
    $cset = $charset ?? $o['charset'];
    $code = $code    ?? sc_generate( $len, $cset );
    $chars = str_split( $code );
    $nonce = wp_create_nonce( SC_NONCE_ACT );
    $token = ( $context !== 'gate' ) ? sc_store_token( $code ) : '';
    $ts    = time();
    $hint  = $context === 'gate' ? esc_html( sc_gate_opts()['hint_text'] ) : esc_html__( 'Enter the code below', 'split-captcha' );

    ob_start(); ?>
<div class="<?= esc_attr( SC_WRAP_CLASS ) ?>" data-ctx="<?= esc_attr( $context ) ?>">
    <span class="<?= esc_attr( SC_LABEL_CLASS ) ?>"><?= $hint ?></span>
    <div class="sc-preview" aria-hidden="true">
        <?= sc_noise_svg() ?>
        <?php foreach ( $chars as $ch ):
            $np  = substr( md5( random_int( 1000, 9999 ) ), 0, random_int( 1, 3 ) );
            $ns  = substr( md5( random_int( 1000, 9999 ) ), 0, random_int( 1, 3 ) );
            $rot = random_int( -20, 20 );
            $sk  = random_int( -8, 8 );
            $sc  = number_format( random_int( 88, 108 ) / 100, 2 );
            $hue = random_int( 0, 360 );
        ?>
            <span class="sc-ch" style="--r:<?= $rot ?>deg;--sk:<?= $sk ?>deg;--sc:<?= $sc ?>;--c:hsl(<?= $hue ?>,55%,28%)">
                <span class="sc-noise" aria-hidden="true"><?= esc_html( $np ) ?></span>
                <span class="sc-real"><?= esc_html( $ch ) ?></span>
                <span class="sc-noise" aria-hidden="true"><?= esc_html( $ns ) ?></span>
            </span>
        <?php endforeach; ?>
    </div>
    <div class="sc-inputs" role="group" aria-label="<?= esc_attr__( 'CAPTCHA', 'split-captcha' ) ?>">
        <?php for ( $i = 0; $i < $len; $i++ ): ?>
            <input type="text"
                   class="<?= esc_attr( SC_BOX_CLASS ) ?>"
                   <?= $context !== 'gate' ? 'name="'.esc_attr( SC_INPUT_NAME ).'[]"' : '' ?>
                   maxlength="1" autocomplete="off" autocorrect="off"
                   autocapitalize="characters" spellcheck="false" inputmode="text"
                   aria-label="<?= esc_attr( sprintf( __( 'Character %d', 'split-captcha' ), $i + 1 ) ) ?>"
                   required />
        <?php endfor; ?>
    </div>
    <?php if ( $context !== 'gate' ): ?>
        <div class="sc-hp-wrap" aria-hidden="true">
            <input type="text" name="<?= esc_attr( SC_HP_NAME ) ?>" class="sc-hp" tabindex="-1" autocomplete="off" value="">
        </div>
        <input type="hidden" name="<?= esc_attr( SC_POST_KEY ) ?>"  value="<?= esc_attr( $token ) ?>">
        <input type="hidden" name="<?= esc_attr( SC_NONCE_FLD ) ?>" value="<?= esc_attr( $nonce ) ?>">
        <input type="hidden" name="<?= esc_attr( SC_TIME_KEY ) ?>"  value="<?= esc_attr( $ts ) ?>">
    <?php else: ?>
        <div class="<?= esc_attr( SC_GATE_TRIES ) ?>"></div>
        <p class="<?= esc_attr( SC_GATE_MSG ) ?>"></p>
        <button type="button" class="<?= esc_attr( SC_GATE_BTN ) ?>">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <?= esc_html__( 'Unlock', 'split-captcha' ) ?>
        </button>
    <?php endif; ?>
</div>
    <?php return ob_get_clean();
}

function sc_validate_submission( &$error_msg ) {
    if ( ! empty( $_POST[ SC_HP_NAME ] ) ) return false;

    $elapsed = time() - (int) ( $_POST[ SC_TIME_KEY ] ?? 0 );
    if ( $elapsed < SC_MIN_TIME ) {
        $error_msg = __( 'Submission too fast. Please try again.', 'split-captcha' );
        return false;
    }

    $nonce = sanitize_key( $_POST[ SC_NONCE_FLD ] ?? '' );
    if ( ! wp_verify_nonce( $nonce, SC_NONCE_ACT ) ) {
        $error_msg = __( 'Security check failed. Please reload.', 'split-captcha' );
        return false;
    }

    $parts  = (array) ( $_POST[ SC_INPUT_NAME ] ?? [] );
    $answer = implode( '', array_map( 'sanitize_text_field', $parts ) );
    $token  = sanitize_key( $_POST[ SC_POST_KEY ] ?? '' );

    if ( ! sc_verify_token( $token, $answer ) ) {
        sc_increment_fail();
        $error_msg = __( 'Incorrect CAPTCHA. Please try again.', 'split-captcha' );
        return false;
    }
    return true;
}

add_action( 'comment_form_after_fields', function() {
    if ( ! sc_opts()['on_comments'] ) return;
    if ( sc_post_enabled( get_the_ID() ) === false ) return;
    echo sc_render_widget( 'comment' );
} );

add_filter( 'preprocess_comment', function( $data ) {
    if ( ! sc_opts()['on_comments'] ) return $data;
    if ( sc_post_enabled( (int)( $data['comment_post_ID'] ?? 0 ) ) === false ) return $data;
    $err = '';
    if ( ! sc_validate_submission( $err ) ) wp_die( esc_html( $err ), esc_html__( 'CAPTCHA Error', 'split-captcha' ), [ 'back_link' => true ] );
    return $data;
} );

add_action( 'wpcf7_init', function() {
    if ( ! sc_opts()['on_cf7'] ) return;
    add_filter( 'wpcf7_form_elements', function( $html ) {
        return strpos( $html, '</form>' ) !== false ? str_replace( '</form>', sc_render_widget( 'cf7' ).'</form>', $html ) : $html;
    } );
} );

add_filter( 'wpcf7_validate', function( $result, $tags ) {
    if ( ! sc_opts()['on_cf7'] ) return $result;
    $err = '';
    if ( ! sc_validate_submission( $err ) ) $result->invalidate( (object)[ 'name' => 'captcha' ], $err );
    return $result;
}, 10, 2 );

add_action( 'login_form', function() {
    if ( sc_opts()['on_login'] ) echo sc_render_widget( 'login' );
} );

add_filter( 'authenticate', function( $user, $username, $password ) {
    if ( ! sc_opts()['on_login'] || empty( $_POST['log'] ) ) return $user;
    $err = '';
    if ( ! sc_validate_submission( $err ) ) return new WP_Error( 'sc_failed', esc_html( $err ) );
    return $user;
}, 30, 3 );

add_action( 'register_form', function() {
    if ( sc_opts()['on_register'] ) echo sc_render_widget( 'register' );
} );

add_filter( 'registration_errors', function( $errors, $login, $email ) {
    if ( ! sc_opts()['on_register'] ) return $errors;
    $err = '';
    if ( ! sc_validate_submission( $err ) ) $errors->add( 'sc_failed', esc_html( $err ) );
    return $errors;
}, 10, 3 );

add_shortcode( 'split_captcha', function( $atts, $content = '' ) {
    if ( empty( $content ) ) return '';
    $go     = sc_gate_opts();
    $len    = (int) $go['length'];
    $cset   = $go['charset'];
    $code   = sc_generate( $len, $cset );
    $crypto = sc_gate_encrypt( do_shortcode( $content ), $code );
    if ( isset( $crypto['error'] ) ) return '<p class="'.SC_ERR_CLASS.'">[Split CAPTCHA: openssl error]</p>';

    ob_start(); ?>
<div class="<?= esc_attr( SC_GATE_WRAP ) ?>"
     data-blob="<?= esc_attr( $crypto['blob'] ) ?>"
     data-iv="<?= esc_attr( $crypto['iv'] ) ?>"
     data-salt="<?= esc_attr( sc_gate_salt() ) ?>"
     data-anim="<?= esc_attr( $go['anim'] ) ?>"
     data-wrong="<?= esc_attr( $go['wrong_style'] ) ?>"
     data-maxatt="<?= esc_attr( (int)$go['max_attempts'] ) ?>"
     data-relock="<?= esc_attr( (int)$go['relock'] ) ?>">
    <div class="<?= esc_attr( SC_GATE_OVERLAY ) ?> sc-lock-<?= esc_attr( $go['lock_style'] ) ?>">
        <div class="sc-lock-icon" aria-hidden="true">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2.5"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <?= sc_render_widget( 'gate', $len, $cset, $code ) ?>
    </div>
    <div class="<?= esc_attr( SC_GATE_CONTENT ) ?>" aria-hidden="true" style="display:none"></div>
</div>
    <?php return ob_get_clean();
} );

add_action( 'wp_head',    'sc_inline_assets' );
add_action( 'login_head', 'sc_inline_assets' );

function sc_inline_assets() { ?>
<style id="sc-styles">
.<?= SC_WRAP_CLASS ?>{margin:16px 0;padding:16px 18px;background:#f8f8fc;border:1px solid #e0e0ec;border-radius:10px;font-family:inherit;position:relative}
.<?= SC_LABEL_CLASS ?>{display:block;font-size:.75rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#666;margin-bottom:10px}
.sc-preview{display:flex;gap:2px;align-items:center;background:#fff;border:1px solid #d4d4e8;border-radius:7px;padding:10px 12px;margin-bottom:12px;user-select:none;position:relative;overflow:hidden;min-height:60px}
.sc-ch{display:inline-flex;align-items:center;justify-content:center;position:relative;z-index:3;transform:rotate(var(--r,0deg)) skewX(var(--sk,0deg)) scaleX(var(--sc,1));line-height:1}
.sc-real{font-family:'Courier New',Courier,monospace;font-size:1.35rem;font-weight:900;color:var(--c,#1a1a3a);text-shadow:1px 2px 0 rgba(0,0,0,.12),-1px 0 0 rgba(255,255,255,.4);position:relative;z-index:2}
.sc-noise{font-family:'Courier New',Courier,monospace;font-size:.5rem;font-weight:400;color:rgba(80,80,120,.3);position:relative;top:-3px;z-index:1;pointer-events:none;line-height:1;letter-spacing:-.02em}
.sc-inputs{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:2px}
.<?= SC_BOX_CLASS ?>{width:40px;height:46px;text-align:center;font-size:1.15rem;font-family:'Courier New',Courier,monospace;font-weight:700;text-transform:uppercase;border:2px solid #c8c8dc;border-radius:7px;background:#fff;outline:none;transition:border-color .15s,box-shadow .15s,background .15s;caret-color:transparent;padding:0;color:#111;-webkit-appearance:none}
.<?= SC_BOX_CLASS ?>:focus{border-color:#5b6cf7;box-shadow:0 0 0 3px rgba(91,108,247,.15)}
.<?= SC_BOX_CLASS ?>.sc-filled{border-color:#22c55e;background:#f0fdf4}
.<?= SC_BOX_CLASS ?>.sc-err-box{border-color:#ef4444!important;background:#fef2f2!important;animation:sc-shake .38s ease}
.sc-hp-wrap{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;opacity:0}
.<?= SC_ERR_CLASS ?>{margin:8px 0 0;font-size:.8rem;color:#ef4444;font-weight:600}
@keyframes sc-shake{0%,100%{transform:translateX(0)}20%{transform:translateX(-5px)}60%{transform:translateX(5px)}}
.<?= SC_GATE_MSG ?>{min-height:1.1em;font-size:.8rem;font-weight:600;color:#ef4444;margin:7px 0 0}
.<?= SC_GATE_TRIES ?>{font-size:.73rem;color:#999;margin-top:5px}
.<?= SC_GATE_BTN ?>{display:inline-flex;align-items:center;gap:6px;margin-top:12px;padding:9px 20px;background:#5b6cf7;color:#fff;border:none;border-radius:7px;font-size:.88rem;font-weight:600;cursor:pointer;transition:background .15s,transform .1s;letter-spacing:.02em}
.<?= SC_GATE_BTN ?>:hover{background:#4a5be6}
.<?= SC_GATE_BTN ?>:active{transform:scale(.97)}
.<?= SC_GATE_BTN ?>:disabled{background:#aaa;cursor:not-allowed}
.<?= SC_GATE_WRAP ?>{position:relative;border-radius:10px;overflow:hidden}
.<?= SC_GATE_OVERLAY ?>{padding:28px 24px;border-radius:10px}
.sc-lock-frosted{background:rgba(255,255,255,.58);backdrop-filter:blur(16px) saturate(1.5);-webkit-backdrop-filter:blur(16px) saturate(1.5);border:1px solid rgba(255,255,255,.72);box-shadow:0 4px 28px rgba(0,0,0,.07)}
.sc-lock-dark{background:rgba(12,12,22,.84);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);color:#eee}
.sc-lock-dark .<?= SC_LABEL_CLASS ?>{color:#bbb}
.sc-lock-dark .<?= SC_BOX_CLASS ?>{background:#1a1a2e;border-color:#3a3a5a;color:#eee}
.sc-lock-dark .sc-preview{background:#0e0e1c;border-color:#2a2a4a}
.sc-lock-dark .sc-real{color:#e8e8ff}
.sc-lock-dark .sc-noise{color:rgba(180,180,220,.25)}
.sc-lock-blur{background:rgba(250,250,255,.92);border:2px dashed #c4c4e0}
.sc-lock-minimal{background:#fafafa;border:1px solid #e0e0e4}
.sc-lock-icon{text-align:center;margin-bottom:12px;opacity:.4}
.<?= SC_GATE_CONTENT ?>{border-radius:10px}
.sc-anim-fade{animation:sc-fade .5s ease both}
@keyframes sc-fade{from{opacity:0}to{opacity:1}}
.sc-anim-slide{animation:sc-slide .44s cubic-bezier(.22,.68,0,1.2) both}
@keyframes sc-slide{from{opacity:0;transform:translateY(-14px)}to{opacity:1;transform:translateY(0)}}
.sc-anim-flicker{animation:sc-flicker .65s steps(1) both}
@keyframes sc-flicker{0%{opacity:0}10%{opacity:1}20%{opacity:0}35%{opacity:1}50%{opacity:0}68%{opacity:1}82%{opacity:0}92%{opacity:1}100%{opacity:1}}
@media(prefers-color-scheme:dark){
.<?= SC_WRAP_CLASS ?>{background:#1c1c2e;border-color:#2e2e48}
.<?= SC_LABEL_CLASS ?>{color:#aaa}
.sc-preview{background:#111122;border-color:#2a2a44}
.<?= SC_BOX_CLASS ?>{background:#1a1a30;border-color:#3a3a58;color:#dde}
.<?= SC_BOX_CLASS ?>.sc-filled{background:#052e16;border-color:#16a34a}
.sc-lock-frosted{background:rgba(18,18,32,.72);border-color:rgba(255,255,255,.1)}
}
</style>
<script id="sc-script">
(function(){
'use strict';
var BOX     = '.<?= SC_BOX_CLASS ?>';
var WRAP    = '.<?= SC_WRAP_CLASS ?>';
var GATE    = '.<?= SC_GATE_WRAP ?>';
var OVERLAY = '.<?= SC_GATE_OVERLAY ?>';
var CONTENT = '.<?= SC_GATE_CONTENT ?>';
var BTN     = '.<?= SC_GATE_BTN ?>';
var MSG     = '.<?= SC_GATE_MSG ?>';
var TRIES   = '.<?= SC_GATE_TRIES ?>';

function initWrap(wrap) {
    var boxes = wrap.querySelectorAll(BOX);
    if (!boxes.length) return;
    boxes.forEach(function(b, i) {
        b.addEventListener('input', function() {
            b.value = b.value.replace(/[^A-Za-z0-9]/g,'').toUpperCase();
            if (b.value.length > 1) b.value = b.value[b.value.length - 1];
            b.classList.toggle('sc-filled', !!b.value);
            b.classList.remove('sc-err-box');
            if (b.value && i + 1 < boxes.length) boxes[i + 1].focus();
        });
        b.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !b.value && i > 0) {
                boxes[i-1].focus(); boxes[i-1].value = ''; boxes[i-1].classList.remove('sc-filled');
            }
            if (e.key === 'ArrowLeft'  && i > 0)              boxes[i-1].focus();
            if (e.key === 'ArrowRight' && i+1 < boxes.length) boxes[i+1].focus();
            if (e.key === 'Enter') { var btn = wrap.querySelector(BTN); if (btn) btn.click(); }
        });
        b.addEventListener('paste', function(e) {
            e.preventDefault();
            var p = (e.clipboardData||window.clipboardData).getData('text').replace(/[^A-Za-z0-9]/g,'').toUpperCase();
            for (var j = 0; j < p.length && i+j < boxes.length; j++) {
                boxes[i+j].value = p[j]; boxes[i+j].classList.add('sc-filled');
            }
            boxes[Math.min(i+p.length, boxes.length-1)].focus();
        });
    });
}

function b64(s) { var b=atob(s),u=new Uint8Array(b.length); for(var i=0;i<b.length;i++) u[i]=b.charCodeAt(i); return u; }
function enc(s) { return new TextEncoder().encode(s); }
function dec(b) { return new TextDecoder().decode(b); }

async function deriveKey(answer, salt) {
    var hash = await crypto.subtle.digest('SHA-256', enc(answer.toUpperCase() + salt));
    return crypto.subtle.importKey('raw', hash, {name:'AES-GCM'}, false, ['decrypt']);
}

async function decryptBlob(blobB64, ivB64, answer, salt) {
    var key = await deriveKey(answer, salt);
    var res = await crypto.subtle.decrypt({name:'AES-GCM', iv:b64(ivB64), tagLength:128}, key, b64(blobB64));
    return dec(new Uint8Array(res));
}

function shakeBoxes(boxes) {
    boxes.forEach(function(b) { b.classList.add('sc-err-box'); b.value = ''; b.classList.remove('sc-filled'); });
    setTimeout(function() {
        boxes.forEach(function(b) { b.classList.remove('sc-err-box'); });
        if (boxes[0]) boxes[0].focus();
    }, 450);
}

function initGate(gate) {
    var blob    = gate.dataset.blob,  iv      = gate.dataset.iv;
    var salt    = gate.dataset.salt,  anim    = gate.dataset.anim   || 'fade';
    var wrong   = gate.dataset.wrong  || 'shake';
    var maxAtt  = parseInt(gate.dataset.maxatt) || 0;
    var relock  = parseInt(gate.dataset.relock) || 0;
    var overlay = gate.querySelector(OVERLAY);
    var content = gate.querySelector(CONTENT);
    var btn     = gate.querySelector(BTN);
    var msgEl   = gate.querySelector(MSG);
    var triesEl = gate.querySelector(TRIES);
    var attempts = 0;
    if (!btn || !overlay || !content) return;

    btn.addEventListener('click', async function() {
        var wrap  = gate.querySelector(WRAP);
        var boxes = wrap ? Array.from(wrap.querySelectorAll(BOX)) : [];
        if (!boxes.length) return;
        var answer = boxes.map(function(b){return b.value;}).join('').toUpperCase();
        if (answer.length < boxes.length) { if (msgEl) msgEl.textContent = 'Fill all characters.'; return; }
        btn.disabled = true;
        if (msgEl) msgEl.textContent = '';
        try {
            var html = await decryptBlob(blob, iv, answer, salt);
            content.innerHTML = html;
            content.style.display = '';
            content.setAttribute('aria-hidden','false');
            content.classList.add('sc-anim-' + anim);
            overlay.style.transition = 'opacity .4s';
            overlay.style.opacity = '0';
            setTimeout(function(){ overlay.style.display = 'none'; }, 420);
            content.querySelectorAll('script').forEach(function(s){
                var n = document.createElement('script'); n.textContent = s.textContent; s.replaceWith(n);
            });
            if (relock > 0) setTimeout(function(){ window.location.reload(); }, relock * 60000);
        } catch(e) {
            attempts++;
            btn.disabled = false;
            if (wrong === 'shake' || wrong === 'counter') shakeBoxes(boxes);
            if (wrong === 'counter' && triesEl) triesEl.textContent = 'Attempt ' + attempts + (maxAtt > 0 ? ' of ' + maxAtt : '');
            if (msgEl) msgEl.textContent = 'Incorrect code. Try again.';
            if (maxAtt > 0 && attempts >= maxAtt) { btn.style.display = 'none'; if (msgEl) msgEl.textContent = 'Too many attempts.'; }
        }
    });
}

function initAll() {
    document.querySelectorAll(WRAP).forEach(initWrap);
    document.querySelectorAll(GATE).forEach(initGate);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
else initAll();

['wpcf7mailsent','wpcf7invalid','wpcf7spam','wpcf7mailfailed'].forEach(function(ev){
    document.addEventListener(ev, initAll);
});
})();
</script>
<?php }

add_action( 'admin_menu', function() {
    add_options_page( 'Split CAPTCHA', 'Split CAPTCHA', 'manage_options', SC_SLUG, 'sc_settings_page' );
} );

add_action( 'admin_init', function() {
    register_setting( SC_SLUG.'-guard', SC_OPT,      'sc_sanitize_opts' );
    register_setting( SC_SLUG.'-gate',  SC_GATE_OPT, 'sc_sanitize_gate_opts' );

    add_settings_section( 'sc_int',  'Enable On',              '__return_false', SC_SLUG.'-guard' );
    add_settings_section( 'sc_cfg',  'CAPTCHA Configuration',  '__return_false', SC_SLUG.'-guard' );
    add_settings_section( 'sc_gapp', 'Appearance',             '__return_false', SC_SLUG.'-gate' );
    add_settings_section( 'sc_gbeh', 'Behaviour',              '__return_false', SC_SLUG.'-gate' );
    add_settings_section( 'sc_gcod', 'Gate CAPTCHA Code',      '__return_false', SC_SLUG.'-gate' );

    foreach ( ['on_comments'=>'Comment Forms','on_cf7'=>'Contact Form 7','on_login'=>'Login Form','on_register'=>'Registration Form'] as $k=>$l ) {
        add_settings_field( $k, $l, function() use ($k) {
            $o = sc_opts();
            printf('<label><input type="checkbox" name="%s[%s]" value="1" %s> Enabled</label>',esc_attr(SC_OPT),esc_attr($k),checked(1,$o[$k],false));
        }, SC_SLUG.'-guard', 'sc_int');
    }

    add_settings_field('length','Code Length',function(){
        $o=sc_opts(); echo '<input type="number" min="4" max="8" name="'.esc_attr(SC_OPT).'[length]" value="'.esc_attr($o['length']).'" style="width:65px"> <span class="description">4–8 chars</span>';
    },SC_SLUG.'-guard','sc_cfg');

    add_settings_field('charset','Character Set',function(){
        $o=sc_opts(); $s=['mixed'=>'Letters + Numbers','alpha'=>'Letters Only','numeric'=>'Numbers Only'];
        echo '<select name="'.esc_attr(SC_OPT).'[charset]">';
        foreach($s as $v=>$l) printf('<option value="%s" %s>%s</option>',esc_attr($v),selected($o['charset'],$v,false),esc_html($l));
        echo '</select>';
    },SC_SLUG.'-guard','sc_cfg');

    add_settings_field('ttl','Token Expiry (s)',function(){
        $o=sc_opts(); echo '<input type="number" min="60" max="3600" step="60" name="'.esc_attr(SC_OPT).'[ttl]" value="'.esc_attr($o['ttl']).'" style="width:85px"> <span class="description">60–3600</span>';
    },SC_SLUG.'-guard','sc_cfg');

    add_settings_field('max_fail','Max Failures (IP lock)',function(){
        $o=sc_opts(); echo '<input type="number" min="0" max="20" name="'.esc_attr(SC_OPT).'[max_fail]" value="'.esc_attr($o['max_fail']).'" style="width:65px"> <span class="description">0 = off</span>';
    },SC_SLUG.'-guard','sc_cfg');

    add_settings_field('min_time','Min Solve Time (s)',function(){
        echo '<input type="number" value="'.esc_attr(SC_MIN_TIME).'" style="width:65px" disabled> <span class="description">Hardcoded anti-bot threshold</span>';
    },SC_SLUG.'-guard','sc_cfg');

    add_settings_field('lock_style','Lock Overlay Style',function(){
        $o=sc_gate_opts(); $s=['frosted'=>'Frosted Glass','dark'=>'Dark Overlay','blur'=>'Light Blur','minimal'=>'Minimal Border'];
        echo '<select name="'.esc_attr(SC_GATE_OPT).'[lock_style]">';
        foreach($s as $v=>$l) printf('<option value="%s" %s>%s</option>',esc_attr($v),selected($o['lock_style'],$v,false),esc_html($l));
        echo '</select>';
    },SC_SLUG.'-gate','sc_gapp');

    add_settings_field('anim','Unlock Animation',function(){
        $o=sc_gate_opts(); $s=['fade'=>'Fade In','slide'=>'Slide Down','flicker'=>'Decrypt Flicker'];
        echo '<select name="'.esc_attr(SC_GATE_OPT).'[anim]">';
        foreach($s as $v=>$l) printf('<option value="%s" %s>%s</option>',esc_attr($v),selected($o['anim'],$v,false),esc_html($l));
        echo '</select>';
    },SC_SLUG.'-gate','sc_gapp');

    add_settings_field('hint_text','Hint Text',function(){
        $o=sc_gate_opts(); echo '<input type="text" name="'.esc_attr(SC_GATE_OPT).'[hint_text]" value="'.esc_attr($o['hint_text']).'" style="width:380px">';
    },SC_SLUG.'-gate','sc_gapp');

    add_settings_field('wrong_style','Wrong Answer Feedback',function(){
        $o=sc_gate_opts(); $s=['shake'=>'Shake + Red','counter'=>'Attempt Counter','silent'=>'Silent'];
        echo '<select name="'.esc_attr(SC_GATE_OPT).'[wrong_style]">';
        foreach($s as $v=>$l) printf('<option value="%s" %s>%s</option>',esc_attr($v),selected($o['wrong_style'],$v,false),esc_html($l));
        echo '</select>';
    },SC_SLUG.'-gate','sc_gbeh');

    add_settings_field('max_attempts','Max Unlock Attempts',function(){
        $o=sc_gate_opts(); echo '<input type="number" min="0" max="10" name="'.esc_attr(SC_GATE_OPT).'[max_attempts]" value="'.esc_attr($o['max_attempts']).'" style="width:65px"> <span class="description">0 = unlimited</span>';
    },SC_SLUG.'-gate','sc_gbeh');

    add_settings_field('relock','Re-lock After (min)',function(){
        $o=sc_gate_opts(); echo '<input type="number" min="0" max="1440" name="'.esc_attr(SC_GATE_OPT).'[relock]" value="'.esc_attr($o['relock']).'" style="width:75px"> <span class="description">0 = never</span>';
    },SC_SLUG.'-gate','sc_gbeh');

    add_settings_field('gate_length','Code Length',function(){
        $o=sc_gate_opts(); echo '<input type="number" min="4" max="8" name="'.esc_attr(SC_GATE_OPT).'[length]" value="'.esc_attr($o['length']).'" style="width:65px"> <span class="description">4–8 chars</span>';
    },SC_SLUG.'-gate','sc_gcod');

    add_settings_field('gate_charset','Character Set',function(){
        $o=sc_gate_opts(); $s=['mixed'=>'Letters + Numbers','alpha'=>'Letters Only','numeric'=>'Numbers Only'];
        echo '<select name="'.esc_attr(SC_GATE_OPT).'[charset]">';
        foreach($s as $v=>$l) printf('<option value="%s" %s>%s</option>',esc_attr($v),selected($o['charset'],$v,false),esc_html($l));
        echo '</select>';
    },SC_SLUG.'-gate','sc_gcod');
} );

function sc_sanitize_opts( $in ) {
    return [
        'on_comments' => !empty($in['on_comments']) ? 1 : 0,
        'on_cf7'      => !empty($in['on_cf7'])      ? 1 : 0,
        'on_login'    => !empty($in['on_login'])    ? 1 : 0,
        'on_register' => !empty($in['on_register']) ? 1 : 0,
        'length'      => min(8,    max(4,  (int)($in['length']   ?? 5))),
        'charset'     => in_array($in['charset']??'', ['alpha','numeric','mixed']) ? $in['charset'] : 'mixed',
        'ttl'         => min(3600, max(60, (int)($in['ttl']      ?? 600))),
        'max_fail'    => min(20,   max(0,  (int)($in['max_fail'] ?? 5))),
    ];
}

function sc_sanitize_gate_opts( $in ) {
    return [
        'lock_style'   => in_array($in['lock_style']??'',  ['frosted','dark','blur','minimal'])  ? $in['lock_style']  : 'frosted',
        'anim'         => in_array($in['anim']??'',        ['fade','slide','flicker'])            ? $in['anim']        : 'flicker',
        'relock'       => min(1440, max(0,  (int)($in['relock']       ?? 0))),
        'wrong_style'  => in_array($in['wrong_style']??'', ['shake','counter','silent'])          ? $in['wrong_style'] : 'shake',
        'max_attempts' => min(10,   max(0,  (int)($in['max_attempts'] ?? 5))),
        'hint_text'    => sanitize_text_field($in['hint_text'] ?? 'Enter the code to unlock this content'),
        'length'       => min(8, max(4, (int)($in['length']  ?? 5))),
        'charset'      => in_array($in['charset']??'', ['alpha','numeric','mixed']) ? $in['charset'] : 'mixed',
    ];
}

function sc_settings_page() {
    $active = (isset($_GET['sc_tab']) && $_GET['sc_tab']==='gate') ? 'gate' : 'guard';
    $url    = admin_url('options-general.php?page='.SC_SLUG);
    ?>
    <div class="wrap">
        <h1>Split CAPTCHA</h1>
        <nav class="nav-tab-wrapper" style="margin-bottom:20px">
            <a href="<?= esc_url($url.'&sc_tab=guard') ?>" class="nav-tab<?= $active==='guard'?' nav-tab-active':'' ?>">&#128737; Form Guard</a>
            <a href="<?= esc_url($url.'&sc_tab=gate') ?>"  class="nav-tab<?= $active==='gate' ?' nav-tab-active':'' ?>">&#128272; Content Gate</a>
        </nav>
        <?php if ($active==='guard'): ?>
            <form method="post" action="options.php">
                <?php settings_fields(SC_SLUG.'-guard'); do_settings_sections(SC_SLUG.'-guard'); submit_button('Save Form Guard Settings'); ?>
            </form>
            <hr>
            <h2>Widget Preview</h2>
            <p style="color:#666;font-size:.9rem">Live preview — noise + honeypot active on every load.</p>
            <?= sc_render_widget('preview') ?>
            <?php sc_inline_assets(); ?>
        <?php else: ?>
            <form method="post" action="options.php">
                <?php settings_fields(SC_SLUG.'-gate'); do_settings_sections(SC_SLUG.'-gate'); submit_button('Save Content Gate Settings'); ?>
            </form>
            <hr>
            <h2>Gate Preview</h2>
            <p style="color:#666;font-size:.9rem">Live demo of <code>[split_captcha]...[/split_captcha]</code></p>
            <?php
            echo do_shortcode('[split_captcha]<div style="padding:20px;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0"><strong style="color:#15803d">Content unlocked.</strong> AES-256-GCM decrypted entirely in-browser. No server call on unlock.</div>[/split_captcha]');
            sc_inline_assets();
            ?>
        <?php endif; ?>
        <hr>
        <p style="color:#999;font-size:.78rem">Shortcode: <code>[split_captcha]protected content[/split_captcha]</code> — nested shortcodes supported.</p>
    </div>
    <?php
}

add_action( 'add_meta_boxes', function() {
    add_meta_box('sc_meta_box','Split CAPTCHA','sc_meta_box_html',['post','page'],'side','default');
} );

function sc_meta_box_html( $post ) {
    wp_nonce_field('sc_meta_save_'.$post->ID,'sc_meta_nonce');
    $cur = get_post_meta($post->ID, SC_META, true);
    ?>
    <p style="margin:0 0 7px;font-weight:600;font-size:.84rem">&#128737; Form Guard on comments:</p>
    <?php foreach([''=> 'Inherit global','1'=>'Force ON','0'=>'Force OFF'] as $v=>$l): ?>
        <label style="display:block;margin-bottom:4px;font-size:.84rem">
            <input type="radio" name="<?= esc_attr(SC_META) ?>" value="<?= esc_attr($v) ?>" <?= checked($v,$cur,false) ?>> <?= esc_html($l) ?>
        </label>
    <?php endforeach; ?>
    <p style="margin:10px 0 0;font-size:.78rem;color:#999">Content Gate: always active when shortcode is present.</p>
    <?php
}

add_action('save_post', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['sc_meta_nonce'])) return;
    if (!wp_verify_nonce($_POST['sc_meta_nonce'],'sc_meta_save_'.$post_id)) return;
    if (!current_user_can('edit_post',$post_id)) return;
    $val = isset($_POST[SC_META]) ? sanitize_key($_POST[SC_META]) : '';
    if ($val === '') delete_post_meta($post_id, SC_META);
    else update_post_meta($post_id, SC_META, $val==='1'?'1':'0');
});

register_activation_hook(__FILE__, function() {
    if (false===get_option(SC_OPT))      add_option(SC_OPT,      sc_opts());
    if (false===get_option(SC_GATE_OPT)) add_option(SC_GATE_OPT, sc_gate_opts());
    sc_gate_salt();
});
