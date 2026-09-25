# Material Symbols Outlined

Self-hosted Google Material Symbols Outlined (WOFF2), licensed under Apache 2.0;
see `LICENSE`. The font includes the full icon set with optical size 24, weight
400, grade 0, and the variable FILL axis (0–1).

Source CSS: https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=block

The shared rules in `resources/css/app.css` enable ligatures and reserve a square
icon slot. Keep those rules local: loading them only from Google Fonts causes
icon names to appear as text when the external stylesheet is unavailable.
