# Xin Shui

Static marketing site. No build step. Plain HTML, CSS and JS.

## Structure

    index.html          single page
    css/styles.css      all styling, brand tokens at the top
    js/main.js          nav, FAQ accordion
    assets/img/         logo and photography
    netlify.toml        publish directory and security headers

## Brand tokens

Defined as CSS variables in `css/styles.css`. Do not substitute colours or
typefaces. Deep teal #1F6FA8, mid teal #5DADE2, pale #EAF5FB, ink #1C2630.
Element colours: wood #3A6A54, fire #A25444, earth #987C52, metal #6D7C88,
water #1A3452. Typefaces: Cormorant Garamond, Inter, Noto Serif SC.

## Image slots

Five placeholders are marked `IMAGE SLOT` in `css/styles.css`. Drop files into
`assets/img/` and swap the gradient for `url("../assets/img/name.jpg")`.

    SLOT 0  .hero              water at first light, wide
    SLOT 1  .split-media       still landscape, portrait crop
    SLOT 2  .duo-media-1       notebook and morning light
    SLOT 3  .duo-media-2       quiet interior, soft shadow
    SLOT 4  .cta               calm water, wide

## Copy rules

No "BaZi", "destiny", "predict", "forecast", "heal" or "cure" anywhere in
public copy. Approved vocabulary is "Five Elements" and "Four Pillars".
No em dashes or en dashes. No location references. No emoji.

## Local preview

    python3 -m http.server 8000

## Deploy

Push to GitHub, connect the repo in Netlify, publish directory `.`,
build command empty.
