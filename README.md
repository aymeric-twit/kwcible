# KWCible

> **Semantic SEO analysis tool** — evaluates how well a web page matches a target keyword using JS-rendered page fetching (Puppeteer + stealth), per-zone relevance scoring, search intent detection, and Google Suggest enrichment.

---

## Description

**KWCible** est un outil d'analyse semantique SEO qui evalue l'adequation entre une page web et un mot-cle cible. Il repond a la question centrale du SEO on-page : *"Cette page est-elle bien optimisee pour cette requete ?"*

L'outil recupere le contenu rendu d'une page via **Puppeteer headless** (avec plugin stealth anti-detection), extrait les zones SEO strategiques, et calcule un score de pertinence global accompagne de recommandations actionnables.

- **Slug** : `kwcible`
- **Version** : 1.0.0
- **Mode d'affichage** : `embedded`
- **Quota** : `form_submit` — 100 analyses / mois

---

## Fonctionnalites

### Recuperation de page via Puppeteer

- Rendu JavaScript complet grace a Chromium headless (pages SPA, React, Vue, etc.)
- Plugin **stealth** (`puppeteer-extra-plugin-stealth`) pour contourner la detection de bots
- Gestion automatique des **challenges Cloudflare** (attente supplementaire si detecte)
- Timeout : 30s navigation + 15s fallback Cloudflare
- Headers `Accept-Language: fr-FR,fr;q=0.9,en;q=0.8`

### Extraction des zones SEO

- **Title** : balise `<title>` avec controle de longueur (seuil 60 caracteres)
- **Meta description** : contenu et longueur (seuil 160 caracteres)
- **Canonical** : detection de la balise `<link rel="canonical">`
- **H1, H2, H3** : extraction hierarchique complete
- **Body** : texte visible du corps de page avec comptage de mots
- **Attributs alt** : textes alternatifs des images
- **Texte des liens** : ancres des liens internes et externes
- **Segments URL** : decomposition du chemin de l'URL

### Analyse semantique

- **Stemming multilingue** (FR/EN) via `wamania/php-stemmer` — racinisation des termes pour comparer le mot-cle et le contenu
- **150+ stopwords** francais et anglais filtres automatiquement
- **Indice de Couverture Semantique (ICS)** : mesure la presence des termes importants dans les zones strategiques (Title, H1, H2, Meta, URL, Body)
- **Indice de Sur-Repetition (ISR)** : detecte le keyword stuffing en mesurant la densite excessive
- **Score par terme** : scoring individuel avec statut (optimal / sous-optimise / sur-optimise)
- **Densite par zone** : pourcentage d'occurrence de chaque terme cle
- **Treemap interactive** : visualisation proportionnelle des termes avec code couleur par statut

### Detection d'intention de recherche

Classification automatique du mot-cle en 4 categories :

- **Transactionnelle** : achat, commande, prix, devis...
- **Commerciale** : comparatif, meilleur, avis, test...
- **Navigationnelle** : marque, site, login, connexion...
- **Informationnelle** : comment, pourquoi, guide, tutoriel...

### Enrichissement Google Suggest

- Interrogation de l'API Google Suggest pour valider/corriger l'ordre des mots du mot-cle
- **Cache fichier JSON** (`cache/suggest/`) pour eviter les appels redondants
- Badge visuel : "SUGGEST VALIDE" ou "SUGGEST CORRIGE" avec indication du mot-cle original

### Diagnostic et recommandations

- **Score SEO global** sur 100 avec jauge visuelle (bon / amelioration necessaire / insuffisant)
- **Checks detailles** : presence du mot-cle dans le title, H1, meta, URL, densite body, etc.
- **Recommandations actionnables** : comparaison actuel vs propose pour chaque element (title, meta description, H1)
- **Recommandations semantiques** : termes sous-optimises a renforcer, termes sur-optimises a reduire

---

## Prerequis

### PHP

- **PHP 8.3+** avec les extensions :
  - `ext-dom` — parsing HTML
  - `ext-mbstring` — manipulation Unicode
  - `ext-curl` — appels Google Suggest
  - `ext-json` — encodage/decodage JSON
- **Composer** — gestionnaire de dependances PHP

### Node.js

- **Node.js 18+** — requis pour le script Puppeteer (`fetch-page.js`)
- **npm** — gestionnaire de paquets Node.js
- **Chromium** — installe automatiquement par Puppeteer, ou disponible sur le systeme

> **Note** : Puppeteer telecharge sa propre version de Chromium lors du `npm install`. Sur certains serveurs, des bibliotheques systeme supplementaires peuvent etre necessaires (voir `chromium-libs/`).

---

## Installation

```bash
# Cloner ou copier le plugin dans le repertoire des projets
cd /home/aymeric/projects/kwcible/

# Installer les dependances PHP (php-stemmer)
composer install

# Installer les dependances Node.js (Puppeteer + stealth)
npm install
```

### Verification

```bash
# Verifier que Node.js est disponible
node --version   # v18+ requis

# Verifier que Puppeteer fonctionne
node fetch-page.js "https://example.com"

# Lancer en mode standalone
php -S localhost:8080
```

Ouvrir `http://localhost:8080` dans le navigateur.

### Integration plateforme

Le plugin fonctionne en mode `embedded` dans la plateforme SEO. L'installation se fait via l'interface d'administration (Admin > Plugins > Installer via Git). Le `boot.php` charge automatiquement l'autoloader Composer.

---

## Utilisation

1. **Saisir l'URL** d'une page web publique dans le formulaire
2. **Cliquer sur "Analyser"** — l'outil recupere la page via Puppeteer, extrait les zones SEO et identifie la requete cle principale
3. **Consulter les resultats** :
   - **Structure de la page** : title, meta, Hn, canonical, nombre de mots, segments URL avec indicateurs de statut
   - **Requete cle principale** : mot-cle identifie, intention de recherche, niveau de concurrence, variantes secondaires
   - **Optimisation semantique** : jauges ICS/ISR, treemap des termes, tableau detaille par terme (score, zones, densite, statut)
   - **Recommandations semantiques** : actions a mener sur les termes sous-optimises ou sur-optimises
   - **Diagnostic SEO** : score global sur 100 avec liste des checks (bon / attention / mauvais)
   - **Recommandations** : propositions concretes actuel vs propose pour le title, la meta description, le H1

---

## Stack technique

| Composant | Technologie | Role |
|-----------|-------------|------|
| Backend | PHP 8.3 | Parsing HTML, stemming, scoring, intent |
| Rendu JS | Node.js + Puppeteer | Recuperation de pages avec rendu JavaScript |
| Anti-detection | puppeteer-extra-plugin-stealth | Contournement des protections anti-bot |
| Stemming | wamania/php-stemmer ^4.0 | Racinisation FR/EN |
| Frontend | Bootstrap 5.3 + Poppins | Interface responsive |
| Visualisation | Treemap squarified (JS natif) | Representation proportionnelle des termes |
| Cache | Fichiers JSON | Reponses Google Suggest |

### Dependances Composer

| Package | Version | Usage |
|---------|---------|-------|
| `wamania/php-stemmer` | ^4.0 | Racinisation multilingue (francais, anglais) |

### Dependances npm

| Package | Version | Usage |
|---------|---------|-------|
| `puppeteer` | ^24.37.5 | Navigateur headless Chromium |
| `puppeteer-extra` | ^3.3.6 | Systeme de plugins Puppeteer |
| `puppeteer-extra-plugin-stealth` | ^2.11.2 | Anti-detection bot (evasion fingerprinting) |

---

## Structure du projet

```
kwcible/
├── module.json          # Metadonnees plugin (slug, quota, display_mode)
├── boot.php             # Bootstrap : charge vendor/autoload.php
├── index.php            # Interface HTML : formulaire + affichage des resultats
├── functions.php        # Logique metier : parsing, stemming, scoring, intent, Suggest
├── fetch-page.js        # Script Node.js Puppeteer avec stealth plugin
├── styles.css           # Styles CSS (charte plateforme, treemap, jauges, zones)
├── composer.json        # Dependance PHP : wamania/php-stemmer ^4.0
├── package.json         # Dependances Node.js : puppeteer + stealth
├── cache/
│   └── suggest/         # Cache JSON des reponses Google Suggest
└── .gitignore           # Exclut vendor/, node_modules/, cache/, .env
```

### Description des fichiers

| Fichier | Role |
|---------|------|
| `index.php` | Point d'entree — formulaire URL + resultats (structure page, requete cle, optimisation semantique, diagnostic, recommandations) |
| `functions.php` | Coeur metier — stopwords FR/EN, detection d'intention, `fetch_page()` via Node.js, `parse_page()` extraction DOM, stemming, scoring par zone, cache Suggest |
| `fetch-page.js` | Recuperation de page via Puppeteer headless avec stealth — gestion Cloudflare, sortie JSON sur stdout |
| `styles.css` | Charte graphique plateforme (`--brand-*`, `--seo-good/warn/bad`), composants treemap, jauges SVG, zone tags, recommandations |
| `boot.php` | Charge l'autoloader Composer pour `php-stemmer` |
| `module.json` | Declaration plugin : `slug: kwcible`, `display_mode: embedded`, `quota_mode: form_submit`, `default_quota: 100` |
