<?php

/**
 * Privacy policy.
 *
 * Deliberately short, because the site genuinely does very little: no forms,
 * no accounts, no advertising, no tracking cookies, and fonts served from our
 * own server rather than Google's. Everything stated here is a description of
 * what the code actually does - if that changes (a contact form, a different
 * analytics tool), this page has to change with it.
 *
 * @var \yii\web\View $this
 */

use yii\helpers\Html;

$this->title = 'Privacy';
$this->params['seo'] = [
    'description' => 'How katiaoskina.com handles visitor data: no tracking cookies, '
        . 'no advertising, cookieless analytics and self-hosted fonts.',
];

$contactEmail = Yii::$app->params['contactEmail'];
$contactLocation = Yii::$app->params['contactLocation'];
$analyticsEnabled = !empty(Yii::$app->params['umamiWebsiteId']);

// Kept in the page rather than in the changelog so the date visitors see is
// the date the text last actually changed.
$lastUpdated = '16 September 2026';
?>
<header class="shead">
    <h1>Privacy</h1>
    <p>What this website does and does not collect about you.</p>
</header>

<div class="legal">
    <h2>Who runs this site</h2>
    <p>
        This is the personal portfolio of Katia Oskina, an illustrator and artist working in
        <?= Html::encode($contactLocation) ?>. For any question about this page, or about the
        information below, write to
        <a href="mailto:<?= Html::encode($contactEmail) ?>"><?= Html::encode($contactEmail) ?></a>.
    </p>

    <h2>What is collected</h2>
    <p>
        Nothing you type. The site has no contact form, no account, no newsletter and no
        comments — the only way to reach the artist is by email, which goes directly to the
        address above and is not stored here.
    </p>
    <p>
        As with any website, the hosting provider records standard web-server logs when a page
        is requested: the IP address, the time, the page, and the browser's user-agent string.
        These logs exist so the server can be operated and secured, and they are not used to
        build any profile of visitors.
    </p>

    <h2>Cookies</h2>
    <p>
        The site sets one cookie, named <code>_csrf</code>. It carries a random token that
        protects form submissions against cross-site request forgery, it contains nothing about
        you, and it disappears when you close the browser. There are no advertising cookies, no
        tracking cookies and no third-party cookies — which is also why you are not being asked
        to click through a consent banner.
    </p>

<?php if ($analyticsEnabled): ?>
    <h2>Analytics</h2>
    <p>
        Visits are counted with <a href="https://umami.is/" target="_blank" rel="noopener">Umami</a>,
        an analytics tool chosen specifically because it sets no cookies and stores no data that
        identifies an individual. It records aggregate figures — how many people opened a page,
        which site or search engine they arrived from, and a country — and nothing that can be
        traced back to a person or followed across other websites.
    </p>
<?php endif; ?>

    <h2>Third parties</h2>
    <p>
        Typefaces are served from this site's own server, so displaying a page sends nothing to
        Google or any other font provider. No analytics, advertising or social-media scripts are
        embedded in the pages.
    </p>
    <p>
        Links to Instagram, Behance, LinkedIn and the shop lead to services run by other
        companies. Once you follow one, that company's own privacy policy applies; this site has
        no part in it and receives nothing back.
    </p>

    <h2>Your rights</h2>
    <p>
        Under the GDPR you may ask what personal data is held about you, ask for it to be
        corrected or erased, and complain to a supervisory authority — in Sweden, the Swedish
        Authority for Privacy Protection (IMY). In practice this site holds no personal data
        beyond the server logs described above, but any such request can be sent to
        <a href="mailto:<?= Html::encode($contactEmail) ?>"><?= Html::encode($contactEmail) ?></a>.
    </p>

    <h2>Copyright</h2>
    <p>
        All artworks and images on this site are the property of the artist and may not be
        reproduced or used without permission.
    </p>

    <p class="legal-updated">Last updated: <?= Html::encode($lastUpdated) ?></p>
</div>
