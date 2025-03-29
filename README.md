# AntiBot PHP

Un script simple et efficace pour détecter et bloquer les bots, crawlers et outils de scraping automatisés.

## Caractéristiques

- Détection des bots via plusieurs méthodes (user-agent, en-têtes HTTP, empreinte navigateur)
- Contrôle du taux de requêtes
- Vérification de la cohérence des sessions
- Prise en compte des changements d'IP
- Whitelisting des bots légitimes (Google, Bing, etc.)
- Logging des tentatives de bots
- Redirection automatique des bots (configurable)

## Installation

1. Téléchargez le fichier `antibot.php` et placez-le dans votre projet
2. Incluez-le au début de vos pages PHP

```php
<?php
include 'antibot.php';
// Votre code ici - s'exécutera uniquement pour les visiteurs humains
?>
```

## Configuration

Modifiez les paramètres au début du fichier selon vos besoins :

```php
// Configuration
$redirect_bot = "https://www.google.com"; // Redirection des bots
$redirect_human = ""; // Laissez vide pour continuer normalement, ou définissez URL
```

### Options avancées

Vous pouvez personnaliser d'autres options en modifiant la classe :

```php
// Ajouter des IPs à la whitelist
$custom_config = [
    'whitelist_ips' => ['123.45.67.89', '98.76.54.32'],
    'log_file' => 'mon_fichier_log.log'
];

$anti_bot = new AntiBotProtection($custom_config);
$is_bot = $anti_bot->check();

// Vos actions personnalisées selon le résultat
```

## Comment ça fonctionne

Le script attribue un "score de suspicion" à chaque visiteur en fonction de plusieurs critères :

1. **User-Agent** - Détecte les signatures courantes de bots ou les UA anormaux
2. **En-têtes HTTP** - Vérifie les en-têtes manquants typiques des bots
3. **Taux de requêtes** - Identifie les visiteurs qui font trop de requêtes trop rapidement
4. **Empreinte de navigateur** - Détecte les changements suspects dans les caractéristiques du navigateur
5. **Cohérence de session** - Vérifie si l'IP change fréquemment ou si le comportement est anormal

Si le score dépasse un certain seuil, le visiteur est considéré comme un bot et redirigé selon la configuration.

## Avantages

- **Simple d'utilisation** - Un seul include suffit
- **Non intrusif** - Aucun impact sur l'expérience des utilisateurs légitimes
- **Léger** - Aucune dépendance externe
- **Efficace** - Combine plusieurs méthodes de détection
- **Personnalisable** - Facile à adapter à vos besoins spécifiques

## Limitations

Ce script n'arrêtera pas tous les bots sophistiqués ou déterminés, mais il constitue une barrière efficace contre la majorité des tentatives automatisées d'accès à votre contenu.

## Licence

Ce code est fourni "tel quel", sans garantie. Vous êtes libre de l'utiliser et de le modifier selon vos besoins.
