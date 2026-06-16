<?php

return [
    'bsVersion' => '4.x', // this will set globally `bsVersion` to Bootstrap 4.x for all Krajee Extensions
    'icon-framework' => \kartik\icons\Icon::FAS,  // Font Awesome Icon framework
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    // Public-site content constants (Phase 3). TODO: replace placeholder shop URL
    // with the real Etsy shop link once the owner provides it.
    'shopUrl' => 'https://www.etsy.com/',
    'contactEmail' => 'ekaterina.oskina@gmail.com',
    'contactLocation' => 'Malmö, Sweden',
    'socialBehance' => 'https://www.behance.net/katiaoskina',
    'socialLinkedin' => 'https://www.linkedin.com/in/katiaoskina',
    'socialInstagram' => 'https://www.instagram.com/katia.oskina',
    // Bump on every public-frontend asset/markup change — shown in the footer
    // so we can tell a stale cached mobile page from a fresh one (see
    // docs/00-START-HERE.md "practical lessons" / 03-data-model "constraints").
    'buildVersion' => 1,
];
