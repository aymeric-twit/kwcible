# CLAUDE.md — KWCible

## Vue d'ensemble

**KWCible** est un outil d'analyse sémantique SEO qui évalue l'adéquation entre une page web et un mot-clé cible. Il récupère le contenu d'une page via Puppeteer (headless Chrome), extrait les zones SEO (title, meta, Hn, body, alt, liens) et calcule un score de pertinence avec analyse d'intention de recherche et suggestions Google Suggest.

---

## Architecture

Application **monolithique PHP + Node.js** (Puppeteer pour le rendu JavaScript).

```
kwcible/
├── index.php          # Interface HTML (formulaire URL+mot-clé + résultats)
├── functions.php      # Logique métier (parsing HTML, stemming, scoring, intent)
├── fetch-page.js      # Script Node.js Puppeteer — récupération de page
├── styles.css         # Styles CSS (charte plateforme)
├── composer.json      # Dépendance PHP (php-stemmer)
├── package.json       # Dépendances Node.js (puppeteer + stealth)
├── cache/suggest/     # Cache des réponses Google Suggest (fichiers JSON)
├── chromium-libs/     # Bibliothèques Chromium pour Puppeteer
├── module.json        # Métadonnées plugin pour la plateforme SEO
└── boot.php           # Bootstrap : autoloader Composer
```

### Flux de données

1. L'utilisateur saisit une URL + mot-clé cible + options (langue, pays)
2. `functions.php` lance `fetch-page.js` via `shell_exec()` pour récupérer le HTML rendu
3. Le HTML est parsé : extraction title, meta description, canonical, H1-H3, body text, alt images, texte liens
4. Stemming du mot-clé et du contenu via `wamania/php-stemmer`
5. Détection d'intention de recherche (transactionnelle, commerciale, navigationnelle, informationnelle)
6. Appel Google Suggest API pour enrichir l'analyse
7. Calcul d'un score de pertinence par zone SEO

---

## Fichiers clés

### `functions.php` — Logique métier

- **Stopwords** : liste FR + EN (~150 mots) dans la constante `STOPWORDS_FR`
- **Intent detection** : constantes `INTENT_TRANSACTIONAL`, `INTENT_COMMERCIAL`, `INTENT_NAVIGATIONAL`
- **`fetch_page(url)`** : exécute `fetch-page.js` via Node.js, retourne `{status, html, finalUrl, httpCode}`
- **`parse_page(html, url)`** : parsing DOM complet (title, meta, Hn, body, URL segments, alt images, liens)
- **Stemming** : `wamania/php-stemmer` pour la racinisation FR/EN
- **Cache Suggest** : fichiers JSON dans `cache/suggest/` (hash MD5 de la requête)

### `fetch-page.js` — Puppeteer headless

- **puppeteer-extra** + **stealth plugin** (anti-détection bot)
- Lance Chromium headless, navigue vers l'URL
- Gère les challenges Cloudflare (attente supplémentaire si détecté)
- Sortie JSON : `{status, html, finalUrl, httpCode}` sur stdout
- Timeout : 30s navigation + 15s Cloudflare fallback
- Headers : `Accept-Language: fr-FR,fr;q=0.9,en;q=0.8`

### `index.php` — Interface

- Bootstrap 5.3 + Poppins (CDN)
- Formulaire : URL, mot-clé cible, langue (hl), pays (gl)
- Résultats : score global, scores par zone, suggestions similaires, intent détecté

---

## Intégration plateforme

- **Display mode** : `embedded` — HTML parsé par `extractParts()`
- **Quota** : `form_submit` / 100 par défaut — auto-incrémenté par le middleware
- **boot.php** : charge `vendor/autoload.php` (php-stemmer)
- **Prérequis runtime** : Node.js doit être installé sur le serveur

---

## Dépendances

### Composer (`composer.json`)

| Package | Version | Usage |
|---------|---------|-------|
| `wamania/php-stemmer` | ^4.0 | Racinisation / stemming multilingue |

### npm (`package.json`)

| Package | Version | Usage |
|---------|---------|-------|
| `puppeteer` | ^24.37.5 | Navigateur headless Chrome |
| `puppeteer-extra` | ^3.3.6 | Plugins Puppeteer |
| `puppeteer-extra-plugin-stealth` | ^2.11.2 | Anti-détection bot |

### CDN

| Dépendance | Usage |
|-----------|-------|
| Bootstrap 5.3 | Layout, composants |
| Google Fonts Poppins | Typographie |

### Prérequis système

- **Node.js** : requis pour `fetch-page.js`
- **Chromium** : installé via Puppeteer ou bibliothèques dans `chromium-libs/`
- **PHP ext-dom** : parsing HTML
- **PHP ext-mbstring** : manipulation Unicode
- **PHP ext-curl** : appels Google Suggest

---

## Conventions

- Code en **français** (variables, fonctions, commentaires, constantes)
- Stopwords et indicateurs d'intention en FR + EN
- Cache fichier JSON pour les réponses Google Suggest
- Pattern **handler → tableau → vue** dans index.php
- Charte graphique plateforme : variables `--brand-*`
