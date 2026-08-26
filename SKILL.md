---
name: intl-phone-input
description: Drop-in international phone input recipe for any HTML form across BHD-Group projects. Uses intl-tel-input v25 (jackocnr, MIT) with the RTL chip-alignment, country-detection, and E.164-normalization gotchas pre-solved (chip stays LEFT in both LTR and RTL, matching Apple/WhatsApp/Telegram/Google forms). Triggers on "phone selector", "phone input", "country code picker", "intl-tel-input", "international phone field", "WhatsApp number field", any HTML form that needs a phone number input.
---

# International Phone Input (intl-tel-input v25 + RTL Fixes)

The canonical recipe for adding a phone-number input to any BHD-Group HTML form. Solves the RTL/LTR layout problem once so future projects don't have to debug it again.

## When to use
- Any new **HTML form** that captures a phone number
- WhatsApp / SMS opt-in fields
- KYC / signup / contact forms
- Replaces ad-hoc `<input type="tel" placeholder="+968 9xxx xxxx">` patterns

**Not for app UIs.** For a React Native / Expo app or a custom vanilla-JS SPA
that renders its own components (no `<form>` + library), use the sibling skill
**country-phone-selector**, it ships the proven Splitty `PhoneField` /
`CountryFlagSheet` / `openCountrySheet` drop-ins and shares one curated country
list (Israel removed) across mobile + web.

## The library
- Package: `intl-tel-input` v25.10.x ([GitHub](https://github.com/jackocnr/intl-tel-input))
- License: MIT
- 8k+ stars, actively maintained, includes libphonenumber for validation
- CDN: `https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.1/`
- Use the **WithUtils** JS build so libphonenumber ships bundled (validation + E.164 formatting + example placeholders)

## What this skill solves

Out-of-the-box intl-tel-input has three RTL gotchas that break Arabic pages:

1. **`.iti__country-container` gets inline `right: 0px`** in RTL → with `left: 0` from CSS the container spans the FULL input width
2. **`<input>` gets inline `padding-right: <chipWidth>px`** in RTL → wrong side when we want the chip on the LEFT
3. **Library skips `padding-left` auto-calculation in RTL pages** → placeholder hidden behind a left-positioned chip

Also fixes the vertical alignment: the chip button defaults to 44px tall positioned with an implicit 9px top offset, overflowing the input bounds.

Result: phone field renders **identically in LTR and RTL pages** with chip on the left, number on the right, the convention Apple, WhatsApp, Telegram, Google forms all use because numbers + dial codes are inherently LTR per Unicode BiDi spec.

## The recipe

### 1. Head includes

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.1/build/css/intlTelInput.css">
<!-- Your own CSS goes after this -->
```

### 2. Form markup

```html
<input type="tel" id="phone" name="phone" autocomplete="tel">
```

Plain `<input type="tel">`. No placeholder needed (the library auto-fills with libphonenumber example numbers per selected country).

### 3. Bottom of page script

```html
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.1/build/js/intlTelInputWithUtils.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.intlTelInput !== 'function') return;
    var phoneInput = document.getElementById('phone');
    if (!phoneInput) return;

    // Drop any custom placeholder so libphonenumber's auto example takes over
    phoneInput.removeAttribute('placeholder');

    var iti = window.intlTelInput(phoneInput, {
      initialCountry: 'auto',
      geoIpLookup: function (success, failure) {
        fetch('https://ipapi.co/json/', { cache: 'force-cache' })
          .then(function (r) { return r.json(); })
          .then(function (d) { success((d && d.country_code) ? d.country_code.toLowerCase() : 'om'); })
          .catch(function () { success('om'); }); // fallback to Oman for BHD-Group context
      },
      separateDialCode: true,
      countryOrder: ['om', 'fi', 'ae', 'sa', 'gb', 'us'],  // tweak per audience
      autoPlaceholder: 'polite',
      formatOnDisplay: true,
      nationalMode: true,
      // Optional: Arabic country labels when page lang is AR
      i18n: document.documentElement.lang === 'ar' ? {
        om: 'عُمان', fi: 'فنلندا', ae: 'الإمارات العربية المتحدة',
        sa: 'المملكة العربية السعودية', gb: 'المملكة المتحدة', us: 'الولايات المتحدة',
        kw: 'الكويت', qa: 'قطر', bh: 'البحرين', eg: 'مصر', jo: 'الأردن',
        searchPlaceholder: 'بحث',
      } : {},
    });

    // RTL pages: library doesn't set padding-left for the LEFT-positioned
    // chip. Measure the chip width and apply it manually.
    function applyChipPadding() {
      if (document.documentElement.dir !== 'rtl') return;
      var chip = phoneInput.closest('.iti').querySelector('.iti__selected-country');
      if (!chip) return;
      var w = Math.ceil(chip.getBoundingClientRect().width) + 6;
      phoneInput.style.setProperty('padding-left', w + 'px', 'important');
    }
    setTimeout(applyChipPadding, 50);
    setTimeout(applyChipPadding, 300);  // catch the post-geo-ip country swap
    phoneInput.addEventListener('countrychange', applyChipPadding);

    // On submit, replace the input value with full international E.164
    // ('+96894022553' / '+358406155262') so the server gets prefixed digits.
    var form = phoneInput.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        if (phoneInput.value.trim()) {
          var intl = iti.getNumber();
          if (intl) phoneInput.value = intl;
        }
      }, true);
    }
  });
</script>
```

### 4. CSS overrides (THE KEY PART)

Copy this block verbatim. Tweak the brand colors/fonts to match your project.

```css
/* === intl-tel-input v25 integration ===
   Force LTR layout in BOTH directions so AR/EN/FI render identically:
   chip on left, number on right. Same convention as Apple Mail, WhatsApp,
   Telegram, Google forms - digits + dial codes are inherently LTR per
   Unicode BiDi spec. */

form .iti { direction: ltr; width: 100%; display: block; }
form .iti * { direction: ltr; }

/* Container: stretch to fill input vertically; pin to LEFT side regardless
   of page direction (library injects right:0 inline in RTL - !important
   wins). */
form .iti__country-container {
  top: 0; bottom: 0; height: auto;
  left: 0 !important; right: auto !important;
  display: flex; align-items: stretch;
}

/* Chip button: fill the container exactly so flag, dial code, arrow sit
   inside the input bounds with no overflow. */
form .iti__selected-country {
  background-color: #f4f6fb;
  height: auto;
  align-self: stretch;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0 10px;
  margin: 0;
  border: 0;
  border-right: 1px solid #dde3ee;
  border-radius: 10px 0 0 10px;
  cursor: pointer;
  line-height: 1;
  vertical-align: top;
  box-sizing: border-box;
  transition: background-color .15s;
}
form .iti__selected-country:hover { background-color: #eaeef7; }
form .iti__selected-country-primary { display: flex; align-items: center; gap: 6px; }
form .iti__selected-dial-code {
  font-weight: 700;
  color: #1d44a3;          /* your navy brand color */
  font-size: .92rem;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
  direction: ltr;
}
form .iti__flag { box-shadow: 0 0 0 1px rgba(0,0,0,.08); border-radius: 2px; }
form .iti__arrow { margin-inline-start: 4px; border-top-color: #7d8aa3; }

/* Input field: number left-aligned LTR regardless of page direction. */
form .iti input[type="tel"] {
  text-align: left !important;
  direction: ltr !important;
  unicode-bidi: plaintext;
}

/* RTL pages: defeat the library's inline padding-right (it tries to clear
   the chip on the right side, but we've moved it to the left). */
[dir="rtl"] form .iti input[type="tel"] { padding-right: 14px !important; }

/* Country dropdown: stays in LTR layout for readable country lists. */
form .iti__dropdown-content,
form .iti__country-list {
  font-family: inherit;
  font-size: .92rem;
  border-radius: 10px;
  box-shadow: 0 12px 32px rgba(15,34,102,.18);
  max-height: 280px;
  border: 1px solid #dde3ee;
  background: #fff;
  direction: ltr;
  text-align: left;
  min-width: 280px;
}
form .iti__country { padding: 8px 12px; font-family: inherit; direction: ltr; text-align: left; }
form .iti__country.iti__highlight,
form .iti__country:hover { background: #eef2f9; }
form .iti--inline-dropdown .iti__dropdown-content { margin-top: 4px; }
```

### 5. Server-side phone normalization

The user might type their number in any format. Server should accept all of these:
- `94022553` (bare 8-digit Oman) → `96894022553`
- `+968 9402 2553` → `96894022553`
- `00968-94022553` → `96894022553`
- `+358 40 615 5262` (Finnish) → `358406155262`
- `+1 (415) 555-1234` (any international) → `14155551234`

Reference PHP implementation:

```php
function normalizePhone(string $raw): ?string {
  $digits = preg_replace('/\D+/', '', $raw);
  if ($digits === '' || $digits === null) return null;
  if (str_starts_with($digits, '00')) $digits = substr($digits, 2);

  // Bare 8-digit Oman local number -> add 968 prefix
  if (strlen($digits) === 8 && preg_match('/^[79]\d{7}$/', $digits)) {
    return '968' . $digits;
  }
  // E.164 (8-15 digits per ITU-T E.164)
  if (strlen($digits) >= 8 && strlen($digits) <= 15) {
    return $digits;
  }
  return null;
}
```

For Dardasha WhatsApp API the digits-only form (no `+`) is the correct payload.

## Verification checklist

After dropping the recipe in, test these in browser:

```js
// 1. Both directions render chip on LEFT
//    Expected: paddingLeft ~= chipWidth+6, paddingRight: 14px
const i = document.querySelector('input[type=tel]');
const s = window.getComputedStyle(i);
console.log({padL: s.paddingLeft, padR: s.paddingRight, dir: s.direction, ta: s.textAlign});

// 2. Chip is flush with input bounds (no vertical overflow)
//    Expected: btnTop_offset = 1px (input's top border), btnH = inputH - 2
const b = document.querySelector('.iti__selected-country');
const iR = i.getBoundingClientRect();
const bR = b.getBoundingClientRect();
console.log({inputH: iR.height, btnH: bR.height, btnTopOff: bR.top - iR.top});

// 3. Placeholder visible right of the chip
//    Expected: i.placeholder == '9212 3456' (Oman example) or country example
console.log(i.placeholder);
```

Visual sanity check at `https://placeholder.dev/your-form`:
- EN: `[🇴🇲 ▼ +968 ▏ 9212 3456 ............]`
- AR: `[🇴🇲 ▼ +968 ▏ 9212 3456 ............]` (label above stays RTL)

## When the input lives inside a modal (display: none on init)

This bites every time. intl-tel-input measures chip width ONCE on init and sets `input.style.paddingLeft = chipWidth + 6`. If the input is inside a `display:none` modal at that moment (Alpine `x-cloak`, headless UI dialog, Bootstrap modal, anything), chip width is 0 → padding lands at 6px → when the modal opens, **`+968` and the placeholder `9212 3456` overlap** (the digits sit on top of each other).

Fix: re-measure whenever the modal becomes visible. Snippet for an Alpine `x-show` modal (works for any modal that toggles `display` via inline style):

```js
function applyChipPadding() {
    var iti = input.closest('.iti');
    if (!iti) return;
    var chip = iti.querySelector('.iti__selected-country');
    if (!chip) return;
    var w = Math.ceil(chip.getBoundingClientRect().width) + 8;
    if (w < 20) return;  // chip still not laid out, skip
    input.style.setProperty('padding-left', w + 'px', 'important');
}
setTimeout(applyChipPadding, 50);
setTimeout(applyChipPadding, 300);
input.addEventListener('countrychange', applyChipPadding);

// Watch for the modal opening (style attribute changing from display:none)
var modal = input.closest('[x-show]') || input.closest('.modal') || input.closest('.fixed');
if (modal && typeof MutationObserver === 'function') {
    new MutationObserver(function () {
        if (modal.style.display !== 'none') {
            applyChipPadding();
            setTimeout(applyChipPadding, 50);
            setTimeout(applyChipPadding, 200);
        }
    }).observe(modal, { attributes: true, attributeFilter: ['style'] });
}
window.addEventListener('resize', applyChipPadding);  // catches narrow viewport chip wrap
```

Why three timer passes inside the observer: the first runs synchronously when display flips; chip width is still ~0. The 50ms pass catches first layout. The 200ms pass catches a delayed reflow (e.g. font loading).

## Battle history

Originally debugged across 4 deployment rounds on riyada.cardify.om:
1. Wrong padding override → placeholder hidden behind chip
2. Chip vertical offset of 9px → overflowed input bounds
3. RTL inline `right: 0` → container spanned full width
4. RTL skip-padding-left bug → placeholder hidden in AR mode

Then 5th round on eid.bhd.om (26 May 2026): same chip-in-hidden-modal trap, fix codified above.

Settled on the recipe above. Reference commits: `b1b1d46` in github.com/zaabi1995/riyada-event, `593b8db` in github.com/zaabi1995/eid-greetings.

## Used by

- `riyada.cardify.om` (8 June 2026 webinars registration form, 3 languages AR/EN/FI)
- `eid.bhd.om` lead-capture modal (Eid greeting card download form, 26 May 2026)
