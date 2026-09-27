<?php

return [
    'bsVersion' => '4.x', // this will set globally `bsVersion` to Bootstrap 4.x for all Krajee Extensions
    'icon-framework' => \kartik\icons\Icon::FAS,  // Font Awesome Icon framework
    'adminEmail' => 'ekaterina.oskina@gmail.com',
    'senderEmail' => 'ekaterina.oskina@gmail.com',
    'senderName' => 'Katia Oskina',
    // Public-site content constants (Phase 3). The "Shop" nav link is hidden
    // while shopUrl is empty - put the real Etsy shop link here to show it.
    'shopUrl' => '',
    'contactEmail' => 'ekaterina.oskina@gmail.com',
    'contactLocation' => 'Malmö, Sweden',
    'socialBehance' => 'https://www.behance.net/katiaoskina',
    'socialLinkedin' => 'https://www.linkedin.com/in/katiaoskina',
    'socialInstagram' => 'https://www.instagram.com/katia.oskina',

    // --- Public SEO / social sharing ----------------------------------------
    // siteDescription is the fallback meta description and the og:description
    // for any page that has no text of its own. Keep it under ~160 characters
    // (Google truncates around there) and keep "Malmö, Sweden" in it: it is
    // one of the few places the site states where the artist works, which is
    // what local search has to match on.
    'siteName' => 'Katia Oskina',
    'siteDescription' => 'Katia Oskina is an illustrator and artist based in Malmö, Sweden — artworks, commercial illustration, picturebooks and sketchbooks.',
    // Fallback social preview, used for pages with no artwork of their own.
    'ogDefaultImage' => '/img/og-default.jpg',

    // --- Location, for schema.org and local search --------------------------
    'artistCity' => 'Malmö',
    'artistRegion' => 'Skåne County',
    'artistCountry' => 'SE',
    'artistLatitude' => '55.6050',
    'artistLongitude' => '13.0038',

    // --- Search engines ------------------------------------------------------
    // Google Search Console offers several ways to prove you own the domain.
    // The simplest that needs no DNS access: choose "HTML tag" verification
    // and paste the content="..." value here. Safe to leave empty.
    'googleSiteVerification' => '8HxEEYpPcuYXGOt_D9ZjdXFarAkN6z7K174avkMv1Cw',

    // --- Analytics ----------------------------------------------------------
    // Umami is cookieless and stores no personal data, so it needs no consent
    // banner. Leave the ID empty to disable analytics entirely (that is the
    // local/dev default — the script is only emitted when this is filled in).
    // Get the ID from cloud.umami.is → Settings → Websites → Edit → Website ID.
    'umamiWebsiteId' => 'ceeb84f5-bcde-4347-8e44-938b4a53419c',
    'umamiScriptUrl' => 'https://cloud.umami.is/script.js',

    // Bump on every public-frontend asset/markup change — shown in the footer
    // so we can tell a stale cached mobile page from a fresh one (see
    // docs/00-START-HERE.md "practical lessons" / 03-data-model "constraints").
    'buildVersion' => 4,
];
