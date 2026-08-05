<?php
/**
 * Petrol Station Compliance Analysis
 * City of Kigali - Digital Inspection Platform
 *
 * A live analytical view over inspection data. Every figure on this page
 * is computed from the database at load time - nothing is hardcoded.
 *
 * Place in the application root, alongside index.php.
 */

require_once __DIR__ . '/config/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode('insights.php'));
    exit;
}

$pdo = getDB();

/* ---------- Headline figures ---------- */
$overall = $pdo->query("
    SELECT COUNT(DISTINCT i.id) AS inspections,
           COUNT(DISTINCT e.id) AS stations,
           MIN(i.inspection_date) AS first_date,
           MAX(i.inspection_date) AS last_date
    FROM inspections i
    JOIN entities e ON e.id = i.entity_id
    WHERE i.deleted_at IS NULL AND e.entity_type_id = 2
")->fetch();

/* ---------- Per-station scores ---------- */
$stations = $pdo->query("
    SELECT e.name, e.district, e.sector,
           ROUND(SUM(CASE WHEN ia.status='yes' THEN ci.max_score ELSE 0 END), 1) AS score
    FROM inspections i
    JOIN entities e ON e.id = i.entity_id
    JOIN inspection_answers ia ON ia.inspection_id = i.id
    JOIN checklist_items ci ON ci.id = ia.item_id
    WHERE i.deleted_at IS NULL AND e.entity_type_id = 2
    GROUP BY i.id
    ORDER BY score ASC
")->fetchAll();

$scores  = array_column($stations, 'score');
$avg     = $scores ? round(array_sum($scores) / count($scores), 1) : 0;
$lowest  = $scores ? min($scores) : 0;
$highest = $scores ? max($scores) : 0;
$below70 = count(array_filter($scores, fn($s) => $s < 70));

/* ---------- District comparison ---------- */
$districts = $pdo->query("
    SELECT e.district,
           COUNT(DISTINCT i.id) AS inspections,
           ROUND(100 * SUM(CASE WHEN ia.status='yes' THEN ci.max_score ELSE 0 END)
                 / NULLIF(SUM(CASE WHEN ia.status IN ('yes','no') THEN ci.max_score ELSE 0 END), 0), 1) AS compliance
    FROM inspections i
    JOIN entities e ON e.id = i.entity_id
    JOIN inspection_answers ia ON ia.inspection_id = i.id
    JOIN checklist_items ci ON ci.id = ia.item_id
    WHERE i.deleted_at IS NULL AND e.entity_type_id = 2
    GROUP BY e.district
    ORDER BY compliance DESC
")->fetchAll();

/* ---------- Section compliance ---------- */
$sections = $pdo->query("
    SELECT cs.section_number, cs.title,
           ROUND(100 * SUM(ia.status='yes') / NULLIF(SUM(ia.status IN ('yes','no')), 0)) AS compliance
    FROM inspection_answers ia
    JOIN checklist_items ci ON ci.id = ia.item_id
    JOIN checklist_sections cs ON cs.id = ci.section_id
    JOIN inspections i ON i.id = ia.inspection_id
    WHERE i.deleted_at IS NULL AND cs.entity_type_id = 2
    GROUP BY cs.id
    ORDER BY compliance ASC
")->fetchAll();

/* ---------- Most-failed requirements ---------- */
$failures = $pdo->query("
    SELECT ci.label,
           SUM(ia.status='no') AS failures,
           COUNT(*) AS assessed,
           ROUND(100 * SUM(ia.status='no') / COUNT(*)) AS fail_pct
    FROM inspection_answers ia
    JOIN checklist_items ci ON ci.id = ia.item_id
    JOIN inspections i ON i.id = ia.inspection_id
    WHERE i.deleted_at IS NULL AND ia.status IN ('yes','no')
    GROUP BY ci.id
    HAVING failures > 0
    ORDER BY failures DESC, fail_pct DESC
    LIMIT 10
")->fetchAll();

$top = $failures[0] ?? null;

/* ---------- Siting vs operations ---------- */
$sitingSections     = ['1', '2', '3', '4', '5', '13'];
$operationsSections = ['6', '7', '8', '9', '10', '11', '12', '14', '15'];

$avgOf = function ($nums) use ($sections) {
    $vals = [];
    foreach ($sections as $s) {
        if (in_array($s['section_number'], $nums, true) && $s['compliance'] !== null) {
            $vals[] = (float) $s['compliance'];
        }
    }
    return $vals ? round(array_sum($vals) / count($vals)) : 0;
};

$sitingAvg = $avgOf($sitingSections);
$opsAvg    = $avgOf($operationsSections);

function band($v)
{
    if ($v >= 85) return 'good';
    if ($v >= 70) return 'fair';
    if ($v >= 55) return 'weak';
    return 'poor';
}

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Petrol Station Compliance Analysis &mdash; City of Kigali</title>
<style>
  :root{
    --blue:#0033A0; --green:#009A44; --yellow:#FAD201; --red:#C0362C;
    --ink:#141922; --muted:#5B6270; --line:#E2E5EA; --bg:#F4F5F2; --card:#fff;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
       background:var(--bg);color:var(--ink);line-height:1.55;padding:24px 16px 60px}
  .wrap{max-width:1080px;margin:0 auto}

  header{border-bottom:3px solid var(--blue);padding-bottom:18px;margin-bottom:28px}
  .eyebrow{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:var(--muted);font-weight:700}
  h1{font-size:clamp(22px,3.4vw,32px);color:var(--blue);margin:4px 0 6px;line-height:1.2}
  .sub{color:var(--muted);font-size:14px}
  .backlink{display:inline-block;margin-bottom:14px;color:var(--blue);
            text-decoration:none;font-size:13px;font-weight:600}
  .backlink:hover{text-decoration:underline}

  .stats{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:28px}
  .stat{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:16px 18px}
  .stat .n{font-size:30px;font-weight:800;color:var(--blue);letter-spacing:-.02em}
  .stat .l{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;font-weight:600;margin-top:2px}

  .finding{background:#FFF4F3;border-left:5px solid var(--red);border-radius:8px;
           padding:20px 22px;margin-bottom:30px}
  .finding h2{font-size:13px;letter-spacing:.12em;text-transform:uppercase;color:var(--red);margin-bottom:8px}
  .finding p{font-size:17px;font-weight:600;margin-bottom:8px}
  .finding .note{font-size:14px;color:var(--muted);font-weight:400}

  section{background:var(--card);border:1px solid var(--line);border-radius:10px;
          padding:22px 24px;margin-bottom:22px}
  h3{font-size:16px;color:var(--blue);margin-bottom:4px}
  .desc{font-size:13px;color:var(--muted);margin-bottom:18px}

  .row{display:grid;grid-template-columns:minmax(120px,220px) 1fr 52px;
       gap:12px;align-items:center;padding:7px 0}
  .row + .row{border-top:1px solid #F0F2F4}
  .name{font-size:13.5px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .name small{display:block;font-weight:400;color:var(--muted);font-size:11.5px}
  .bar{background:#EEF0F3;border-radius:5px;height:22px;overflow:hidden}
  .fill{height:100%;border-radius:5px;transition:width .5s ease}
  .fill.good{background:var(--green)}
  .fill.fair{background:#7FB800}
  .fill.weak{background:var(--yellow)}
  .fill.poor{background:var(--red)}
  .val{text-align:right;font-weight:700;font-size:13.5px;font-variant-numeric:tabular-nums}

  .split{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));margin-bottom:22px}
  .pill{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:20px 22px;text-align:center}
  .pill .big{font-size:44px;font-weight:800;letter-spacing:-.03em;line-height:1}
  .pill .cap{font-size:13px;color:var(--muted);margin-top:6px;font-weight:600}
  .pill .exp{font-size:12.5px;color:var(--muted);margin-top:8px;line-height:1.45}
  .pill.a .big{color:var(--green)}
  .pill.b .big{color:var(--red)}

  table{width:100%;border-collapse:collapse;font-size:13.5px}
  th{text-align:left;padding:9px 10px;background:#F7F8F9;color:var(--muted);
     font-size:11.5px;text-transform:uppercase;letter-spacing:.06em;border-bottom:2px solid var(--line)}
  td{padding:9px 10px;border-bottom:1px solid #F0F2F4;vertical-align:top}
  td.num{text-align:right;font-weight:700;font-variant-numeric:tabular-nums;white-space:nowrap}
  .tag{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700}
  .tag.poor{background:#FDE8E6;color:var(--red)}
  .tag.weak{background:#FFF6D6;color:#8A6D00}

  .interp{background:#F0F4FF;border-left:5px solid var(--blue);border-radius:8px;
          padding:20px 22px;margin-bottom:22px}
  .interp h3{margin-bottom:8px}
  .interp p{font-size:14px;margin-bottom:10px}
  .interp p:last-child{margin-bottom:0}

  footer{text-align:center;color:var(--muted);font-size:12px;margin-top:34px;
         padding-top:18px;border-top:1px solid var(--line)}

  @media (max-width:560px){
    .row{grid-template-columns:1fr 44px;grid-template-areas:"n v" "b b";gap:6px}
    .name{grid-area:n}.val{grid-area:v}.bar{grid-area:b}
  }
  @media print{body{background:#fff;padding:0}section,.stat{break-inside:avoid}.backlink{display:none}}
</style>
</head>
<body>
<div class="wrap">

  <a class="backlink" href="index.php">&larr; Back to platform</a>

  <header>
    <div class="eyebrow">City of Kigali &middot; Directorate of Inspection</div>
    <h1>Petrol Station Compliance Analysis</h1>
    <div class="sub">
      <?= (int) $overall['inspections'] ?> inspections across
      <?= count($districts) ?> districts &middot;
      <?= e($overall['first_date']) ?> to <?= e($overall['last_date']) ?>
      &middot; generated <?= date('j F Y, H:i') ?>
    </div>
  </header>

  <div class="stats">
    <div class="stat"><div class="n"><?= (int) $overall['stations'] ?></div><div class="l">Stations assessed</div></div>
    <div class="stat"><div class="n"><?= $avg ?>%</div><div class="l">Mean compliance</div></div>
    <div class="stat"><div class="n"><?= $below70 ?></div><div class="l">Below 70%</div></div>
    <div class="stat"><div class="n"><?= $lowest ?>%</div><div class="l">Lowest score</div></div>
    <div class="stat"><div class="n"><?= $highest ?>%</div><div class="l">Highest score</div></div>
  </div>

  <?php if ($top): ?>
  <div class="finding">
    <h2>Principal finding</h2>
    <p><?= e(rtrim($top['label'], '. ')) ?> &mdash; absent at
       <?= (int) $top['failures'] ?> of <?= (int) $top['assessed'] ?> stations
       (<?= (int) $top['fail_pct'] ?>%).</p>
    <div class="note">This is the single most frequently failed requirement across all stations
      inspected, and it applies to facilities storing bulk flammable fuel in populated areas.</div>
  </div>
  <?php endif; ?>

  <div class="split">
    <div class="pill a">
      <div class="big"><?= $sitingAvg ?>%</div>
      <div class="cap">Siting &amp; separation</div>
      <div class="exp">Plot size, road safety, distance from residences, power lines,
        sensitive areas, tank capacity</div>
    </div>
    <div class="pill b">
      <div class="big"><?= $opsAvg ?>%</div>
      <div class="cap">Operations &amp; documentation</div>
      <div class="exp">Permits, licences, insurance, firefighting systems, electrical
        certification, sanitation, zoning</div>
    </div>
  </div>

  <div class="interp">
    <h3>What this indicates</h3>
    <p>Compliance with <strong>siting and separation</strong> requirements averages
      <?= $sitingAvg ?>%, while <strong>operational and documentary</strong> compliance
      averages <?= $opsAvg ?>% &mdash; a gap of <?= abs($sitingAvg - $opsAvg) ?> percentage points.</p>
    <p>Siting requirements are verified once, at the point of planning approval, and cannot
      easily lapse. Operational requirements &mdash; valid insurance, current retail licences,
      tested equipment, displayed evacuation plans &mdash; must be maintained continuously,
      and are only detected as lapsed when someone inspects.</p>
    <p>The pattern therefore suggests that approval controls are working and that the gap
      lies in periodic verification. This is the function the platform is intended to support.</p>
  </div>

  <section>
    <h3>Compliance by district</h3>
    <div class="desc">Weighted across all assessed criteria</div>
    <?php foreach ($districts as $d): ?>
      <div class="row">
        <div class="name"><?= e($d['district']) ?>
          <small><?= (int) $d['inspections'] ?> inspections</small></div>
        <div class="bar"><div class="fill <?= band($d['compliance']) ?>"
             style="width:<?= (float) $d['compliance'] ?>%"></div></div>
        <div class="val"><?= (float) $d['compliance'] ?>%</div>
      </div>
    <?php endforeach; ?>
  </section>

  <section>
    <h3>Compliance by requirement area</h3>
    <div class="desc">All <?= count($sections) ?> assessed sections, weakest first</div>
    <?php foreach ($sections as $s): if ($s['compliance'] === null) continue; ?>
      <div class="row">
        <div class="name"><?= e($s['title']) ?>
          <small>Section <?= e($s['section_number']) ?></small></div>
        <div class="bar"><div class="fill <?= band($s['compliance']) ?>"
             style="width:<?= (float) $s['compliance'] ?>%"></div></div>
        <div class="val"><?= (int) $s['compliance'] ?>%</div>
      </div>
    <?php endforeach; ?>
  </section>

  <section>
    <h3>Station ranking</h3>
    <div class="desc">Lowest scoring first &mdash; suggested order of follow-up</div>
    <?php foreach ($stations as $st): ?>
      <div class="row">
        <div class="name"><?= e($st['name']) ?>
          <small><?= e($st['district']) ?><?= $st['sector'] ? ' &middot; ' . e($st['sector']) : '' ?></small></div>
        <div class="bar"><div class="fill <?= band($st['score']) ?>"
             style="width:<?= (float) $st['score'] ?>%"></div></div>
        <div class="val"><?= (float) $st['score'] ?>%</div>
      </div>
    <?php endforeach; ?>
  </section>

  <section>
    <h3>Most frequently failed requirements</h3>
    <div class="desc">Ranked by number of stations in non-compliance</div>
    <table>
      <thead>
        <tr><th>Requirement</th><th style="text-align:right">Failed</th>
            <th style="text-align:right">Assessed</th><th style="text-align:right">Rate</th></tr>
      </thead>
      <tbody>
        <?php foreach ($failures as $f): ?>
        <tr>
          <td><?= e(rtrim($f['label'], '. ')) ?></td>
          <td class="num"><?= (int) $f['failures'] ?></td>
          <td class="num"><?= (int) $f['assessed'] ?></td>
          <td class="num">
            <span class="tag <?= $f['fail_pct'] >= 50 ? 'poor' : 'weak' ?>">
              <?= (int) $f['fail_pct'] ?>%
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <footer>
    City of Kigali &mdash; Digital Inspection Platform<br>
    Figures computed from live inspection records at time of viewing.
  </footer>

</div>
</body>
</html>