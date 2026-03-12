<?php

// ─── Stopwords FR (~150 mots) ──────────────────────────────────────────────
const STOPWORDS_FR = [
    'le','la','les','un','une','des','de','du','au','aux','ce','ces','cette',
    'cet','mon','ma','mes','ton','ta','tes','son','sa','ses','notre','nos',
    'votre','vos','leur','leurs','je','tu','il','elle','on','nous','vous',
    'ils','elles','me','te','se','lui','en','y','qui','que','quoi','dont',
    'ou','où','et','mais','ou','donc','or','ni','car','si','ne','pas','plus',
    'moins','très','bien','mal','peu','trop','aussi','encore','toujours',
    'jamais','rien','tout','tous','toute','toutes','même','autre','autres',
    'avec','pour','par','sur','sous','dans','entre','vers','chez','sans',
    'avant','après','depuis','pendant','contre','comme','être','avoir',
    'faire','dire','aller','voir','savoir','pouvoir','vouloir','devoir',
    'falloir','est','sont','été','était','sera','fait','peut','doit',
    'a','ai','as','avons','avez','ont','suis','es','sommes','êtes',
    'c','d','j','l','m','n','s','t','qu','à','ça','ci','là',
    'the','a','an','and','or','but','in','on','at','to','for','of','with',
    'is','are','was','were','be','been','has','have','had','do','does','did',
    'not','no','this','that','these','those','it','its','i','you','he','she',
    'we','they','my','your','his','her','our','their','what','which','who',
    'how','when','where','why','will','would','can','could','should','may',
    'might','shall','must','than','then','so','if','about','up','out','just',
    'also','from','by','all','each','every','both','few','more','most',
    'some','any','many','much','own','other','new','old','big','small',
    'long','short','good','bad','great','little','right','left','part',
    'only','own','same','first','last','next','still','already','entre',
    'ainsi','alors','cela','celui','celle','ceux','celles','chaque',
    'deux','trois','quatre','cinq','six','sept','huit','neuf','dix',
    'cent','mille','premier','première','dernier','dernière','fois',
    'temps','jour','vie','monde','main','chose','cas',
    'point','fait','ici','quand','comment','pourquoi','parce',
    'www','http','https','com','fr','org','html','php','page',
];

// ─── Mots indicateurs d'intention de recherche ─────────────────────────────
const INTENT_TRANSACTIONAL = [
    'acheter','achat','prix','tarif','promo','promotion','solde','soldes',
    'pas cher','meilleur prix','commander','commande','livraison','boutique',
    'magasin','offre','réduction','discount','vente','bon plan','devis',
    'buy','purchase','price','cheap','deal','order','shop','store','sale',
];

const INTENT_COMMERCIAL = [
    'comparatif','comparateur','avis','test','meilleur','top','classement',
    'versus','vs','review','alternative','quel','quelle','choisir','guide',
    'sélection','recommandation','best','compare','comparison','rating',
];

const INTENT_NAVIGATIONAL = [
    'connexion','login','compte','espace client','mon compte','inscription',
    'contact','accueil','page','site officiel','official','sign in','log in',
];

// ─── Récupération HTTP (via cURL) ─────────────────────────────────────────

/**
 * Récupère le HTML d'une page via cURL.
 * Suit les redirections, simule un navigateur réel via User-Agent et headers.
 *
 * @return array{status: string, html: string, finalUrl: string, httpCode: int, error?: string}
 */
function fetch_page(string $url): array
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_ENCODING       => '',  // Accepte gzip, deflate, br
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: fr-FR,fr;q=0.9,en;q=0.8',
            'Cache-Control: no-cache',
        ],
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    ]);

    $html = curl_exec($ch);
    $erreur = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;

    curl_close($ch);

    if ($html === false || $erreur !== '') {
        return [
            'status'   => 'error',
            'html'     => '',
            'finalUrl' => $finalUrl,
            'httpCode' => $httpCode,
            'error'    => 'cURL : ' . ($erreur ?: 'aucune réponse'),
        ];
    }

    if ($httpCode >= 400) {
        return [
            'status'   => 'error',
            'html'     => '',
            'finalUrl' => $finalUrl,
            'httpCode' => $httpCode,
            'error'    => "Erreur HTTP {$httpCode}",
        ];
    }

    return [
        'status'   => 'ok',
        'html'     => $html,
        'finalUrl' => $finalUrl,
        'httpCode' => $httpCode,
    ];
}

// ─── Parsing HTML ──────────────────────────────────────────────────────────

function parse_page(string $html, string $url): array
{
    $result = [
        'title'            => '',
        'meta_description' => '',
        'meta_keywords'    => '',
        'canonical'        => '',
        'h1'               => [],
        'h2'               => [],
        'h3'               => [],
        'body_text'        => '',
        'url_parts'        => [],
        'img_alts'         => [],
        'links_text'       => [],
        'word_count'       => 0,
    ];

    // Décomposition URL
    $parsed = parse_url($url);
    $path = $parsed['path'] ?? '';
    $segments = array_filter(explode('/', $path), fn($s) => $s !== '');
    $result['url_parts'] = array_values(array_filter(
        array_map(function ($seg) {
            // Retirer les extensions et remplacer tirets/underscores par des espaces
            $seg = preg_replace('/\.[a-z]{2,5}$/i', '', $seg);
            $seg = str_replace(['-', '_'], ' ', $seg);
            return trim($seg);
        }, $segments),
        fn($seg) => mb_strlen($seg) >= 4
    ));

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);

    // Title
    $titles = $doc->getElementsByTagName('title');
    if ($titles->length > 0) {
        $result['title'] = trim($titles->item(0)->textContent);
    }

    // Meta tags
    $metas = $doc->getElementsByTagName('meta');
    foreach ($metas as $meta) {
        $name = strtolower($meta->getAttribute('name'));
        $content = $meta->getAttribute('content');
        if ($name === 'description') {
            $result['meta_description'] = trim($content);
        } elseif ($name === 'keywords') {
            $result['meta_keywords'] = trim($content);
        }
    }

    // Canonical
    $links = $doc->getElementsByTagName('link');
    foreach ($links as $link) {
        if (strtolower($link->getAttribute('rel')) === 'canonical') {
            $result['canonical'] = trim($link->getAttribute('href'));
            break;
        }
    }

    // Headings
    foreach (['h1', 'h2', 'h3'] as $tag) {
        $nodes = $doc->getElementsByTagName($tag);
        foreach ($nodes as $node) {
            $text = trim($node->textContent);
            if ($text !== '') {
                $result[$tag][] = $text;
            }
        }
    }

    // Images alt
    $imgs = $doc->getElementsByTagName('img');
    foreach ($imgs as $img) {
        $alt = trim($img->getAttribute('alt'));
        if ($alt !== '') {
            $result['img_alts'][] = $alt;
        }
    }

    // Links text (internes)
    $anchors = $doc->getElementsByTagName('a');
    $host = $parsed['host'] ?? '';
    foreach ($anchors as $a) {
        $href = $a->getAttribute('href');
        $linkHost = parse_url($href, PHP_URL_HOST);
        // Liens internes ou relatifs
        if ($linkHost === null || $linkHost === '' || $linkHost === $host) {
            $text = trim($a->textContent);
            if ($text !== '' && mb_strlen($text) < 200) {
                $result['links_text'][] = $text;
            }
        }
    }

    // Body text (sans scripts/styles)
    $scripts = $xpath->query('//script|//style|//noscript|//nav|//footer|//header');
    foreach ($scripts as $script) {
        $script->parentNode->removeChild($script);
    }
    $body = $doc->getElementsByTagName('body');
    if ($body->length > 0) {
        $rawText = $body->item(0)->textContent;
        // Nettoyage
        $rawText = preg_replace('/\s+/', ' ', $rawText);
        $result['body_text'] = trim($rawText);
    }

    // Word count
    $words = preg_split('/\s+/', $result['body_text'], -1, PREG_SPLIT_NO_EMPTY);
    $result['word_count'] = count($words);

    return $result;
}

// ─── Analyse sémantique ────────────────────────────────────────────────────

function tokenize(string $text): array
{
    $text = mb_strtolower($text);
    // Garder les lettres, chiffres, accents
    $text = preg_replace('/[^\p{L}\p{N}\s\'-]/u', ' ', $text);
    $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    // Filtrer stopwords et mots trop courts
    $filtered = array_values(array_filter($tokens, function ($t) {
        return mb_strlen($t) >= 3 && !in_array($t, STOPWORDS_FR, true);
    }));
    // Stemmer chaque token
    return array_map('stem_token', $filtered);
}

// ─── Stemmer français (suffix-stripping) ────────────────────────────────────

function get_french_stemmer(): \Wamania\Snowball\Stemmer\French
{
    static $stemmer = null;
    if ($stemmer === null) {
        $stemmer = new \Wamania\Snowball\Stemmer\French();
    }
    return $stemmer;
}

function stem_token(string $token): string
{
    $len = mb_strlen($token);
    if ($len < 4) return $token;

    // Suffixes ordonnés du plus long au plus court
    // Chaque paire : [suffixe à retirer, remplacement]
    $rules = [
        ['issement', ''],
        ['ement', ''],
        ['ment', ''],
        ['ches', 'c'],     // blanches → blanc
        ['che', 'c'],      // blanche → blanc
        ['gues', 'g'],     // longues → long
        ['gue', 'g'],      // longue → long
        ['euses', 'eux'],  // heureuses → heureux
        ['euse', 'eux'],   // heureuse → heureux
        ['ères', 'er'],    // premières → premier
        ['ère', 'er'],     // première → premier
        ['ives', 'if'],    // sportives → sportif
        ['ive', 'if'],     // sportive → sportif
        ['ées', 'é'],      // colorées → coloré
        ['ée', 'é'],       // colorée → coloré
        ['es', ''],        // robes → rob
        ['e', ''],         // robe → rob
        ['s', ''],         // blancs → blanc
    ];

    foreach ($rules as [$suffix, $replacement]) {
        $suffixLen = mb_strlen($suffix);
        if ($len > $suffixLen + 2 && mb_substr($token, -$suffixLen) === $suffix) {
            $stem = mb_substr($token, 0, $len - $suffixLen) . $replacement;
            return $stem !== '' ? $stem : $token;
        }
    }

    return $token;
}

function tokenize_with_surface(string $text, float $weight, array &$surfaceMap): array
{
    $text = mb_strtolower($text);
    $text = preg_replace('/[^\p{L}\p{N}\s\'-]/u', ' ', $text);
    $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    // Filtrer stopwords et mots trop courts
    $filtered = array_values(array_filter($tokens, function ($t) {
        return mb_strlen($t) >= 3 && !in_array($t, STOPWORDS_FR, true);
    }));

    $stems = [];
    foreach ($filtered as $original) {
        $stem = stem_token($original);
        $stems[] = $stem;
        // Tracker la forme de surface la plus pondérée pour chaque stem
        if (!isset($surfaceMap[$stem])) {
            $surfaceMap[$stem] = [];
        }
        $surfaceMap[$stem][$original] = ($surfaceMap[$stem][$original] ?? 0) + $weight;
    }

    return ['stems' => $stems, 'originals' => $filtered];
}

function resolve_surface_ngram(string $stemNgram, array $ngramSurfaceMap, array $surfaceMap): string
{
    // Essayer d'abord la résolution par n-gram complet (préserve le contexte : "robe blanche")
    if (isset($ngramSurfaceMap[$stemNgram]) && !empty($ngramSurfaceMap[$stemNgram])) {
        $candidates = $ngramSurfaceMap[$stemNgram];
        // Tiebreaker : préférer les formes fléchies (plus longues)
        foreach ($candidates as $surface => $w) {
            $candidates[$surface] = $w + mb_strlen($surface) * 0.1;
        }
        arsort($candidates);
        return array_key_first($candidates);
    }

    // Fallback : résolution par stem individuel
    $stems = explode(' ', $stemNgram);
    $resolved = [];
    foreach ($stems as $stem) {
        if (isset($surfaceMap[$stem]) && !empty($surfaceMap[$stem])) {
            $candidates = $surfaceMap[$stem];
            foreach ($candidates as $surface => $w) {
                $candidates[$surface] = $w + mb_strlen($surface) * 0.1;
            }
            arsort($candidates);
            $resolved[] = array_key_first($candidates);
        } else {
            $resolved[] = $stem;
        }
    }
    return implode(' ', $resolved);
}

// ─── Google Suggest : validation de l'ordre des mots ──────────────────────

/**
 * Interroge l'API Google Suggest et retourne les suggestions.
 * Cache fichier (7 jours) dans cache/suggest/.
 *
 * @return string[] Tableau de suggestions brutes
 */
function interroger_google_suggest(string $requete): array
{
    $cacheDir = __DIR__ . '/cache/suggest';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    $cacheKey = md5($requete);
    $cacheFichier = $cacheDir . '/' . $cacheKey . '.json';
    $ttl = 7 * 24 * 3600; // 7 jours

    // Vérifier le cache
    if (file_exists($cacheFichier) && (time() - filemtime($cacheFichier)) < $ttl) {
        $contenuCache = file_get_contents($cacheFichier);
        if ($contenuCache !== false) {
            $donnees = json_decode($contenuCache, true);
            if (is_array($donnees)) {
                return $donnees;
            }
        }
    }

    // Appel HTTP vers Google Suggest
    $url = 'https://suggestqueries.google.com/complete/search?client=firefox&q='
        . urlencode($requete) . '&hl=fr&gl=FR';

    $contexte = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'timeout' => 5,
        ],
    ]);

    $reponse = @file_get_contents($url, false, $contexte);
    if ($reponse === false) {
        return [];
    }

    $json = json_decode($reponse, true);
    if (!is_array($json) || !isset($json[1]) || !is_array($json[1])) {
        return [];
    }

    $suggestions = $json[1];

    // Sauvegarder en cache
    file_put_contents($cacheFichier, json_encode($suggestions, JSON_UNESCAPED_UNICODE));

    // Rate limiting : 200ms entre chaque appel
    usleep(200000);

    return $suggestions;
}

/**
 * Valide et corrige l'ordre et la composition du keyword principal via Google Suggest.
 *
 * Phase 1 : Permutations des mots de catégorie (réordonne les mots existants).
 * Phase 2 : Substitutions — pour chaque mot de catégorie, essaie de le remplacer
 *           par un unigram top-scoré de la page et compare via Google Suggest.
 *
 * @param string $keyword    Le keyword résolu (formes de surface)
 * @param array  $termZones  Carte zones par stem (pour détecter les entités)
 * @param array  $surfaceMap Carte stem → formes de surface
 * @param array  $topTermes  Termes résolus avec scores (surface → score)
 * @return array{keyword: string, suggest_debug: array}
 */
function valider_ordre_keyword(string $keyword, array $termZones, array $surfaceMap, array $topTermes = []): array
{
    $mots = explode(' ', $keyword);
    $debug = [
        'keyword_avant' => $keyword,
        'resultat'      => 'inchange',
    ];

    // Moins de 2 mots → rien à valider
    if (count($mots) < 2) {
        return ['keyword' => $keyword, 'suggest_debug' => $debug];
    }

    // Séparer l'entité (marque) des mots de catégorie.
    // Une entité est un mot dont le stem est présent dans URL + links_text.
    $motsCat = [];
    $entite = null;
    $dernierMot = end($mots);
    $dernierStem = stem_token(mb_strtolower($dernierMot));

    // Vérifier si le dernier mot est une entité forte (marque)
    if (
        isset($termZones[$dernierStem]['url'])
        && isset($termZones[$dernierStem]['links_text'])
    ) {
        $entite = $dernierMot;
        $motsCat = array_slice($mots, 0, -1);
    } else {
        $motsCat = $mots;
    }

    // Moins de 2 mots de catégorie → rien à permuter
    if (count($motsCat) < 2) {
        $debug['resultat'] = 'skip_trop_peu_mots_cat';
        return ['keyword' => $keyword, 'suggest_debug' => $debug];
    }

    // Limiter les permutations (max 3 mots = 6 permutations)
    if (count($motsCat) > 3) {
        $debug['resultat'] = 'skip_trop_mots_cat';
        return ['keyword' => $keyword, 'suggest_debug' => $debug];
    }

    $debug['entite'] = $entite;
    $debug['mots_categorie'] = $motsCat;

    // ─── Phase 1 : Permutations (réordonnement) ─────────────────────────
    $permutations = [];
    generer_permutations($motsCat, 0, count($motsCat) - 1, $permutations);
    $debug['nb_permutations'] = count($permutations);

    $meilleurScore = -1;
    $meilleurKeyword = $keyword;
    $detailsPermutations = [];

    foreach ($permutations as $perm) {
        $partieCategorie = implode(' ', $perm);
        $requete = $entite !== null
            ? $partieCategorie . ' ' . $entite
            : $partieCategorie;

        $resultat = scorer_requete_suggest($requete);
        $detailsPermutations[] = $resultat;

        if ($resultat['score'] > $meilleurScore) {
            $meilleurScore = $resultat['score'];
            $meilleurKeyword = $requete;
        }
    }

    $debug['permutations'] = $detailsPermutations;

    // ─── Phase 2 : Substitutions (remplacement de mots) ─────────────────
    // Collecter les unigrams candidats (mots simples de la page, absents du keyword)
    $motsKeyword = array_map('mb_strtolower', $mots);
    $candidats = [];
    foreach ($topTermes as $terme => $score) {
        // Uniquement les unigrams
        if (str_contains($terme, ' ')) {
            continue;
        }
        // Pas déjà dans le keyword
        if (in_array(mb_strtolower($terme), $motsKeyword, true)) {
            continue;
        }
        $candidats[$terme] = $score;
        // Limiter à 5 candidats pour ne pas exploser les requêtes
        if (count($candidats) >= 5) {
            break;
        }
    }

    $debug['candidats_substitution'] = array_keys($candidats);
    $detailsSubstitutions = [];

    if (!empty($candidats)) {
        // Pour chaque mot de catégorie, essayer de le remplacer par chaque candidat.
        // Pour limiter le nombre de requêtes, on ne teste que l'ordre naturel
        // (candidat en position du mot remplacé) + l'ordre inversé si 2 mots.
        foreach ($motsCat as $idx => $motOriginal) {
            foreach ($candidats as $candidat => $candidatScore) {
                $motsCatModifies = $motsCat;
                $motsCatModifies[$idx] = $candidat;

                // Tester l'ordre tel quel + l'inversé (si 2 mots)
                $ordresATester = [$motsCatModifies];
                if (count($motsCatModifies) === 2) {
                    $ordresATester[] = array_reverse($motsCatModifies);
                } elseif (count($motsCatModifies) === 3) {
                    // Pour 3 mots, tester aussi candidat en première position
                    $candidatPremier = $motsCatModifies;
                    // Mettre le candidat en position 0 s'il n'y est pas déjà
                    if ($idx !== 0) {
                        array_splice($candidatPremier, $idx, 1);
                        array_unshift($candidatPremier, $candidat);
                    }
                    $ordresATester[] = $candidatPremier;
                }

                $dejaTeste = [];
                foreach ($ordresATester as $ordre) {
                    $partieCategorie = implode(' ', $ordre);
                    if (isset($dejaTeste[$partieCategorie])) {
                        continue;
                    }
                    $dejaTeste[$partieCategorie] = true;

                    $requete = $entite !== null
                        ? $partieCategorie . ' ' . $entite
                        : $partieCategorie;

                    $resultat = scorer_requete_suggest($requete);
                    $resultat['substitution'] = $motOriginal . ' → ' . $candidat;
                    $detailsSubstitutions[] = $resultat;

                    if ($resultat['score'] > $meilleurScore) {
                        $meilleurScore = $resultat['score'];
                        $meilleurKeyword = $requete;
                    }
                }
            }
        }
    }

    $debug['substitutions'] = $detailsSubstitutions;

    // ─── Résultat final ─────────────────────────────────────────────────
    if ($meilleurKeyword !== $keyword) {
        $debug['resultat'] = 'corrige';
        $debug['keyword_apres'] = $meilleurKeyword;
    } else {
        $debug['resultat'] = 'valide';
    }

    return ['keyword' => $meilleurKeyword, 'suggest_debug' => $debug];
}

/**
 * Score une requête via Google Suggest.
 *
 * @return array{requete: string, suggestions: int, score: int, raison: string}
 */
function scorer_requete_suggest(string $requete): array
{
    $suggestions = interroger_google_suggest($requete);
    $requeteNormalisee = mb_strtolower($requete);
    $score = 0;
    $raison = '';

    // Match exact dans les suggestions
    foreach ($suggestions as $s) {
        if (mb_strtolower($s) === $requeteNormalisee) {
            $score = 100;
            $raison = 'match_exact';
            break;
        }
    }

    // Début de suggestion (suggestion commence par la requête)
    if ($score < 100) {
        foreach ($suggestions as $s) {
            if (str_starts_with(mb_strtolower($s), $requeteNormalisee)) {
                $score = max($score, 80);
                $raison = $raison ?: 'debut_suggestion';
                break;
            }
        }
    }

    // Score secondaire basé sur le nombre de suggestions
    $scoreSecondaire = min(count($suggestions), 10) * 2; // max 20 points
    $score += $scoreSecondaire;
    if ($raison === '' && count($suggestions) > 0) {
        $raison = 'suggestions_comptees';
    }

    return [
        'requete'     => $requete,
        'suggestions' => count($suggestions),
        'score'       => $score,
        'raison'      => $raison ?: 'aucune_suggestion',
    ];
}

/**
 * Génère toutes les permutations d'un tableau (récursif, in-place).
 *
 * @param string[] $arr           Tableau à permuter
 * @param int      $debut         Index de début
 * @param int      $fin           Index de fin
 * @param array    $permutations  Référence vers le tableau de résultats
 */
function generer_permutations(array $arr, int $debut, int $fin, array &$permutations): void
{
    if ($debut === $fin) {
        $permutations[] = $arr;
        return;
    }
    for ($i = $debut; $i <= $fin; $i++) {
        [$arr[$debut], $arr[$i]] = [$arr[$i], $arr[$debut]];
        generer_permutations($arr, $debut + 1, $fin, $permutations);
        [$arr[$debut], $arr[$i]] = [$arr[$i], $arr[$debut]];
    }
}

function count_ngrams(array $tokens, int $n = 1): array
{
    $counts = [];
    $len = count($tokens);
    for ($i = 0; $i <= $len - $n; $i++) {
        $ngram = implode(' ', array_slice($tokens, $i, $n));
        $counts[$ngram] = ($counts[$ngram] ?? 0) + 1;
    }
    return $counts;
}

function calculate_soseo(array $topTerms, array $termZones): float
{
    if (empty($topTerms)) return 0;

    $coverageScores = [];
    foreach ($topTerms as $term => $score) {
        $zones = $termZones[$term] ?? [];
        $coverage = 0;
        if (isset($zones['title']))     $coverage += 25;
        if (isset($zones['h1']))        $coverage += 25;
        if (isset($zones['url']))       $coverage += 15;
        if (isset($zones['meta_desc'])) $coverage += 10;
        if (isset($zones['h2']))        $coverage += 10;
        if (isset($zones['body']))      $coverage += 15;
        $coverageScores[] = min($coverage, 100);
    }

    return array_sum($coverageScores) / count($coverageScores);
}

function calculate_dseo(array $topTerms, array $bodyNgramCounts, int $totalBodyWords, string $primaryKwStem): float
{
    if (empty($topTerms) || $totalBodyWords === 0) return 0;

    $overOptimized = 0;
    $count = count($topTerms);

    foreach ($topTerms as $term => $score) {
        $bodyCount = $bodyNgramCounts[$term] ?? 0;
        $wordCount = substr_count($term, ' ') + 1;
        $density = $bodyCount / $totalBodyWords * 100;

        $threshold = match ($wordCount) {
            1 => 3,
            2 => 2,
            default => 1.5,
        };

        if ($density > $threshold) {
            $overOptimized++;
        }
    }

    $dseo = ($overOptimized / $count) * 100;

    // Pénalité si le keyword principal a une densité > 4%
    $pkBodyCount = $bodyNgramCounts[$primaryKwStem] ?? 0;
    $pkDensity = $pkBodyCount / $totalBodyWords * 100;
    if ($pkDensity > 4) {
        $dseo += 30;
    }

    return min($dseo, 100);
}

function analyze_keywords(array $parsed): array
{
    // Zones texte (une seule chaîne)
    $textZones = [
        'h1'       => implode(' ', $parsed['h1']),
        'title'    => $parsed['title'],
        'url'      => implode(' ', $parsed['url_parts']),
        'h2'       => implode(' ', $parsed['h2']),
        'meta_desc'=> $parsed['meta_description'],
        'body'     => $parsed['body_text'],
    ];
    // Zones multi-items : tokenisées par item pour éviter les ngrams inter-items
    $arrayZones = [
        'img_alts'   => $parsed['img_alts'],
        'links_text' => $parsed['links_text'],
    ];

    $weights = [
        'h1'        => 5,
        'title'     => 4,
        'url'       => 3,
        'h2'        => 2,
        'meta_desc' => 2,
        'body'      => 1,
        'img_alts'   => 2,
        'links_text' => 2,
    ];

    $scores = [];
    $surfaceMap = [];
    $ngramSurfaceMap = [];
    $termZones = [];       // $termZones[stemTerm][zone] = true
    $bodyNgramCounts = []; // compteurs bruts body pour calcul de densité

    // Zones texte classiques (body log-damped)
    foreach ($textZones as $zone => $text) {
        if (trim($text) === '') continue;
        $w = $weights[$zone];
        $result = tokenize_with_surface($text, $w, $surfaceMap);
        $tokens = $result['stems'];
        $origTokens = $result['originals'];
        $useLog = ($zone === 'body');

        foreach ([1 => 1, 2 => 1.5, 3 => 2] as $n => $ngramBonus) {
            $ngrams = count_ngrams($tokens, $n);

            // Construire ngramSurfaceMap (formes de surface au niveau n-gram)
            $len = count($tokens);
            for ($i = 0; $i <= $len - $n; $i++) {
                $stemNgram = implode(' ', array_slice($tokens, $i, $n));
                $surfaceNgram = implode(' ', array_slice($origTokens, $i, $n));
                $ngramSurfaceMap[$stemNgram][$surfaceNgram] =
                    ($ngramSurfaceMap[$stemNgram][$surfaceNgram] ?? 0) + $w;
            }

            foreach ($ngrams as $term => $count) {
                // Tracker la présence par zone
                $termZones[$term][$zone] = true;

                // Compteurs bruts body pour densité
                if ($zone === 'body') {
                    $bodyNgramCounts[$term] = ($bodyNgramCounts[$term] ?? 0) + $count;
                }

                $effective = $useLog ? log(1 + $count) : $count;
                $scores[$term] = ($scores[$term] ?? 0) + $effective * $w * $ngramBonus;
            }
        }
    }

    // Zones array : tokeniser chaque item séparément puis agréger avec log
    foreach ($arrayZones as $zone => $items) {
        if (empty($items)) continue;
        $w = $weights[$zone];
        $perItem = [];
        foreach ($items as $item) {
            $result = tokenize_with_surface($item, $w, $surfaceMap);
            $tokens = $result['stems'];
            $origTokens = $result['originals'];
            foreach ([1 => 1, 2 => 1.5, 3 => 2] as $n => $ngramBonus) {
                $ngrams = count_ngrams($tokens, $n);

                // Construire ngramSurfaceMap
                $len = count($tokens);
                for ($i = 0; $i <= $len - $n; $i++) {
                    $stemNgram = implode(' ', array_slice($tokens, $i, $n));
                    $surfaceNgram = implode(' ', array_slice($origTokens, $i, $n));
                    $ngramSurfaceMap[$stemNgram][$surfaceNgram] =
                        ($ngramSurfaceMap[$stemNgram][$surfaceNgram] ?? 0) + $w;
                }

                foreach ($ngrams as $term => $count) {
                    $termZones[$term][$zone] = true;
                    $perItem[$term] = ($perItem[$term] ?? 0) + $count;
                }
            }
        }
        foreach ($perItem as $term => $count) {
            $wordCount = substr_count($term, ' ') + 1;
            $ngramBonus = [1 => 1, 2 => 1.5, 3 => 2][$wordCount] ?? 1;
            $scores[$term] = ($scores[$term] ?? 0) + log(1 + $count) * $w * $ngramBonus;
        }
    }

    // Position bonus : termes dans les 200 premiers caractères du body
    $bodyFirst200 = mb_substr($textZones['body'] ?? '', 0, 200);
    $earlyTokens = tokenize($bodyFirst200);
    $earlyNgrams = [];
    foreach ([1, 2, 3] as $n) {
        foreach (array_keys(count_ngrams($earlyTokens, $n)) as $ng) {
            $earlyNgrams[$ng] = true;
        }
    }

    // Appliquer bonus multi-zones + bonus position
    foreach ($scores as $term => &$scoreRef) {
        $zones = array_keys($termZones[$term] ?? []);
        $freqScore = $scoreRef;

        // Zone presence bonus
        $zoneBonus = 0;
        $inTitle = in_array('title', $zones);
        $inH1    = in_array('h1', $zones);
        $inUrl   = in_array('url', $zones);

        if ($inTitle && $inH1 && $inUrl) {
            $zoneBonus = 0.8;
        } elseif ($inTitle && $inH1) {
            $zoneBonus = 0.5;
        } elseif (count($zones) >= 3) {
            $zoneBonus = 0.3;
        }

        // Position bonus (premiers 200 caractères du body)
        $posBonus = isset($earlyNgrams[$term]) ? 0.2 : 0;

        $scoreRef = $freqScore * (1 + $zoneBonus + $posBonus);
    }
    unset($scoreRef);

    arsort($scores);

    // Filtrer : préférer les n-grams longs qui englobent des n-grams courts
    $topTerms = array_slice($scores, 0, 80, true);
    $filtered = [];
    foreach ($topTerms as $term => $score) {
        $dominated = false;
        foreach ($filtered as $kept => $keptScore) {
            if (str_contains($kept, $term) && $keptScore >= $score) {
                $dominated = true;
                break;
            }
        }
        if (!$dominated) {
            $filtered[$term] = $score;
        }
    }

    // Identifier le keyword principal : préférer les termes multi-mots
    // Bonus d'accord title+H1 (x1.5) pour la sélection
    $topTerm = array_key_first($filtered);
    $topScore = $filtered[$topTerm] ?? 0;
    $primaryKw = $topTerm;
    $primaryScore = $topScore;

    if ($topTerm !== null && !str_contains($topTerm, ' ')) {
        $bestMulti = '';
        $bestMultiScore = 0;
        foreach ($filtered as $term => $score) {
            if (!str_contains($term, ' ')) continue;
            if (!str_contains($term, $topTerm)) continue;

            $selectionScore = $score;
            // Bonus title+H1 agreement pour la sélection
            if (isset($termZones[$term]['title']) && isset($termZones[$term]['h1'])) {
                $selectionScore *= 1.5;
            }

            if ($selectionScore >= $topScore * 0.4 && $selectionScore > $bestMultiScore) {
                $bestMulti = $term;
                $bestMultiScore = $selectionScore;
            }
            if ($bestMulti !== '' && str_contains($term, $bestMulti) && $selectionScore >= $topScore * 0.3) {
                $bestMulti = $term;
                $bestMultiScore = $selectionScore;
            }
        }
        if ($bestMulti !== '') {
            $primaryKw = $bestMulti;
            $primaryScore = $filtered[$bestMulti];
        }
    }

    // Aussi tenter de promouvoir un bigram vers un trigram le contenant
    if ($primaryKw !== null && substr_count($primaryKw, ' ') === 1) {
        $bestTrigram = '';
        $bestTrigramScore = 0;
        foreach ($filtered as $term => $score) {
            if (substr_count($term, ' ') < 2) continue;         // doit être trigram+
            if (!str_contains($term, $primaryKw)) continue;      // doit contenir le bigram

            $selectionScore = $score;
            if (isset($termZones[$term]['title']) && isset($termZones[$term]['h1'])) {
                $selectionScore *= 1.5;
            }

            if ($selectionScore >= $primaryScore * 0.35 && $selectionScore > $bestTrigramScore) {
                $bestTrigram = $term;
                $bestTrigramScore = $selectionScore;
            }
        }
        if ($bestTrigram !== '') {
            $primaryKw = $bestTrigram;
            $primaryScore = $filtered[$bestTrigram];
        }
    }

    // Enrichissement H1 : si le H1 contient des termes significatifs absents du
    // keyword primaire et que ces termes sont des entités fortes (présentes dans
    // URL + titres produits), utiliser le H1 complet comme keyword.
    // Cas typique : PLP "Vêtements fille enfant Cyrillus" — le trigram "vetement fille
    // enfant" est sélectionné mais la marque "cyrillus" (dans URL + tous les produits)
    // est perdue car le H1 fait 4+ mots et les n-grams sont limités à 3.
    $h1Text = implode(' ', $parsed['h1']);
    $h1Debug = ['h1_raw' => $h1Text, 'primaryKw_before' => $primaryKw];
    if ($primaryKw !== null && trim($h1Text) !== '') {
        $h1Tokenized = tokenize_with_surface($h1Text, 0, $surfaceMap);
        $h1Stems = $h1Tokenized['stems'];
        $h1Debug['h1_stems'] = $h1Stems;
        $h1Debug['h1_stems_count'] = count($h1Stems);

        if (count($h1Stems) >= 2 && count($h1Stems) <= 6) {
            $primaryStems = explode(' ', $primaryKw);
            $missingStems = array_diff($h1Stems, $primaryStems);
            $h1Debug['primary_stems'] = $primaryStems;
            $h1Debug['missing_stems'] = array_values($missingStems);

            if (!empty($missingStems)) {
                // Vérifier que les termes manquants sont des entités fortes (URL + links_text)
                $hasStrongEntity = false;
                $entityCheck = [];
                foreach ($missingStems as $stem) {
                    $entityCheck[$stem] = [
                        'in_url' => isset($termZones[$stem]['url']),
                        'in_links_text' => isset($termZones[$stem]['links_text']),
                        'in_filtered' => isset($filtered[$stem]),
                        'zones' => array_keys($termZones[$stem] ?? []),
                    ];
                    if (isset($termZones[$stem]['url']) && isset($termZones[$stem]['links_text'])) {
                        $hasStrongEntity = true;
                    }
                }
                $h1Debug['entity_check'] = $entityCheck;
                $h1Debug['has_strong_entity'] = $hasStrongEntity;

                if ($hasStrongEntity) {
                    // Collecter les entités fortes manquantes
                    $strongEntityStems = [];
                    foreach ($missingStems as $stem) {
                        if (isset($termZones[$stem]['url']) && isset($termZones[$stem]['links_text'])) {
                            $strongEntityStems[] = $stem;
                        }
                    }

                    // Réordonner les stems du primary : garder la tête (catégorie produit)
                    // fixe en position 0, trier le reste par score desc pour refléter
                    // l'ordre de recherche naturel (ex: "robe femme blanc" > "robe blanc femme")
                    $head = $primaryStems[0];
                    $tail = array_slice($primaryStems, 1);
                    usort($tail, fn($a, $b) => ($scores[$b] ?? 0) <=> ($scores[$a] ?? 0));
                    $sortedPrimary = array_merge([$head], $tail);

                    // Construire le keyword : tête du primary + entités fortes, max 3 mots
                    $maxWords = 3;
                    $keepFromPrimary = max(1, $maxWords - count($strongEntityStems));
                    $newStems = array_merge(
                        array_slice($sortedPrimary, 0, $keepFromPrimary),
                        $strongEntityStems
                    );
                    $h1Debug['dropped_stems'] = array_slice($sortedPrimary, $keepFromPrimary);

                    $h1Score = 0;
                    foreach ($newStems as $stem) {
                        $h1Score += $scores[$stem] ?? 0;
                    }

                    $primaryKw = implode(' ', $newStems);
                    $primaryScore = $h1Score;
                    $h1Debug['result'] = 'enriched';
                } else {
                    $h1Debug['result'] = 'failed_no_strong_entity';
                }
            } else {
                $h1Debug['result'] = 'skipped_no_missing_stems';
            }
        } else {
            $h1Debug['result'] = 'skipped_stem_count';
        }
    } else {
        $h1Debug['result'] = 'skipped_no_h1';
    }

    $variants = [];
    foreach ($filtered as $term => $score) {
        if ($term === $primaryKw) continue;
        if (str_contains($primaryKw, $term) || str_contains($term, $primaryKw)) continue;
        if (count($variants) < 5) {
            $variants[] = ['term' => $term, 'score' => $score];
        }
    }

    // Calculer ICS et ISR (sur les stems, avant résolution)
    $top20Stems = array_slice($filtered, 0, 20, true);
    $totalBodyWords = $parsed['word_count'];

    $soseo = calculate_soseo($top20Stems, $termZones);
    $dseo = calculate_dseo($top20Stems, $bodyNgramCounts, $totalBodyWords, $primaryKw);

    // Construire term_details (top 20 termes avec infos détaillées)
    $termDetails = [];
    foreach ($top20Stems as $stemTerm => $score) {
        $zones = array_keys($termZones[$stemTerm] ?? []);
        $bodyCount = $bodyNgramCounts[$stemTerm] ?? 0;
        $wordCount = substr_count($stemTerm, ' ') + 1;
        $density = $totalBodyWords > 0 ? round($bodyCount / $totalBodyWords * 100, 2) : 0;

        $threshold = match ($wordCount) {
            1 => 3,
            2 => 2,
            default => 1.5,
        };

        if ($density > $threshold) {
            $status = 'sur-optimisé';
        } elseif (count($zones) <= 1) {
            $status = 'sous-optimisé';
        } else {
            $status = 'optimal';
        }

        $termDetails[] = [
            'term'       => resolve_surface_ngram($stemTerm, $ngramSurfaceMap, $surfaceMap),
            'score'      => round($score, 1),
            'zones'      => $zones,
            'body_count' => $bodyCount,
            'density'    => $density,
            'status'     => $status,
        ];
    }

    // Résoudre les formes de surface (stems → formes naturelles)
    $primaryKw = resolve_surface_ngram($primaryKw, $ngramSurfaceMap, $surfaceMap);
    foreach ($variants as &$v) {
        $v['term'] = resolve_surface_ngram($v['term'], $ngramSurfaceMap, $surfaceMap);
    }
    unset($v);
    $resolvedFiltered = [];
    foreach ($filtered as $stemTerm => $score) {
        $resolvedFiltered[resolve_surface_ngram($stemTerm, $ngramSurfaceMap, $surfaceMap)] = $score;
    }
    $filtered = $resolvedFiltered;

    // Valider l'ordre et la composition du keyword via Google Suggest
    $suggestResult = valider_ordre_keyword($primaryKw, $termZones, $surfaceMap, $filtered);
    $primaryKw = $suggestResult['keyword'];
    $suggestDebug = $suggestResult['suggest_debug'];

    // Intention de recherche
    $intent = detect_intent($primaryKw, $parsed);

    // Estimation concurrence
    $competition = estimate_competition($primaryKw);

    return [
        'primary_keyword' => $primaryKw,
        'primary_score'   => $primaryScore,
        'variants'        => $variants,
        'intent'          => $intent,
        'competition'     => $competition,
        'all_scores'      => array_slice($filtered, 0, 20, true),
        'soseo'           => round($soseo, 1),
        'dseo'            => round($dseo, 1),
        'term_details'    => $termDetails,
        'h1_debug'        => $h1Debug,
        'suggest_debug'   => $suggestDebug,
    ];
}

function detect_intent(string $keyword, array $parsed): array
{
    $allText = mb_strtolower($keyword . ' ' . $parsed['title'] . ' ' . implode(' ', $parsed['h1']));

    $scores = [
        'transactionnelle' => 0,
        'commerciale'      => 0,
        'navigationnelle'  => 0,
        'informationnelle' => 0,
    ];

    foreach (INTENT_TRANSACTIONAL as $w) {
        if (str_contains($allText, $w)) $scores['transactionnelle'] += 2;
    }
    foreach (INTENT_COMMERCIAL as $w) {
        if (str_contains($allText, $w)) $scores['commerciale'] += 2;
    }
    foreach (INTENT_NAVIGATIONAL as $w) {
        if (str_contains($allText, $w)) $scores['navigationnelle'] += 2;
    }

    // Heuristiques supplémentaires
    if (preg_match('/comment|guide|tutoriel|définition|qu.est.ce|pourquoi|explication/i', $allText)) {
        $scores['informationnelle'] += 3;
    }
    if (preg_match('/liste|top \d|meilleur|classement|comparatif/i', $allText)) {
        $scores['commerciale'] += 2;
    }

    // Si aucun signal clair, défaut = informationnelle
    $max = max($scores);
    if ($max === 0) {
        $scores['informationnelle'] = 1;
    }

    arsort($scores);
    $primary = array_key_first($scores);

    return [
        'type'  => $primary,
        'label' => ucfirst($primary),
        'score' => $scores[$primary],
    ];
}

function estimate_competition(string $keyword): array
{
    $wordCount = count(explode(' ', $keyword));
    $len = mb_strlen($keyword);

    // Plus un keyword est long/spécifique, moins il y a de concurrence
    if ($wordCount >= 4 || $len >= 30) {
        return ['level' => 'faible', 'label' => 'Faible', 'color' => '#22c55e'];
    } elseif ($wordCount >= 2 || $len >= 15) {
        return ['level' => 'moyen', 'label' => 'Moyen', 'color' => '#f97316'];
    } else {
        return ['level' => 'élevé', 'label' => 'Élevé', 'color' => '#ef4444'];
    }
}

// ─── Diagnostic SEO ────────────────────────────────────────────────────────

function generate_diagnostic(array $parsed, string $primaryKw, float $soseo = 0, float $dseo = 0): array
{
    $checks = [];
    $kwLower = mb_strtolower($primaryKw);
    $totalScore = 0;
    $maxScore = 0;

    // 1. Keyword dans le Title
    $maxScore += 15;
    $titleLower = mb_strtolower($parsed['title']);
    if (str_contains($titleLower, $kwLower)) {
        $checks[] = ['label' => 'Keyword dans le Title', 'status' => 'bon', 'message' => 'Le mot-clé principal est présent dans le titre.', 'points' => 15];
        $totalScore += 15;
    } else {
        // Vérifier si au moins un mot du keyword est présent
        $kwWords = explode(' ', $kwLower);
        $found = 0;
        foreach ($kwWords as $w) {
            if (mb_strlen($w) >= 3 && str_contains($titleLower, $w)) $found++;
        }
        if ($found > 0 && $found >= count($kwWords) / 2) {
            $checks[] = ['label' => 'Keyword dans le Title', 'status' => 'attention', 'message' => 'Le titre contient une partie du mot-clé mais pas la correspondance exacte.', 'points' => 8];
            $totalScore += 8;
        } else {
            $checks[] = ['label' => 'Keyword dans le Title', 'status' => 'mauvais', 'message' => 'Le mot-clé principal est absent du titre.', 'points' => 0];
        }
    }

    // 2. Cohérence Title ↔ H1
    $maxScore += 10;
    $h1Text = implode(' ', $parsed['h1']);
    $h1Lower = mb_strtolower($h1Text);
    if ($h1Text !== '' && $parsed['title'] !== '') {
        // Comparer les mots significatifs
        $titleTokens = tokenize($parsed['title']);
        $h1Tokens = tokenize($h1Text);
        $common = array_intersect($titleTokens, $h1Tokens);
        $ratio = count($common) / max(count($titleTokens), 1);
        if ($ratio >= 0.5) {
            $checks[] = ['label' => 'Cohérence Title ↔ H1', 'status' => 'bon', 'message' => 'Le Title et le H1 partagent un champ sémantique cohérent.', 'points' => 10];
            $totalScore += 10;
        } elseif ($ratio >= 0.25) {
            $checks[] = ['label' => 'Cohérence Title ↔ H1', 'status' => 'attention', 'message' => 'Le Title et le H1 ont peu de mots-clés en commun.', 'points' => 5];
            $totalScore += 5;
        } else {
            $checks[] = ['label' => 'Cohérence Title ↔ H1', 'status' => 'mauvais', 'message' => 'Le Title et le H1 traitent de sujets différents.', 'points' => 0];
        }
    } else {
        $checks[] = ['label' => 'Cohérence Title ↔ H1', 'status' => 'mauvais', 'message' => 'Title ou H1 manquant, impossible de vérifier la cohérence.', 'points' => 0];
    }

    // 3. Keyword dans la meta description
    $maxScore += 10;
    $metaLower = mb_strtolower($parsed['meta_description']);
    if ($parsed['meta_description'] === '') {
        $checks[] = ['label' => 'Keyword dans Meta Description', 'status' => 'mauvais', 'message' => 'Aucune meta description définie.', 'points' => 0];
    } elseif (str_contains($metaLower, $kwLower)) {
        $checks[] = ['label' => 'Keyword dans Meta Description', 'status' => 'bon', 'message' => 'Le mot-clé principal est présent dans la meta description.', 'points' => 10];
        $totalScore += 10;
    } else {
        $kwWords = explode(' ', $kwLower);
        $found = 0;
        foreach ($kwWords as $w) {
            if (mb_strlen($w) >= 3 && str_contains($metaLower, $w)) $found++;
        }
        if ($found > 0) {
            $checks[] = ['label' => 'Keyword dans Meta Description', 'status' => 'attention', 'message' => 'La meta description contient certains mots du keyword.', 'points' => 5];
            $totalScore += 5;
        } else {
            $checks[] = ['label' => 'Keyword dans Meta Description', 'status' => 'mauvais', 'message' => 'Le mot-clé est absent de la meta description.', 'points' => 0];
        }
    }

    // 4. Keyword dans l'URL
    $maxScore += 10;
    $urlText = mb_strtolower(implode(' ', $parsed['url_parts']));
    if (str_contains($urlText, $kwLower)) {
        $checks[] = ['label' => 'Keyword dans l\'URL', 'status' => 'bon', 'message' => 'Le mot-clé est présent dans l\'URL.', 'points' => 10];
        $totalScore += 10;
    } else {
        $kwWords = explode(' ', $kwLower);
        $found = 0;
        foreach ($kwWords as $w) {
            if (mb_strlen($w) >= 3 && str_contains($urlText, $w)) $found++;
        }
        if ($found > 0) {
            $checks[] = ['label' => 'Keyword dans l\'URL', 'status' => 'attention', 'message' => 'L\'URL contient une partie du mot-clé.', 'points' => 5];
            $totalScore += 5;
        } else {
            $checks[] = ['label' => 'Keyword dans l\'URL', 'status' => 'mauvais', 'message' => 'Le mot-clé est absent de l\'URL.', 'points' => 0];
        }
    }

    // 5. Richesse sémantique
    $maxScore += 10;
    $bodyWords = preg_split('/\s+/', mb_strtolower($parsed['body_text']), -1, PREG_SPLIT_NO_EMPTY);
    $uniqueWords = count(array_unique($bodyWords));
    $totalWords = count($bodyWords);
    $ratio = $totalWords > 0 ? $uniqueWords / $totalWords : 0;
    if ($ratio >= 0.4) {
        $checks[] = ['label' => 'Richesse sémantique', 'status' => 'bon', 'message' => sprintf('Bon ratio de diversité lexicale (%.0f%% mots uniques).', $ratio * 100), 'points' => 10];
        $totalScore += 10;
    } elseif ($ratio >= 0.25) {
        $checks[] = ['label' => 'Richesse sémantique', 'status' => 'attention', 'message' => sprintf('Diversité lexicale moyenne (%.0f%% mots uniques).', $ratio * 100), 'points' => 5];
        $totalScore += 5;
    } else {
        $checks[] = ['label' => 'Richesse sémantique', 'status' => 'mauvais', 'message' => sprintf('Faible diversité lexicale (%.0f%% mots uniques).', $ratio * 100), 'points' => 0];
    }

    // 6. Nombre de H1
    $maxScore += 10;
    $h1Count = count($parsed['h1']);
    if ($h1Count === 1) {
        $checks[] = ['label' => 'Unicité du H1', 'status' => 'bon', 'message' => 'Un seul H1, c\'est correct.', 'points' => 10];
        $totalScore += 10;
    } elseif ($h1Count === 0) {
        $checks[] = ['label' => 'Unicité du H1', 'status' => 'mauvais', 'message' => 'Aucun H1 trouvé sur la page.', 'points' => 0];
    } else {
        $checks[] = ['label' => 'Unicité du H1', 'status' => 'attention', 'message' => sprintf('%d balises H1 trouvées. Il devrait n\'y en avoir qu\'une seule.', $h1Count), 'points' => 4];
        $totalScore += 4;
    }

    // 7. Longueur du Title
    $maxScore += 10;
    $titleLen = mb_strlen($parsed['title']);
    if ($titleLen === 0) {
        $checks[] = ['label' => 'Longueur du Title', 'status' => 'mauvais', 'message' => 'Aucun Title défini.', 'points' => 0];
    } elseif ($titleLen <= 60 && $titleLen >= 30) {
        $checks[] = ['label' => 'Longueur du Title', 'status' => 'bon', 'message' => sprintf('Title de %d caractères (idéal : 30-60).', $titleLen), 'points' => 10];
        $totalScore += 10;
    } elseif ($titleLen <= 70) {
        $checks[] = ['label' => 'Longueur du Title', 'status' => 'attention', 'message' => sprintf('Title de %d caractères, légèrement au-dessus de la limite recommandée (60).', $titleLen), 'points' => 7];
        $totalScore += 7;
    } else {
        $checks[] = ['label' => 'Longueur du Title', 'status' => 'mauvais', 'message' => sprintf('Title de %d caractères, trop long (max recommandé : 60).', $titleLen), 'points' => 3];
        $totalScore += 3;
    }

    // 8. Longueur Meta Description
    $maxScore += 10;
    $metaLen = mb_strlen($parsed['meta_description']);
    if ($metaLen === 0) {
        $checks[] = ['label' => 'Longueur Meta Description', 'status' => 'mauvais', 'message' => 'Aucune meta description définie.', 'points' => 0];
    } elseif ($metaLen <= 160 && $metaLen >= 120) {
        $checks[] = ['label' => 'Longueur Meta Description', 'status' => 'bon', 'message' => sprintf('Meta description de %d caractères (idéal : 120-160).', $metaLen), 'points' => 10];
        $totalScore += 10;
    } elseif ($metaLen <= 170 && $metaLen >= 70) {
        $checks[] = ['label' => 'Longueur Meta Description', 'status' => 'attention', 'message' => sprintf('Meta description de %d caractères.', $metaLen), 'points' => 6];
        $totalScore += 6;
    } else {
        $checks[] = ['label' => 'Longueur Meta Description', 'status' => 'mauvais', 'message' => sprintf('Meta description de %d caractères (idéal : 120-160).', $metaLen), 'points' => 2];
        $totalScore += 2;
    }

    // 9. Keyword dans H1
    $maxScore += 15;
    if ($h1Text === '') {
        $checks[] = ['label' => 'Keyword dans le H1', 'status' => 'mauvais', 'message' => 'Pas de H1 pour vérifier la présence du keyword.', 'points' => 0];
    } elseif (str_contains($h1Lower, $kwLower)) {
        $checks[] = ['label' => 'Keyword dans le H1', 'status' => 'bon', 'message' => 'Le mot-clé principal est présent dans le H1.', 'points' => 15];
        $totalScore += 15;
    } else {
        $kwWords = explode(' ', $kwLower);
        $found = 0;
        foreach ($kwWords as $w) {
            if (mb_strlen($w) >= 3 && str_contains($h1Lower, $w)) $found++;
        }
        if ($found > 0 && $found >= count($kwWords) / 2) {
            $checks[] = ['label' => 'Keyword dans le H1', 'status' => 'attention', 'message' => 'Le H1 contient une partie du mot-clé principal.', 'points' => 8];
            $totalScore += 8;
        } else {
            $checks[] = ['label' => 'Keyword dans le H1', 'status' => 'mauvais', 'message' => 'Le mot-clé principal est absent du H1.', 'points' => 0];
        }
    }

    // 10. Optimisation sémantique (ICS)
    $maxScore += 10;
    if ($soseo >= 60) {
        $checks[] = ['label' => 'Optimisation sémantique', 'status' => 'bon', 'message' => sprintf('Bonne couverture sémantique (ICS : %.0f%%).', $soseo), 'points' => 10];
        $totalScore += 10;
    } elseif ($soseo >= 30) {
        $checks[] = ['label' => 'Optimisation sémantique', 'status' => 'attention', 'message' => sprintf('Couverture sémantique partielle (ICS : %.0f%%).', $soseo), 'points' => 5];
        $totalScore += 5;
    } else {
        $checks[] = ['label' => 'Optimisation sémantique', 'status' => 'mauvais', 'message' => sprintf('Couverture sémantique insuffisante (ICS : %.0f%%).', $soseo), 'points' => 0];
    }

    // 11. Risque de sur-optimisation (ISR)
    $maxScore += 5;
    if ($dseo < 20) {
        $checks[] = ['label' => 'Risque de sur-optimisation', 'status' => 'bon', 'message' => sprintf('Pas de sur-optimisation détectée (ISR : %.0f%%).', $dseo), 'points' => 5];
        $totalScore += 5;
    } elseif ($dseo <= 50) {
        $checks[] = ['label' => 'Risque de sur-optimisation', 'status' => 'attention', 'message' => sprintf('Risque modéré de sur-optimisation (ISR : %.0f%%).', $dseo), 'points' => 3];
        $totalScore += 3;
    } else {
        $checks[] = ['label' => 'Risque de sur-optimisation', 'status' => 'mauvais', 'message' => sprintf('Sur-optimisation détectée (ISR : %.0f%%).', $dseo), 'points' => 0];
    }

    $scorePercent = $maxScore > 0 ? round($totalScore / $maxScore * 100) : 0;

    return [
        'checks'     => $checks,
        'score'      => $totalScore,
        'max_score'  => $maxScore,
        'percentage' => $scorePercent,
    ];
}

// ─── Recommandations sémantiques ────────────────────────────────────────────

function generate_semantic_recommendations(array $keywords): array
{
    $recs = [];
    $termDetails = $keywords['term_details'] ?? [];
    $soseo = $keywords['soseo'] ?? 0;
    $dseo = $keywords['dseo'] ?? 0;

    if (empty($termDetails)) return $recs;

    $zoneLabels = [
        'title'     => 'Title',
        'h1'        => 'H1',
        'h2'        => 'H2',
        'meta_desc' => 'Meta description',
        'url'       => 'URL',
        'body'       => 'Contenu',
        'links_text' => 'Liens internes',
    ];
    $strategicZones = ['title', 'h1', 'h2', 'meta_desc'];

    // Termes sous-optimisés : recommander les zones manquantes
    $underOptimized = [];
    foreach ($termDetails as $td) {
        if ($td['status'] !== 'sous-optimisé') continue;
        $missing = array_diff($strategicZones, $td['zones']);
        if (!empty($missing)) {
            $missingLabels = array_map(fn($z) => $zoneLabels[$z], $missing);
            $underOptimized[] = [
                'term'    => $td['term'],
                'missing' => $missingLabels,
            ];
        }
    }

    if (!empty($underOptimized)) {
        $lines = [];
        foreach (array_slice($underOptimized, 0, 5) as $item) {
            $lines[] = sprintf(
                '<strong>%s</strong> — ajouter dans : %s',
                htmlspecialchars($item['term']),
                implode(', ', $item['missing'])
            );
        }
        $recs[] = [
            'type'    => 'under',
            'icon'    => 'arrow-up',
            'title'   => 'Termes à renforcer',
            'message' => 'Ces termes importants ne sont présents que dans une seule zone. Placez-les dans davantage de zones stratégiques pour améliorer votre ICS.',
            'items'   => $lines,
        ];
    }

    // Termes sur-optimisés : recommander de réduire
    $overOptimized = [];
    foreach ($termDetails as $td) {
        if ($td['status'] !== 'sur-optimisé') continue;
        $overOptimized[] = [
            'term'    => $td['term'],
            'density' => $td['density'],
        ];
    }

    if (!empty($overOptimized)) {
        $lines = [];
        foreach (array_slice($overOptimized, 0, 5) as $item) {
            $lines[] = sprintf(
                '<strong>%s</strong> — densité actuelle : %.1f%% (trop élevée)',
                htmlspecialchars($item['term']),
                $item['density']
            );
        }
        $recs[] = [
            'type'    => 'over',
            'icon'    => 'arrow-down',
            'title'   => 'Termes à réduire',
            'message' => 'Ces termes sont trop répétés dans le contenu. Remplacez certaines occurrences par des synonymes ou des formulations alternatives pour baisser votre ISR.',
            'items'   => $lines,
        ];
    }

    // Recommandation ICS globale
    if ($soseo < 30) {
        $recs[] = [
            'type'    => 'soseo',
            'icon'    => 'target',
            'title'   => 'Couverture sémantique faible',
            'message' => 'Vos termes importants sont concentrés dans peu de zones. Pour un meilleur référencement, intégrez vos mots-clés principaux dans le Title, le H1, au moins un H2 et la meta description.',
            'items'   => [],
        ];
    } elseif ($soseo < 60) {
        // Identifier les zones stratégiques les moins couvertes
        $zoneCoverage = [];
        foreach ($strategicZones as $z) $zoneCoverage[$z] = 0;
        foreach ($termDetails as $td) {
            foreach ($td['zones'] as $z) {
                if (isset($zoneCoverage[$z])) $zoneCoverage[$z]++;
            }
        }
        asort($zoneCoverage);
        $weakest = [];
        foreach ($zoneCoverage as $z => $count) {
            if ($count < count($termDetails) * 0.3) {
                $weakest[] = $zoneLabels[$z];
            }
        }
        if (!empty($weakest)) {
            $recs[] = [
                'type'    => 'soseo',
                'icon'    => 'target',
                'title'   => 'Zones stratégiques à enrichir',
                'message' => 'Les zones suivantes contiennent peu de termes importants : <strong>' . implode('</strong>, <strong>', $weakest) . '</strong>. Ajoutez-y vos mots-clés secondaires pour améliorer votre score ICS.',
                'items'   => [],
            ];
        }
    }

    // Termes optimaux pour féliciter
    $optimalCount = 0;
    foreach ($termDetails as $td) {
        if ($td['status'] === 'optimal') $optimalCount++;
    }
    if ($optimalCount > 0 && $soseo >= 60 && $dseo < 20) {
        $recs[] = [
            'type'    => 'success',
            'icon'    => 'check',
            'title'   => 'Bonne optimisation',
            'message' => sprintf('%d termes sur %d sont correctement optimisés. Votre contenu présente un bon équilibre entre couverture sémantique et naturel.', $optimalCount, count($termDetails)),
            'items'   => [],
        ];
    }

    return $recs;
}

// ─── Recommandations ───────────────────────────────────────────────────────

function generate_recommendations(array $parsed, array $diagnostic, string $primaryKw): array
{
    $recs = [];

    // Title optimisé
    $currentTitle = $parsed['title'];
    $titleLower = mb_strtolower($currentTitle);
    $kwLower = mb_strtolower($primaryKw);

    if (!str_contains($titleLower, $kwLower) || mb_strlen($currentTitle) > 60) {
        $kw = ucfirst($primaryKw);
        // Proposer un title optimisé
        if (mb_strlen($currentTitle) > 60) {
            // Raccourcir en gardant le keyword au début
            $proposed = $kw . ' — ' . mb_substr($currentTitle, 0, 55 - mb_strlen($kw));
            if (mb_strlen($proposed) > 60) {
                $proposed = mb_substr($proposed, 0, 57) . '...';
            }
        } else {
            $proposed = $kw . ' — ' . $currentTitle;
            if (mb_strlen($proposed) > 60) {
                $proposed = $kw . ' | ' . mb_substr($currentTitle, 0, 55 - mb_strlen($kw));
            }
        }
        $recs[] = [
            'type'    => 'title',
            'label'   => 'Title optimisé',
            'current' => $currentTitle,
            'proposed'=> $proposed,
            'reason'  => mb_strlen($currentTitle) > 60
                ? 'Le titre actuel dépasse 60 caractères et/ou ne contient pas le keyword principal.'
                : 'Le keyword principal devrait figurer dans le titre pour un meilleur référencement.',
        ];
    }

    // H1 optimisé
    $h1Text = implode(' ', $parsed['h1']);
    $h1Lower = mb_strtolower($h1Text);
    if ($h1Text === '' || !str_contains($h1Lower, $kwLower)) {
        $kw = ucfirst($primaryKw);
        if ($h1Text === '') {
            $proposed = $kw;
        } else {
            // Intégrer le keyword dans le H1 existant
            $proposed = $kw . ' : ' . $h1Text;
        }
        $recs[] = [
            'type'    => 'h1',
            'label'   => 'H1 optimisé',
            'current' => $h1Text ?: '(aucun)',
            'proposed'=> $proposed,
            'reason'  => $h1Text === ''
                ? 'Aucun H1 n\'est défini. Ajoutez un H1 contenant le mot-clé principal.'
                : 'Le H1 actuel ne contient pas le mot-clé principal identifié.',
        ];
    }

    // Meta description
    if ($parsed['meta_description'] === '' || !str_contains(mb_strtolower($parsed['meta_description']), $kwLower)) {
        $kw = ucfirst($primaryKw);
        if ($parsed['meta_description'] === '') {
            $proposed = "Découvrez tout sur $kwLower. Guide complet et informations essentielles pour comprendre $kwLower en détail.";
        } else {
            $proposed = $kw . ' — ' . mb_substr($parsed['meta_description'], 0, 150 - mb_strlen($kw));
        }
        if (mb_strlen($proposed) > 160) {
            $proposed = mb_substr($proposed, 0, 157) . '...';
        }
        $recs[] = [
            'type'    => 'meta_description',
            'label'   => 'Meta description optimisée',
            'current' => $parsed['meta_description'] ?: '(aucune)',
            'proposed'=> $proposed,
            'reason'  => $parsed['meta_description'] === ''
                ? 'Aucune meta description définie. Elle aide au CTR dans les résultats de recherche.'
                : 'La meta description ne mentionne pas le keyword principal.',
        ];
    }

    // Angle SEO si décalage Title/H1
    $titleTokens = tokenize($parsed['title']);
    $h1Tokens = tokenize($h1Text);
    if (!empty($titleTokens) && !empty($h1Tokens)) {
        $common = array_intersect($titleTokens, $h1Tokens);
        $ratio = count($common) / max(count($titleTokens), 1);
        if ($ratio < 0.3) {
            $recs[] = [
                'type'    => 'angle',
                'label'   => 'Angle SEO',
                'current' => 'Décalage entre Title et H1',
                'proposed'=> "Aligner le Title et le H1 autour du même mot-clé principal « $primaryKw » pour renforcer le signal sémantique.",
                'reason'  => 'Le Title et le H1 ciblent des thématiques différentes, ce qui dilue le signal SEO.',
            ];
        }
    }

    // Contenu trop court
    if ($parsed['word_count'] < 300) {
        $recs[] = [
            'type'    => 'content',
            'label'   => 'Enrichir le contenu',
            'current' => sprintf('%d mots', $parsed['word_count']),
            'proposed'=> 'Viser au minimum 300 mots pour un contenu de qualité, idéalement 800+ mots pour un article informatif.',
            'reason'  => 'Un contenu trop court limite les chances de positionnement sur des requêtes compétitives.',
        ];
    }

    return $recs;
}

// ─── Orchestrateur ─────────────────────────────────────────────────────────

function handle_seo_form(): array
{
    $data = [
        'hasPost'          => false,
        'url'              => '',
        'error'            => '',
        'parsed'           => [],
        'keywords'         => [],
        'diagnostic'       => [],
        'recommendations'  => [],
        'fetchInfo'        => [],
    ];

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_POST['url'])) {
        return $data;
    }

    $data['hasPost'] = true;
    $url = trim($_POST['url']);

    // Ajouter https:// si absent
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }
    $data['url'] = $url;

    // Vérifier URL valide
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $data['error'] = 'URL invalide. Veuillez saisir une URL complète (ex: https://example.com/page).';
        return $data;
    }

    // Quota plateforme
    if (class_exists('Platform\\Module\\Quota')) {
        if (!\Platform\Module\Quota::trackerSiDisponible('kwcible')) {
            $data['error'] = 'Quota mensuel dépassé.';
            return $data;
        }
    }

    // 1. Fetch
    $fetch = fetch_page($url);
    $data['fetchInfo'] = $fetch;

    if ($fetch['status'] !== 'ok') {
        $data['error'] = $fetch['error'] ?? 'Impossible de récupérer la page.';
        return $data;
    }

    // 2. Parse
    $parsed = parse_page($fetch['html'], $fetch['finalUrl']);
    $data['parsed'] = $parsed;

    // 3. Analyze keywords
    $keywords = analyze_keywords($parsed);
    $data['keywords'] = $keywords;

    // 4. Diagnostic
    $primaryKw = $keywords['primary_keyword'];
    $diagnostic = generate_diagnostic($parsed, $primaryKw, $keywords['soseo'] ?? 0, $keywords['dseo'] ?? 0);
    $data['diagnostic'] = $diagnostic;

    // 5. Recommendations
    $recommendations = generate_recommendations($parsed, $diagnostic, $primaryKw);
    $data['recommendations'] = $recommendations;

    // 6. Semantic recommendations
    $data['semantic_recs'] = generate_semantic_recommendations($keywords);

    return $data;
}
