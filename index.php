<?php
$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}
require_once __DIR__ . '/functions.php';

$view = handle_seo_form();

$hasPost         = $view['hasPost'];
$url             = $view['url'];
$error           = $view['error'];
$parsed          = $view['parsed'];
$keywords        = $view['keywords'];
$diagnostic      = $view['diagnostic'];
$recommendations = $view['recommendations'];
$semanticRecs    = $view['semantic_recs'] ?? [];
$fetchInfo       = $view['fetchInfo'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KWCible — Analyse sémantique SEO</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="navbar mb-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">
            <span data-i18n="nav.titre">KWCible</span>
            <span class="d-block d-sm-inline ms-sm-2" data-i18n="nav.soustitre">Analyse sémantique SEO</span>
        </span>
        <?php if (!defined('PLATFORM_EMBEDDED')): ?>
        <select id="lang-select" class="form-select form-select-sm" style="width:auto; background-color:rgba(255,255,255,0.15); color:#fff; border-color:rgba(255,255,255,0.3); font-size:0.8rem;">
            <option value="fr">FR</option>
            <option value="en">EN</option>
        </select>
        <?php endif; ?>
    </div>
</nav>

<div class="container-lg pb-5">

    <!-- ─── Config Card ─────────────────────────────────────────────────── -->
    <div class="card mb-4" id="configCard">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-8">
                        <label for="urlInput" class="form-label fw-semibold" data-i18n="form.label_url">URL à analyser</label>
                        <div class="input-group">
                            <input
                                type="text"
                                class="form-control"
                                id="urlInput"
                                name="url"
                                placeholder="https://example.com/page-a-analyser"
                                data-i18n-placeholder="form.placeholder_url"
                                value="<?= htmlspecialchars($url) ?>"
                                required
                            >
                            <button type="submit" class="btn btn-primary px-4" data-i18n="form.btn_analyser">
                                Analyser
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="config-help-panel">
                            <div class="help-title" data-i18n="help.titre">Comment ça marche ?</div>
                            <ul>
                                <li data-i18n="help.etape1">Entrez l'URL d'une page web publique</li>
                                <li data-i18n="help.etape2">L'outil identifie la <strong>requête clé principale</strong></li>
                                <li data-i18n="help.etape3">Diagnostic SEO complet avec recommandations</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($hasPost && $error): ?>
        <!-- ─── Erreur ──────────────────────────────────────────────────── -->
        <div class="alert alert-warning border-warning-subtle bg-warning-subtle text-dark">
            <strong data-i18n="status.erreur_prefix">Erreur :</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($hasPost && !$error): ?>

        <div class="row g-4 mb-4">
        <!-- ─── Structure de la page ────────────────────────────────────── -->
        <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header py-3">
                <h2 class="h6 mb-0 fw-bold" data-i18n="table.structure_titre">Structure de la page</h2>
                <small class="text-muted"><?= htmlspecialchars($fetchInfo['finalUrl'] ?? $url) ?></small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th style="width:180px" data-i18n="table.th_element">Élément</th>
                                <th data-i18n="table.th_contenu">Contenu</th>
                                <th style="width:100px" class="text-center" data-i18n="table.th_statut">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Title -->
                            <tr>
                                <td class="fw-semibold" data-i18n="table.el_title">Title</td>
                                <td class="truncate-text"><?php if ($parsed['title']): ?><?= htmlspecialchars($parsed['title']) ?><?php else: ?><span data-i18n="table.aucun_m">(aucun)</span><?php endif; ?></td>
                                <td class="text-center">
                                    <?php
                                    $tLen = mb_strlen($parsed['title']);
                                    if ($tLen === 0) echo '<span class="badge-error" data-i18n="badge.absent_m">Absent</span>';
                                    elseif ($tLen <= 60) echo '<span class="badge-ok" data-i18n="badge.car" data-i18n-params=\'' . htmlspecialchars(json_encode(['n' => $tLen])) . '\'>' . $tLen . ' car.</span>';
                                    else echo '<span class="badge-warn" data-i18n="badge.car" data-i18n-params=\'' . htmlspecialchars(json_encode(['n' => $tLen])) . '\'>' . $tLen . ' car.</span>';
                                    ?>
                                </td>
                            </tr>
                            <!-- Meta Description -->
                            <tr>
                                <td class="fw-semibold" data-i18n="table.el_meta_description">Meta Description</td>
                                <td class="truncate-text"><?php if ($parsed['meta_description']): ?><?= htmlspecialchars($parsed['meta_description']) ?><?php else: ?><span data-i18n="table.aucune_f">(aucune)</span><?php endif; ?></td>
                                <td class="text-center">
                                    <?php
                                    $mLen = mb_strlen($parsed['meta_description']);
                                    if ($mLen === 0) echo '<span class="badge-error" data-i18n="badge.absente_f">Absente</span>';
                                    elseif ($mLen <= 160) echo '<span class="badge-ok" data-i18n="badge.car" data-i18n-params=\'' . htmlspecialchars(json_encode(['n' => $mLen])) . '\'>' . $mLen . ' car.</span>';
                                    else echo '<span class="badge-warn" data-i18n="badge.car" data-i18n-params=\'' . htmlspecialchars(json_encode(['n' => $mLen])) . '\'>' . $mLen . ' car.</span>';
                                    ?>
                                </td>
                            </tr>
                            <!-- H1 -->
                            <tr>
                                <td class="fw-semibold" data-i18n="table.el_h1">H1</td>
                                <td>
                                    <?php if (empty($parsed['h1'])): ?>
                                        <em class="text-muted" data-i18n="table.aucun_m">(aucun)</em>
                                    <?php else: ?>
                                        <?php foreach ($parsed['h1'] as $h): ?>
                                            <div><?= htmlspecialchars($h) ?></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $h1c = count($parsed['h1']);
                                    if ($h1c === 1) echo '<span class="badge-ok" data-i18n="badge.h1_count" data-i18n-params=\'' . htmlspecialchars(json_encode(['n' => 1])) . '\'>1 H1</span>';
                                    elseif ($h1c === 0) echo '<span class="badge-error" data-i18n="badge.h1_count" data-i18n-params=\'' . htmlspecialchars(json_encode(['n' => 0])) . '\'>0 H1</span>';
                                    else echo '<span class="badge-warn" data-i18n="badge.h1_count" data-i18n-params=\'' . htmlspecialchars(json_encode(['n' => $h1c])) . '\'>' . $h1c . ' H1</span>';
                                    ?>
                                </td>
                            </tr>
                            <!-- H2 -->
                            <tr>
                                <td class="fw-semibold" data-i18n="table.el_h2">H2</td>
                                <td>
                                    <?php if (empty($parsed['h2'])): ?>
                                        <em class="text-muted" data-i18n="table.aucun_m">(aucun)</em>
                                    <?php else: ?>
                                        <?php foreach (array_slice($parsed['h2'], 0, 8) as $h): ?>
                                            <span class="kw-badge"><?= htmlspecialchars($h) ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($parsed['h2']) > 8): ?>
                                            <span class="text-muted ms-1" data-i18n="table.autres" data-i18n-params='<?= htmlspecialchars(json_encode(['n' => count($parsed['h2']) - 8])) ?>'>+<?= count($parsed['h2']) - 8 ?> autres</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge-ok"><?= count($parsed['h2']) ?></span>
                                </td>
                            </tr>
                            <!-- H3 -->
                            <?php if (!empty($parsed['h3'])): ?>
                            <tr>
                                <td class="fw-semibold" data-i18n="table.el_h3">H3</td>
                                <td>
                                    <?php foreach (array_slice($parsed['h3'], 0, 6) as $h): ?>
                                        <span class="kw-badge"><?= htmlspecialchars($h) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($parsed['h3']) > 6): ?>
                                        <span class="text-muted ms-1" data-i18n="table.autres" data-i18n-params='<?= htmlspecialchars(json_encode(['n' => count($parsed['h3']) - 6])) ?>'>+<?= count($parsed['h3']) - 6 ?> autres</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge-ok"><?= count($parsed['h3']) ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <!-- Canonical -->
                            <tr>
                                <td class="fw-semibold" data-i18n="table.el_canonical">Canonical</td>
                                <td class="truncate-text"><?php if ($parsed['canonical']): ?><?= htmlspecialchars($parsed['canonical']) ?><?php else: ?><span data-i18n="table.aucun_m">(aucun)</span><?php endif; ?></td>
                                <td class="text-center">
                                    <?= $parsed['canonical'] ? '<span class="badge-ok" data-i18n="badge.ok">OK</span>' : '<span class="badge-warn" data-i18n="badge.absent_m">Absent</span>' ?>
                                </td>
                            </tr>
                            <!-- Word Count -->
                            <tr>
                                <td class="fw-semibold" data-i18n="table.el_mots">Nombre de mots</td>
                                <td><?= number_format($parsed['word_count'], 0, ',', ' ') ?> <span data-i18n="unit.mots">mots</span></td>
                                <td class="text-center">
                                    <?php
                                    if ($parsed['word_count'] >= 300) echo '<span class="badge-ok" data-i18n="badge.ok">OK</span>';
                                    elseif ($parsed['word_count'] >= 100) echo '<span class="badge-warn" data-i18n="badge.court">Court</span>';
                                    else echo '<span class="badge-error" data-i18n="badge.tres_court">Très court</span>';
                                    ?>
                                </td>
                            </tr>
                            <!-- URL Parts -->
                            <?php if (!empty($parsed['url_parts'])): ?>
                            <tr>
                                <td class="fw-semibold" data-i18n="table.el_segments_url">Segments URL</td>
                                <td>
                                    <?php foreach ($parsed['url_parts'] as $seg): ?>
                                        <span class="kw-badge"><?= htmlspecialchars($seg) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge-ok"><?= count($parsed['url_parts']) ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>

        <!-- ─── Requête clé principale ──────────────────────────────────── -->
        <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header py-3">
                <h2 class="h6 mb-0 fw-bold" data-i18n="kw.titre">Requête clé principale</h2>
            </div>
            <div class="card-body">
                <div class="kw-primary-card mb-3">
                    <div class="kw-label" data-i18n="kw.label">Mot-clé principal identifié</div>
                    <div class="kw-value">
                        <?= htmlspecialchars($keywords['primary_keyword']) ?>
                        <?php if (!empty($keywords['suggest_debug']) && $keywords['suggest_debug']['resultat'] === 'corrige'): ?>
                            <span style="display:inline-block;font-size:0.65rem;background:var(--brand-gold-light);color:#b8860b;padding:2px 8px;border-radius:4px;margin-left:8px;vertical-align:middle;font-weight:600;letter-spacing:0.03em;" data-i18n="kw.suggest_corrige">SUGGEST CORRIGÉ</span>
                            <?php if (!empty($keywords['suggest_debug']['keyword_avant'])): ?>
                                <span style="display:inline-block;font-size:0.65rem;color:#94a3b8;margin-left:4px;vertical-align:middle;" data-i18n="kw.etait" data-i18n-params='<?= htmlspecialchars(json_encode(['keyword' => $keywords['suggest_debug']['keyword_avant']])) ?>'>était : <?= htmlspecialchars($keywords['suggest_debug']['keyword_avant']) ?></span>
                            <?php endif; ?>
                        <?php elseif (!empty($keywords['suggest_debug']) && $keywords['suggest_debug']['resultat'] === 'valide'): ?>
                            <span style="display:inline-block;font-size:0.65rem;background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:4px;margin-left:8px;vertical-align:middle;font-weight:600;letter-spacing:0.03em;" data-i18n="kw.suggest_valide">SUGGEST VALIDÉ</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php
                        // Mapper le type d'intention vers la clé i18n
                        $intentI18nMap = [
                            'transactionnelle' => 'intent.transactionnelle',
                            'commerciale' => 'intent.commerciale',
                            'navigationnelle' => 'intent.navigationnelle',
                            'informationnelle' => 'intent.informationnelle',
                        ];
                        $intentI18nKey = $intentI18nMap[$keywords['intent']['type']] ?? '';
                        // Mapper le niveau de concurrence vers la clé i18n
                        $compI18nMap = [
                            'faible' => 'competition.faible',
                            'moyen' => 'competition.moyen',
                            'élevé' => 'competition.eleve',
                        ];
                        $compI18nKey = $compI18nMap[$keywords['competition']['level']] ?? '';
                        ?>
                        <span class="kw-intent" data-i18n="<?= htmlspecialchars($intentI18nKey) ?>">
                            <?= htmlspecialchars($keywords['intent']['label']) ?>
                        </span>
                        <span class="kw-competition" style="background: <?= htmlspecialchars($keywords['competition']['color']) ?>20; color: <?= htmlspecialchars($keywords['competition']['color']) ?>" data-i18n="kw.concurrence" data-i18n-params='<?= htmlspecialchars(json_encode(['level' => $keywords['competition']['label']])) ?>'>
                            Concurrence : <?= htmlspecialchars($keywords['competition']['label']) ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($keywords['h1_debug'])): ?>
                    <details style="margin-top:10px;">
                        <summary style="font-size:12px;color:#64748b;cursor:pointer;" data-i18n="kw.debug_h1">Debug H1 enrichissement</summary>
                        <pre style="background:#f8f9fa;padding:10px;border-radius:6px;font-size:12px;margin-top:5px;max-height:300px;overflow:auto;"><?= htmlspecialchars(json_encode($keywords['h1_debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    </details>
                <?php endif; ?>

                <?php if (!empty($keywords['suggest_debug'])): ?>
                    <details style="margin-top:10px;">
                        <summary style="font-size:12px;color:#64748b;cursor:pointer;" data-i18n="kw.debug_suggest">Debug Google Suggest (ordre des mots)</summary>
                        <pre style="background:#f8f9fa;padding:10px;border-radius:6px;font-size:12px;margin-top:5px;max-height:300px;overflow:auto;"><?= htmlspecialchars(json_encode($keywords['suggest_debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    </details>
                <?php endif; ?>

                <?php if (!empty($keywords['variants'])): ?>
                    <div class="mt-3">
                        <div class="fw-semibold mb-2" style="font-size: 0.85rem; color: #64748b;" data-i18n="kw.variantes_titre">Variantes secondaires</div>
                        <div>
                            <?php foreach ($keywords['variants'] as $v): ?>
                                <span class="kw-badge">
                                    <?= htmlspecialchars($v['term']) ?>
                                    <span class="kw-badge-score"><?= round($v['score']) ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
        </div>

        <!-- ─── Optimisation sémantique ───────────────────────────────────── -->
        <?php if (!empty($keywords['term_details'])): ?>
        <div class="card mb-4">
            <div class="card-header py-3">
                <h2 class="h6 mb-0 fw-bold" data-i18n="sem.titre">Optimisation sémantique</h2>
            </div>
            <div class="card-body">
                <!-- Jauges ICS / ISR côte à côte -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <?php
                        $soseo = $keywords['soseo'];
                        if ($soseo >= 60) $soseoColor = 'var(--seo-good)';
                        elseif ($soseo >= 30) $soseoColor = 'var(--seo-warn)';
                        else $soseoColor = 'var(--seo-bad)';
                        $circumSoseo = 2 * M_PI * 34;
                        $offsetSoseo = $circumSoseo - ($circumSoseo * $soseo / 100);
                        ?>
                        <div class="seo-score-gauge">
                            <div class="seo-score-circle">
                                <svg viewBox="0 0 80 80">
                                    <circle class="score-bg" cx="40" cy="40" r="34"/>
                                    <circle class="score-fill" cx="40" cy="40" r="34"
                                            stroke="<?= $soseoColor ?>"
                                            stroke-dasharray="<?= round($circumSoseo, 1) ?>"
                                            stroke-dashoffset="<?= round($offsetSoseo, 1) ?>"/>
                                </svg>
                                <div class="seo-score-number" style="color: <?= $soseoColor ?>"><?= round($soseo) ?>%</div>
                            </div>
                            <div class="seo-score-label">
                                <strong><span class="metric-hint" data-i18n-title="sem.ics_title" title="Indice de Couverture Sémantique — Mesure la présence des termes importants dans les zones stratégiques (Title, H1, H2, Meta, URL, Body). Plus le score est élevé, meilleure est la distribution.">ICS</span> — <span data-i18n="sem.ics_label">Couverture sémantique</span></strong>
                                <span data-i18n="sem.ics_desc">Présence des termes importants dans les zones stratégiques.</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php
                        $dseo = $keywords['dseo'];
                        if ($dseo < 20) $dseoColor = 'var(--seo-good)';
                        elseif ($dseo <= 50) $dseoColor = 'var(--seo-warn)';
                        else $dseoColor = 'var(--seo-bad)';
                        $circumDseo = 2 * M_PI * 34;
                        $offsetDseo = $circumDseo - ($circumDseo * $dseo / 100);
                        ?>
                        <div class="seo-score-gauge">
                            <div class="seo-score-circle">
                                <svg viewBox="0 0 80 80">
                                    <circle class="score-bg" cx="40" cy="40" r="34"/>
                                    <circle class="score-fill" cx="40" cy="40" r="34"
                                            stroke="<?= $dseoColor ?>"
                                            stroke-dasharray="<?= round($circumDseo, 1) ?>"
                                            stroke-dashoffset="<?= round($offsetDseo, 1) ?>"/>
                                </svg>
                                <div class="seo-score-number" style="color: <?= $dseoColor ?>"><?= round($dseo) ?>%</div>
                            </div>
                            <div class="seo-score-label">
                                <strong><span class="metric-hint" data-i18n-title="sem.isr_title" title="Indice de Sur-Répétition — Détecte le keyword stuffing en mesurant la densité excessive des termes dans le contenu. Un score bas indique un contenu naturel.">ISR</span> — <span data-i18n="sem.isr_label">Sur-répétition</span></strong>
                                <?php
                                if ($dseo < 20) echo '<span data-i18n="sem.isr_no_risk">Aucun risque de sur-optimisation détecté.</span>';
                                elseif ($dseo <= 50) echo '<span data-i18n="sem.isr_warning">Attention, certains termes sont trop répétés.</span>';
                                else echo '<span data-i18n="sem.isr_danger">Risque élevé de keyword stuffing.</span>';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Treemap des termes importants -->
                <div class="treemap-container" id="treemap"></div>
                <div class="treemap-tooltip" id="treemapTooltip"></div>

                <!-- Tableau des termes importants -->
                <div class="table-responsive">
                    <table class="table table-sm term-details-table mb-0">
                        <thead>
                            <tr>
                                <th data-i18n="term.th_terme">Terme</th>
                                <th class="text-center" style="width:100px" data-i18n="term.th_score">Score</th>
                                <th style="width:180px" data-i18n="term.th_zones">Zones</th>
                                <th class="text-center" style="width:80px" data-i18n="term.th_densite">Densité</th>
                                <th class="text-center" style="width:100px" data-i18n="term.th_statut">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxTermScore = $keywords['term_details'][0]['score'] ?? 1;
                            foreach ($keywords['term_details'] as $td):
                                $barWidth = $maxTermScore > 0 ? round($td['score'] / $maxTermScore * 100) : 0;
                                if ($td['status'] === 'optimal') {
                                    $statusClass = 'badge-ok';
                                    $statusLabel = 'Optimal';
                                    $statusI18n = 'term.optimal';
                                } elseif ($td['status'] === 'sur-optimisé') {
                                    $statusClass = 'badge-error';
                                    $statusLabel = 'Sur-optimisé';
                                    $statusI18n = 'term.sur_optimise';
                                } else {
                                    $statusClass = 'badge-warn';
                                    $statusLabel = 'Sous-optimisé';
                                    $statusI18n = 'term.sous_optimise';
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold" style="font-size:0.85rem"><?= htmlspecialchars($td['term']) ?></div>
                                    <div class="term-bar-track">
                                        <div class="term-bar-fill <?= $td['status'] === 'sur-optimisé' ? 'over' : ($td['status'] === 'sous-optimisé' ? 'under' : '') ?>" style="width:<?= $barWidth ?>%"></div>
                                    </div>
                                </td>
                                <td class="text-center"><?= $td['score'] ?></td>
                                <td>
                                    <?php foreach ($td['zones'] as $z): ?>
                                        <span class="zone-tag zone-<?= htmlspecialchars($z) ?>"><?= htmlspecialchars($z) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="text-center"><?= $td['density'] ?>%</td>
                                <td class="text-center"><span class="<?= $statusClass ?>" data-i18n="<?= $statusI18n ?>"><?= $statusLabel ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ─── Recommandations sémantiques ──────────────────────────────── -->
        <?php if (!empty($semanticRecs)): ?>
        <div class="card mb-4">
            <div class="card-header py-3">
                <h2 class="h6 mb-0 fw-bold" data-i18n="semrec.titre">Recommandations sémantiques</h2>
            </div>
            <div class="card-body">
                <?php
                // Mapper les titres de recommandation vers les clés i18n
                $semrecTitleMap = [
                    'Termes à renforcer' => 'semrec.renforcer',
                    'Termes à réduire' => 'semrec.reduire',
                    'Couverture sémantique faible' => 'semrec.couverture_faible',
                    'Zones stratégiques à enrichir' => 'semrec.zones_enrichir',
                    'Bonne optimisation' => 'semrec.bonne_optim',
                ];
                $semrecMsgMap = [
                    'Termes à renforcer' => 'semrec.renforcer_msg',
                    'Termes à réduire' => 'semrec.reduire_msg',
                    'Couverture sémantique faible' => 'semrec.couverture_faible_msg',
                    'Bonne optimisation' => 'semrec.bonne_optim_msg',
                ];
                ?>
                <?php foreach ($semanticRecs as $rec): ?>
                    <div class="sem-rec-block sem-rec-<?= htmlspecialchars($rec['type']) ?>">
                        <div class="sem-rec-header">
                            <span class="sem-rec-icon">
                                <?php
                                if ($rec['type'] === 'under') echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1z" transform="rotate(180 8 8)"/></svg>';
                                elseif ($rec['type'] === 'over') echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1z"/></svg>';
                                elseif ($rec['type'] === 'success') echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg>';
                                else echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="3"/></svg>';
                                ?>
                            </span>
                            <strong <?php if (isset($semrecTitleMap[$rec['title']])): ?>data-i18n="<?= $semrecTitleMap[$rec['title']] ?>"<?php endif; ?>><?= htmlspecialchars($rec['title']) ?></strong>
                        </div>
                        <div class="sem-rec-message" <?php if (isset($semrecMsgMap[$rec['title']])): ?>data-i18n="<?= $semrecMsgMap[$rec['title']] ?>"<?php endif; ?>><?= $rec['message'] ?></div>
                        <?php if (!empty($rec['items'])): ?>
                            <ul class="sem-rec-list">
                                <?php foreach ($rec['items'] as $item): ?>
                                    <li><?= $item ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ─── Diagnostic SEO ──────────────────────────────────────────── -->
        <div class="card mb-4">
            <div class="card-header py-3">
                <h2 class="h6 mb-0 fw-bold" data-i18n="diag.titre">Diagnostic SEO</h2>
            </div>
            <div class="card-body">
                <!-- Score gauge -->
                <?php
                $pct = $diagnostic['percentage'];
                if ($pct >= 70) $scoreColor = 'var(--seo-good)';
                elseif ($pct >= 40) $scoreColor = 'var(--seo-warn)';
                else $scoreColor = 'var(--seo-bad)';
                $circumference = 2 * M_PI * 34;
                $offset = $circumference - ($circumference * $pct / 100);
                ?>
                <div class="seo-score-gauge">
                    <div class="seo-score-circle">
                        <svg viewBox="0 0 80 80">
                            <circle class="score-bg" cx="40" cy="40" r="34"/>
                            <circle class="score-fill" cx="40" cy="40" r="34"
                                    stroke="<?= $scoreColor ?>"
                                    stroke-dasharray="<?= round($circumference, 1) ?>"
                                    stroke-dashoffset="<?= round($offset, 1) ?>"/>
                        </svg>
                        <div class="seo-score-number" style="color: <?= $scoreColor ?>"><?= $pct ?>%</div>
                    </div>
                    <div class="seo-score-label">
                        <strong data-i18n="diag.score_global">Score SEO global</strong>
                        <span data-i18n="diag.points" data-i18n-params='<?= htmlspecialchars(json_encode(['score' => $diagnostic['score'], 'max' => $diagnostic['max_score']])) ?>'><?= $diagnostic['score'] ?> / <?= $diagnostic['max_score'] ?> points —</span>
                        <?php
                        if ($pct >= 70) echo '<span data-i18n="diag.bon_niveau">Bon niveau d\'optimisation.</span>';
                        elseif ($pct >= 40) echo '<span data-i18n="diag.partiel">Optimisation partielle, des améliorations sont possibles.</span>';
                        else echo '<span data-i18n="diag.insuffisant">Optimisation insuffisante, des actions correctives sont nécessaires.</span>';
                        ?>
                    </div>
                </div>

                <!-- Checks list -->
                <?php
                // Mapper les labels des checks vers les clés i18n
                $diagLabelMap = [
                    'Keyword dans le Title' => 'diag.kw_title',
                    'Cohérence Title ↔ H1' => 'diag.coherence_title_h1',
                    'Keyword dans Meta Description' => 'diag.kw_meta',
                    'Keyword dans l\'URL' => 'diag.kw_url',
                    'Richesse sémantique' => 'diag.richesse',
                    'Unicité du H1' => 'diag.unicite_h1',
                    'Longueur du Title' => 'diag.longueur_title',
                    'Longueur Meta Description' => 'diag.longueur_meta',
                    'Keyword dans le H1' => 'diag.kw_h1',
                    'Optimisation sémantique' => 'diag.optim_semantique',
                    'Risque de sur-optimisation' => 'diag.risque_suroptim',
                ];
                // Mapper les messages des checks vers les clés i18n
                $diagMsgMap = [
                    'Le mot-clé principal est présent dans le titre.' => 'diag.kw_title_bon',
                    'Le titre contient une partie du mot-clé mais pas la correspondance exacte.' => 'diag.kw_title_partiel',
                    'Le mot-clé principal est absent du titre.' => 'diag.kw_title_absent',
                    'Le Title et le H1 partagent un champ sémantique cohérent.' => 'diag.coherence_bon',
                    'Le Title et le H1 ont peu de mots-clés en commun.' => 'diag.coherence_partiel',
                    'Le Title et le H1 traitent de sujets différents.' => 'diag.coherence_mauvais',
                    'Title ou H1 manquant, impossible de vérifier la cohérence.' => 'diag.coherence_manquant',
                    'Aucune meta description définie.' => 'diag.meta_absente',
                    'Le mot-clé principal est présent dans la meta description.' => 'diag.kw_meta_bon',
                    'La meta description contient certains mots du keyword.' => 'diag.kw_meta_partiel',
                    'Le mot-clé est absent de la meta description.' => 'diag.kw_meta_absent',
                    'Le mot-clé est présent dans l\'URL.' => 'diag.kw_url_bon',
                    'L\'URL contient une partie du mot-clé.' => 'diag.kw_url_partiel',
                    'Le mot-clé est absent de l\'URL.' => 'diag.kw_url_absent',
                    'Un seul H1, c\'est correct.' => 'diag.h1_unique',
                    'Aucun H1 trouvé sur la page.' => 'diag.h1_absent',
                    'Le mot-clé principal est présent dans le H1.' => 'diag.kw_h1_bon',
                    'Le H1 contient une partie du mot-clé principal.' => 'diag.kw_h1_partiel',
                    'Le mot-clé principal est absent du H1.' => 'diag.kw_h1_absent',
                    'Pas de H1 pour vérifier la présence du keyword.' => 'diag.kw_h1_manquant',
                    'Aucun Title défini.' => 'diag.title_absent',
                ];
                ?>
                <?php foreach ($diagnostic['checks'] as $check): ?>
                    <?php
                    $checkLabelI18n = $diagLabelMap[$check['label']] ?? '';
                    $checkMsgI18n = $diagMsgMap[$check['message']] ?? '';
                    // Pour les messages avec des valeurs dynamiques (sprintf), extraire les params
                    $checkMsgParams = '';
                    if (!$checkMsgI18n) {
                        // Messages avec des valeurs numériques dynamiques
                        if (preg_match('/^Bon ratio de diversité lexicale \((\d+)% mots uniques\)\.$/', $check['message'], $m)) {
                            $checkMsgI18n = 'diag.richesse_bon';
                            $checkMsgParams = htmlspecialchars(json_encode(['pct' => $m[1]]));
                        } elseif (preg_match('/^Diversité lexicale moyenne \((\d+)% mots uniques\)\.$/', $check['message'], $m)) {
                            $checkMsgI18n = 'diag.richesse_partiel';
                            $checkMsgParams = htmlspecialchars(json_encode(['pct' => $m[1]]));
                        } elseif (preg_match('/^Faible diversité lexicale \((\d+)% mots uniques\)\.$/', $check['message'], $m)) {
                            $checkMsgI18n = 'diag.richesse_mauvais';
                            $checkMsgParams = htmlspecialchars(json_encode(['pct' => $m[1]]));
                        } elseif (preg_match('/^(\d+) balises H1 trouvées/', $check['message'], $m)) {
                            $checkMsgI18n = 'diag.h1_multiple';
                            $checkMsgParams = htmlspecialchars(json_encode(['n' => $m[1]]));
                        } elseif (preg_match('/^Title de (\d+) caractères \(idéal : 30-60\)\.$/', $check['message'], $m)) {
                            $checkMsgI18n = 'diag.title_bon';
                            $checkMsgParams = htmlspecialchars(json_encode(['n' => $m[1]]));
                        } elseif (preg_match('/^Title de (\d+) caractères, légèrement/', $check['message'], $m)) {
                            $checkMsgI18n = 'diag.title_long';
                            $checkMsgParams = htmlspecialchars(json_encode(['n' => $m[1]]));
                        } elseif (preg_match('/^Title de (\d+) caractères, trop long/', $check['message'], $m)) {
                            $checkMsgI18n = 'diag.title_trop_long';
                            $checkMsgParams = htmlspecialchars(json_encode(['n' => $m[1]]));
                        } elseif (preg_match('/^Meta description de (\d+) caractères \(idéal : 120-160\)\.$/', $check['message'], $m)) {
                            // Could be bon or mauvais — check points to distinguish
                            $checkMsgI18n = ($check['points'] >= 10) ? 'diag.meta_bon' : 'diag.meta_mauvais';
                            $checkMsgParams = htmlspecialchars(json_encode(['n' => $m[1]]));
                        } elseif (preg_match('/^Meta description de (\d+) caractères\.$/', $check['message'], $m)) {
                            $checkMsgI18n = 'diag.meta_partiel';
                            $checkMsgParams = htmlspecialchars(json_encode(['n' => $m[1]]));
                        } elseif (preg_match('/couverture sémantique \(ICS : (\d+)%\)/', $check['message'], $m)) {
                            if (strpos($check['message'], 'Bonne') !== false) $checkMsgI18n = 'diag.ics_bon';
                            elseif (strpos($check['message'], 'partielle') !== false) $checkMsgI18n = 'diag.ics_partiel';
                            else $checkMsgI18n = 'diag.ics_mauvais';
                            $checkMsgParams = htmlspecialchars(json_encode(['pct' => $m[1]]));
                        } elseif (preg_match('/sur-optimisation.*\(ISR : (\d+)%\)/', $check['message'], $m)) {
                            if (strpos($check['message'], 'Pas de') !== false) $checkMsgI18n = 'diag.isr_bon';
                            elseif (strpos($check['message'], 'modéré') !== false) $checkMsgI18n = 'diag.isr_partiel';
                            else $checkMsgI18n = 'diag.isr_mauvais';
                            $checkMsgParams = htmlspecialchars(json_encode(['pct' => $m[1]]));
                        }
                    }
                    ?>
                    <div class="seo-check-row">
                        <div class="status-icon <?= htmlspecialchars($check['status']) ?>">
                            <?php
                            if ($check['status'] === 'bon') echo '&#10003;';
                            elseif ($check['status'] === 'attention') echo '!';
                            else echo '&#10005;';
                            ?>
                        </div>
                        <div class="check-content">
                            <div class="check-label" <?php if ($checkLabelI18n): ?>data-i18n="<?= $checkLabelI18n ?>"<?php endif; ?>><?= htmlspecialchars($check['label']) ?></div>
                            <div class="check-message" <?php if ($checkMsgI18n): ?>data-i18n="<?= $checkMsgI18n ?>"<?php if ($checkMsgParams): ?> data-i18n-params='<?= $checkMsgParams ?>'<?php endif; ?><?php endif; ?>><?= htmlspecialchars($check['message']) ?></div>
                        </div>
                        <div class="check-points" data-i18n="diag.pts" data-i18n-params='<?= htmlspecialchars(json_encode(['n' => $check['points']])) ?>'><?= $check['points'] ?> pts</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ─── Recommandations ─────────────────────────────────────────── -->
        <?php if (!empty($recommendations)): ?>
        <div class="card mb-4">
            <div class="card-header py-3">
                <h2 class="h6 mb-0 fw-bold" data-i18n="reco.titre">Recommandations</h2>
            </div>
            <div class="card-body">
                <?php
                // Mapper les labels de recommandation vers les clés i18n
                $recoLabelMap = [
                    'Title optimisé' => 'reco.title_label',
                    'H1 optimisé' => 'reco.h1_label',
                    'Meta description optimisée' => 'reco.meta_label',
                    'Angle SEO' => 'reco.angle_label',
                    'Enrichir le contenu' => 'reco.contenu_label',
                ];
                // Mapper les raisons de recommandation vers les clés i18n
                $recoReasonMap = [
                    'Le titre actuel dépasse 60 caractères et/ou ne contient pas le keyword principal.' => 'reco.title_reason_long',
                    'Le keyword principal devrait figurer dans le titre pour un meilleur référencement.' => 'reco.title_reason_kw',
                    'Aucun H1 n\'est défini. Ajoutez un H1 contenant le mot-clé principal.' => 'reco.h1_reason_absent',
                    'Le H1 actuel ne contient pas le mot-clé principal identifié.' => 'reco.h1_reason_kw',
                    'Aucune meta description définie. Elle aide au CTR dans les résultats de recherche.' => 'reco.meta_reason_absent',
                    'La meta description ne mentionne pas le keyword principal.' => 'reco.meta_reason_kw',
                    'Le Title et le H1 ciblent des thématiques différentes, ce qui dilue le signal SEO.' => 'reco.angle_reason',
                    'Un contenu trop court limite les chances de positionnement sur des requêtes compétitives.' => 'reco.contenu_reason',
                ];
                ?>
                <?php foreach ($recommendations as $rec): ?>
                    <?php
                    $recoLabelI18n = $recoLabelMap[$rec['label']] ?? '';
                    $recoReasonI18n = $recoReasonMap[$rec['reason']] ?? '';
                    ?>
                    <div class="rec-item">
                        <div class="rec-label" <?php if ($recoLabelI18n): ?>data-i18n="<?= $recoLabelI18n ?>"<?php endif; ?>><?= htmlspecialchars($rec['label']) ?></div>
                        <div class="rec-reason" <?php if ($recoReasonI18n): ?>data-i18n="<?= $recoReasonI18n ?>"<?php endif; ?>><?= htmlspecialchars($rec['reason']) ?></div>
                        <div class="rec-compare">
                            <div class="rec-current">
                                <span class="rec-tag" data-i18n="reco.tag_actuel">Actuel</span>
                                <?= htmlspecialchars($rec['current']) ?>
                            </div>
                            <div class="rec-proposed">
                                <span class="rec-tag" data-i18n="reco.tag_propose">Proposé</span>
                                <?= htmlspecialchars($rec['proposed']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<script src="translations.js"></script>
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>

<script>
// ─── i18n ──────────────────────────────────────────────────────────────
var baseUrl = window.MODULE_BASE_URL || '.';

var langueActuelle = (function () {
    if (typeof window.PLATFORM_LANG === 'string' && window.PLATFORM_LANG) return window.PLATFORM_LANG;
    try { var p = new URLSearchParams(window.location.search).get('lg'); if (p) return p; } catch (_) {}
    try { var s = localStorage.getItem('lang'); if (s) return s; } catch (_) {}
    return 'fr';
})();

function t(cle, params) {
    var trad = (typeof TRANSLATIONS !== 'undefined' && TRANSLATIONS[langueActuelle] && TRANSLATIONS[langueActuelle][cle])
        ? TRANSLATIONS[langueActuelle][cle]
        : (typeof TRANSLATIONS !== 'undefined' && TRANSLATIONS.fr && TRANSLATIONS.fr[cle])
            ? TRANSLATIONS.fr[cle]
            : cle;
    if (params) {
        Object.keys(params).forEach(function (k) {
            trad = trad.replace(new RegExp('\\{' + k + '\\}', 'g'), params[k]);
        });
    }
    return trad;
}

function traduirePage() {
    document.querySelectorAll('[data-i18n]').forEach(function (el) {
        var cle = el.getAttribute('data-i18n');
        var paramsAttr = el.getAttribute('data-i18n-params');
        var params = null;
        if (paramsAttr) {
            try { params = JSON.parse(paramsAttr); } catch (_) {}
        }
        el.innerHTML = t(cle, params);
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
        el.placeholder = t(el.getAttribute('data-i18n-placeholder'));
    });
    document.querySelectorAll('[data-i18n-title]').forEach(function (el) {
        el.title = t(el.getAttribute('data-i18n-title'));
    });
    // Traduire les labels de concurrence avec paramètres dynamiques
    document.querySelectorAll('[data-i18n="kw.concurrence"]').forEach(function (el) {
        var paramsAttr = el.getAttribute('data-i18n-params');
        if (paramsAttr) {
            try {
                var params = JSON.parse(paramsAttr);
                // Traduire le niveau de concurrence inclus dans le paramètre
                var levelKey = 'competition.' + (params.level === 'Faible' || params.level === 'Low' ? 'faible' :
                    params.level === 'Moyen' || params.level === 'Medium' ? 'moyen' : 'eleve');
                params.level = t(levelKey);
                el.innerHTML = t('kw.concurrence', params);
            } catch (_) {
                el.innerHTML = t('kw.concurrence', params);
            }
        }
    });
}

function changerLangue(lng) {
    langueActuelle = lng;
    try { localStorage.setItem('lang', lng); } catch (_) {}
    traduirePage();
}

function initLangueSelect() {
    var select = document.getElementById('lang-select');
    if (!select) return;
    select.value = langueActuelle;
    select.addEventListener('change', function () { changerLangue(this.value); });
}

if (typeof window !== 'undefined') {
    window.addEventListener('platformLangChange', function (e) {
        if (e.detail && e.detail.lang) changerLangue(e.detail.lang);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    traduirePage();
    initLangueSelect();
});
</script>

<?php if (!empty($keywords['term_details'])): ?>
<script>
(function() {
    var termData = <?= json_encode($keywords['term_details'], JSON_UNESCAPED_UNICODE) ?>;
    var container = document.getElementById('treemap');
    var tooltip = document.getElementById('treemapTooltip');
    if (!container || !termData.length) return;

    // ─── Squarified Treemap Algorithm ───────────────────────────────
    function worstRatio(row, side) {
        var s = 0; for (var i = 0; i < row.length; i++) s += row[i]._area;
        var maxA = -Infinity, minA = Infinity;
        for (var i = 0; i < row.length; i++) {
            if (row[i]._area > maxA) maxA = row[i]._area;
            if (row[i]._area < minA) minA = row[i]._area;
        }
        var s2 = s * s, side2 = side * side;
        return Math.max((side2 * maxA) / s2, s2 / (side2 * minA));
    }

    function layoutRow(row, x, y, w, h) {
        var rowArea = 0; for (var i = 0; i < row.length; i++) rowArea += row[i]._area;
        var rects = [];
        if (w >= h) {
            var rw = rowArea / h, cy = y;
            for (var i = 0; i < row.length; i++) {
                var rh = row[i]._area / rw;
                rects.push({d: row[i], x: x, y: cy, w: rw, h: rh});
                cy += rh;
            }
        } else {
            var rh = rowArea / w, cx = x;
            for (var i = 0; i < row.length; i++) {
                var rw = row[i]._area / rh;
                rects.push({d: row[i], x: cx, y: y, w: rw, h: rh});
                cx += rw;
            }
        }
        return rects;
    }

    function squarify(children, row, x, y, w, h) {
        if (children.length === 0) return layoutRow(row, x, y, w, h);
        var c = children[0];
        var newRow = row.concat([c]);
        var side = Math.min(w, h);
        if (row.length === 0 || worstRatio(newRow, side) <= worstRatio(row, side)) {
            return squarify(children.slice(1), newRow, x, y, w, h);
        } else {
            var laid = layoutRow(row, x, y, w, h);
            var rowArea = 0; for (var i = 0; i < row.length; i++) rowArea += row[i]._area;
            if (w >= h) {
                var dw = rowArea / h;
                return laid.concat(squarify(children, [], x + dw, y, w - dw, h));
            } else {
                var dh = rowArea / w;
                return laid.concat(squarify(children, [], x, y + dh, w, h - dh));
            }
        }
    }

    // ─── Status label mapping for i18n ────────────────────────────────
    var statusI18nMap = {
        'optimal': 'term.optimal',
        'sous-optimisé': 'term.sous_optimise',
        'sur-optimisé': 'term.sur_optimise'
    };

    // ─── Render ─────────────────────────────────────────────────────
    var H = 280;
    var statusColors = {
        'optimal':        {bg: 'rgba(34,197,94,0.18)', border: 'rgba(34,197,94,0.5)', text: '#166534'},
        'sous-optimisé':  {bg: 'rgba(249,115,22,0.15)', border: 'rgba(249,115,22,0.5)', text: '#92400e'},
        'sur-optimisé':   {bg: 'rgba(239,68,68,0.15)', border: 'rgba(239,68,68,0.5)', text: '#991b1b'}
    };
    var zoneLabels = {title:'Title',h1:'H1',h2:'H2',url:'URL',meta_desc:'Meta',body:'Body',img_alts:'Img',links_text:'Links'};

    termData.sort(function(a, b) { return b.score - a.score; });
    var total = 0;
    for (var i = 0; i < termData.length; i++) total += termData[i].score;

    function render() {
        container.innerHTML = '';
        var W = container.clientWidth;
        container.style.height = H + 'px';

        for (var i = 0; i < termData.length; i++) {
            termData[i]._area = (termData[i].score / total) * W * H;
        }

        var rects = squarify(termData, [], 0, 0, W, H);

        for (var i = 0; i < rects.length; i++) {
            var r = rects[i], d = r.d;
            var colors = statusColors[d.status] || statusColors['optimal'];
            var el = document.createElement('div');
            el.className = 'treemap-cell';
            el.style.cssText = 'left:'+r.x+'px;top:'+r.y+'px;width:'+r.w+'px;height:'+r.h+'px;' +
                'background:'+colors.bg+';border-color:'+colors.border+';color:'+colors.text+';';

            var label = '<span class="tc-term">' + d.term + '</span>';
            if (r.w > 90 && r.h > 50) {
                label += '<span class="tc-score">' + d.score + '</span>';
            }
            el.innerHTML = label;

            (function(d, el) {
                el.addEventListener('mouseenter', function(e) {
                    var zones = [];
                    for (var j = 0; j < d.zones.length; j++) zones.push(zoneLabels[d.zones[j]] || d.zones[j]);
                    var statusLabel = (typeof t === 'function' && statusI18nMap[d.status]) ? t(statusI18nMap[d.status]) : d.status;
                    tooltip.innerHTML =
                        '<strong>' + d.term + '</strong><br>' +
                        t('treemap.score', {score: d.score}) + '<br>' +
                        t('treemap.zones', {zones: zones.join(', ')}) + '<br>' +
                        t('treemap.densite', {density: d.density}) + '<br>' +
                        t('treemap.occurrences', {count: d.body_count}) + '<br>' +
                        '<span class="tt-status tt-' + d.status + '">' + statusLabel + '</span>';
                    tooltip.style.display = 'block';
                });
                el.addEventListener('mousemove', function(e) {
                    var cx = container.getBoundingClientRect();
                    var tx = e.clientX - cx.left + 12;
                    var ty = e.clientY - cx.top - 10;
                    if (tx + 200 > container.clientWidth) tx = tx - 220;
                    tooltip.style.left = tx + 'px';
                    tooltip.style.top = ty + 'px';
                });
                el.addEventListener('mouseleave', function() {
                    tooltip.style.display = 'none';
                });
            })(d, el);

            container.appendChild(el);
        }
    }

    render();

    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(render, 200);
    });
})();
</script>
<?php endif; ?>

</body>
</html>
