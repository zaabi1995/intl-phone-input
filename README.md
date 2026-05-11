# intl-phone-input (Claude Code skill)

The canonical phone-input recipe for BHD-Group HTML projects. Drop-in CSS + JS that wraps [intl-tel-input v25](https://github.com/jackocnr/intl-tel-input) and solves its three RTL gotchas so the field looks identical in Arabic, English, and Finnish pages.

See [SKILL.md](./SKILL.md) for the full recipe.

## Why this exists

Out-of-the-box intl-tel-input renders the country chip on the RIGHT side of the input in RTL pages and silently breaks the chip-width-clearance padding. Three rounds of debugging on `riyada.cardify.om` (the Riyada × Vantaa cross-border webinar registration page for 8 June 2026) shipped a CSS + JS combination that:

- Forces the chip to the **LEFT** in both directions, matching Apple Mail, WhatsApp, Telegram, Google forms (digits + dial codes are LTR per Unicode BiDi spec)
- Defeats the three RTL inline-style overrides the library injects when `document.dir === 'rtl'`
- Aligns the chip flush with the input bounds (no 9px vertical overflow)
- Auto-detects the user's country via ipapi.co with Oman fallback
- Uses libphonenumber-validated example numbers as placeholders

## Files

```
intl-phone-input/
├── SKILL.md                          # full recipe + verification checklist
├── snippets/
│   ├── example.html                  # minimal working demo (open in browser)
│   └── normalize.php                 # server-side E.164 normalisation
└── README.md
```

## Drop-in

Copy the head + body + CSS + JS blocks from [SKILL.md](./SKILL.md). Replace the brand colour (`#1d44a3` navy) and font with your project's. The recipe is otherwise framework-agnostic — plain HTML, plain CSS, vanilla JS.

## Used by

- [riyada.cardify.om](https://riyada.cardify.om/) — Riyada × Vantaa webinars, 8 June 2026, AR/EN/FI
