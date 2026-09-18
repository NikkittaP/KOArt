<?php

namespace app\commands;

use app\helpers\PortfolioPdf;
use app\models\Paintings;
use app\models\Sections;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Builds section PDF portfolios from the command line. Doubles as the smoke
 * test for app\helpers\PortfolioPdf (the repo has no automated test suite):
 * it fails when a PDF's page count is not 1 cover + one page per photo.
 *
 *   php yii portfolio/build                 every section
 *   php yii portfolio/build picturebooks    one section
 */
class PortfolioController extends Controller
{
    public function actionBuild($slug = null)
    {
        $query = Sections::find()->orderBy(['sort' => SORT_ASC]);
        if ($slug !== null) {
            $query->andWhere(['slug' => $slug]);
        }

        $failed = false;
        foreach ($query->all() as $section) {
            $works = Paintings::findForSectionMosaic($section->id)->all();
            if (!PortfolioPdf::hasContent($works)) {
                $this->stdout("{$section->slug}: no works with photos, skipped\n");
                continue;
            }
            $expected = 1 + PortfolioPdf::photoCount($works);
            $t = microtime(true);
            $path = PortfolioPdf::get($section, $works);
            $secs = microtime(true) - $t;
            $pages = PortfolioPdf::countPages($path);
            $ok = $pages === $expected;
            $failed = $failed || !$ok;
            $this->stdout(sprintf(
                "%s: %d pages, %.1f MB, %.1f s, peak %.0f MB — %s\n  %s\n",
                $section->slug,
                $pages,
                filesize($path) / 1048576,
                $secs,
                memory_get_peak_usage(true) / 1048576,
                $ok ? 'OK' : "FAIL (expected {$expected} pages)",
                $path
            ));
        }
        return $failed ? ExitCode::SOFTWARE : ExitCode::OK;
    }
}
