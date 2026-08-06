# NCache

![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)

NCache est une bibliothèque de mise en cache légère pour PHP. Elle fournit une interface simple et extensible pour stocker et récupérer des données en cache, avec la possibilité d'utiliser plusieurs drivers (mémoire, fichiers, Redis, SQLite, etc.) selon vos besoins.

> Remarque : Ce README a été rédigé d'après l'analyse du dépôt. Il décrit l'API publique, les drivers inclus et les dépendances observées. Adaptez les exemples et la configuration aux besoins spécifiques.

## Caractéristiques

- API simple pour set/get/delete/clear
- Support pour plusieurs drivers (Array, Filesystem, Redis, Sqlite, Serialize, ...)
- TTL (time-to-live) configurable par clé
- Namespaces / préfixes pour éviter les collisions de clés
- Registre interne des entrées (métadonnées, signature)
- Extensible : possibilité d'ajouter de nouveaux drivers

## Prérequis

- PHP >= 8.1
- Selon le driver utilisé, certaines extensions PHP peuvent être nécessaires :
  - Redis : ext-redis (phpredis)
  - SQLite : pdo_sqlite ou sqlite3
  - Memcached : ext-memcached

## Installation

Avec Composer (si le paquet est publié sur Packagist) :

```bash
composer require noga-ng/ncache
```

Depuis le dépôt :

```bash
git clone https://github.com/Noga-ng/NCache.git
cd NCache
composer install
```

## Utilisation basique

Exemple générique en PHP :

```php
<?php
require 'vendor/autoload.php';

use NCache\NCache;
use NCache\Enum\CType;

// Créer un cache sur une clé
$cache = NCache::key('ma_cle', CType::STRING)
    ->ttl(3600) // TTL en secondes
    ->set('ma valeur');

// Persister en utilisant le driver configuré
$cache->put();

// Récupérer
$value = NCache::key('ma_cle', CType::STRING)->get();

// Supprimer
NCache::key('ma_cle', CType::STRING)->delete();

// Vider tout le cache d'un type / répertoire
NCache::clear(CType::STRING, 'mon_dir');
```

Remarques : adaptez CType selon le type attendu (STRING, JSON, etc.). Consultez Src/Enum pour les valeurs exactes.

## Configuration

La configuration centrale est gérée par Src/Config/CacheConfig.php. Elle permet de définir :

- Le dossier de base pour les fichiers de cache
- Le comportement GC (selon implémentation)
- Paramètres spécifiques aux drivers (chemins, hôtes, ports)

Exemple de configuration (fichier `config.php`) :

```php
return [
    'base_path' => __DIR__ . '/cache',
    'default_ttl' => 3600,
    'drivers' => [
        'redis' => [ 'host' => '127.0.0.1', 'port' => 6379 ],
        'filesystem' => [ 'path' => __DIR__ . '/cache' ],
    ],
];
```

## Drivers inclus

Le dépôt contient plusieurs implémentations de drivers (Src/Driver) :

- JsonCache : persistance JSON en fichier
- SerializeCache : sérialisation PHP
- SqliteCache : stockage dans une base SQLite
- RedisCache : driver pour ext-redis (attention : certaines méthodes semblent à implémenter)
- MemCached : driver pour memcached
- StringCache : driver spécialisé pour valeurs string

Note : certains drivers (ex. RedisCache) contiennent des méthodes avec des retours factices dans le code actuel. Vérifiez l'implémentation avant utilisation en production.

## Tests

Le projet inclut une configuration PHPUnit (phpunit.xml). Pour exécuter les tests :

```bash
composer install --dev
composer run phpunit
# ou
./vendor/bin/phpunit -c phpunit.xml
```

## Analyse statique

Les règles pour l'analyse statique sont fournies (phpstan). Exécution :

```bash
composer run analyse
```

## Contribution

Contributions bienvenues. Processus suggéré :

1. Forkez le dépôt
2. Créez une branche : `git checkout -b feature/nom`
3. Ajoutez des tests et respectez les règles de codage
4. Ouvrez une Pull Request

Si vous corrigez ou implémentez un driver, merci d'ajouter des tests unitaires couvrant les cas principaux (save/get/delete/clear/exists).

## Roadmap / Améliorations proposées

- Compléter les drivers inachevés (ex: RedisCache)
- Fournir un adaptateur PSR-16/PSR-6
- Ajouter métriques de hit/miss
- Documenter les options de configuration et les exemples d'intégration

## Licence

Ce projet est distribué sous la licence MIT. Voir le fichier LICENSE pour plus de détails.

## Auteur

Noga-ng — https://github.com/Noga-ng
