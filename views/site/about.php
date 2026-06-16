<?php

/**
 * About page (Phase 3 port of design_mockups_v2/about.html): two-column
 * layout, portrait + bio. Contact info lives only in the footer (no
 * duplicate links in the body) — see docs/02-design-spec.md.
 *
 * @var \yii\web\View $this
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'About';
?>
<header class="shead"><h1>About</h1></header>
<div class="about">
    <div class="about-photo">
        <img src="<?= Url::to('@web/about_photo/001_2016-08.jpg') ?>" alt="Katia Oskina">
    </div>
    <div class="about-txt">
        <p>I am an artist and illustrator specialising in children&#x27;s book and board game illustration. I also design packaging and book covers, creating eye-catching visuals that tell a story.</p>
        <p>My goal is to bring imagination to life with vibrant, engaging artwork. I have a formal background in fine art and illustration, and I keep developing my visual language through both commissioned work and personal projects.</p>
        <p>Alongside client projects I draw for myself, experiment with new approaches, and explore long-term ideas. My experience also includes teaching painting and leading workshops, which shapes the way I think about structure and visual storytelling.</p>
        <p>Based in Malmö, Sweden. Available for freelance.</p>
    </div>
</div>
