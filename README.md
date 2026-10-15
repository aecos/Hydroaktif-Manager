# Hydroaktif Manager — v0.1.0

**Gestion d'atelier électronique, impression 3D, projets et rangements.**

Version initiale compatible avec un hébergement web PHP/MySQL classique : **aucun Docker ni Node.js nécessaire**. L'interface est en français, responsive et ne charge aucune bibliothèque distante.

## Prérequis

- PHP **8.2 minimum** ; recommandé et ciblé : **PHP 8.3**.
- Extensions PHP : `pdo_mysql`, `mbstring`, `json`, `openssl`.
- Serveur MySQL **5.7+** ou MariaDB compatible InnoDB et `utf8mb4`.
- Accès HTTPS, FTP/SFTP ou gestionnaire de fichiers, base MySQL déjà créée et vide.
- Apache avec `.htaccess` **ou** serveur Nginx configuré pour interdire l'accès aux répertoires privés.
- Droits en écriture sur `config/` **pendant l'installation seulement**.

## Installation en 6 étapes

1. **Sauvegarder** les éventuelles données de l'hébergement avant toute manipulation. Préférer un sous-domaine ou sous-répertoire dédié, vide.
2. Créer une **base MySQL vide** et un utilisateur disposant de `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `ALTER` et `INDEX` sur cette base. Les tables sont créées par l'installateur.
3. Décompresser l'archive et envoyer **le contenu du dossier `hydroaktif-manager`** dans le répertoire web souhaité (`public_html/`, sous-dossier, etc.). Ne pas envoyer uniquement le ZIP si l'hébergeur ne sait pas l'extraire.
4. Ouvrir `https://ton-domaine.tld/install.php`, contrôler les prérequis, entrer les identifiants MySQL et créer le premier compte administrateur avec un mot de passe de 12 caractères minimum.
5. Se connecter sur `https://ton-domaine.tld/index.php?page=login`.
6. Après installation, **retirer les droits d'écriture superflus sur `config/`**. Vérifier que les URL `/config/`, `/app/`, `/database/`, `/storage/` renvoient bien un refus d'accès. Pour plus de sûreté, supprimer `install.php` une fois l'installation terminée (il est déjà bloqué logiquement par le fichier de verrouillage).

> Si l'application est placée dans un sous-dossier, remplace `https://ton-domaine.tld/` par `https://ton-domaine.tld/sous-dossier/`. Les liens applicatifs sont relatifs.

### Apache versus Nginx

Les fichiers `.htaccess` fournis restreignent l'accès aux répertoires `app`, `config`, `database`, `storage` et `docs` sous Apache. Si tu utilises **Nginx**, les règles `.htaccess` n'ont aucun effet : configure une restriction HTTP explicite sur ces chemins **avant** de mettre l'application en ligne. Exemples de blocs Nginx à adapter à la configuration de l'hébergeur :

```nginx
location ~ ^/(app|config|database|storage|docs)(/|$) { deny all; }
location ~ /\. { deny all; }
```

Pour une installation dans un sous-répertoire, il faut adapter les préfixes des chemins.

## Modules effectivement disponibles dans cette version

| Module | Fonctionnel dès 0.1.0 |
|---|---|
| Connexion | Administrateur initial, session, déconnexion, CSRF, tentatives limitées |
| Tableau de bord | Statistiques calculées sur la base réelle et alertes filaments |
| Impression 3D | Références de filament, bobines, pesées / ajustements, historique en base |
| Électronique | Création de composants, MPN, fabricant, URL de datasheet, entrées/sorties par emplacement |
| Projets | Création, description, budget prévisionnel, changements de statut |
| Rangements | Arborescence d'emplacements avec dimensions et codes uniques |
| Équipements | Inventaire des machines et outils |
| Administration | Nom de l'atelier, affichage des informations serveur |
| API JSON | `api.php?resource=summary`, `locations`, `components`, `spools` (authentifiée, lecture seule) |

## Prévu dans les itérations suivantes — NON inclus dans 0.1.0

- Éditeur SVG libre : glisser-déposer, redimensionnement, vue meuble / tiroir / compartiment.
- Intégrations Mouser et DigiKey (recherche et import automatique) ; photographies, PDF, fichiers 3D.
- Gestion avancée des projets : nomenclatures, tâches, réservations, consommation et fichiers STL/STEP.
- Import/export CSV, QR codes, bibliothèque en ligne de filaments, intégration des imprimantes / AMS.
- Gestion multi-utilisateurs, audit avancé, sauvegardes guidées, migrations et mises à jour automatisées.
- ESP32 et LED de localisation.

> Il s'agit d'un **socle initial**, et non d'une V1 métier terminée. Tester sur une installation de préproduction avant une mise en production réelle.

## API JSON (lecture seule)

Après connexion dans le navigateur :

```
GET /api.php?resource=summary
GET /api.php?resource=locations
GET /api.php?resource=components
GET /api.php?resource=spools
```

Les ressources renvoient `{"data": ...}`. Elles nécessitent la session authentifiée. L'API publique / REST complète sera étoffée lors des prochaines versions.

## Sécurité, sauvegardes et limites

- Les paramètres MySQL sont stockés dans `config/database.php` et **ne doivent jamais être diffusés publiquement**.
- Les formulaires vérifient le jeton CSRF et utilisent des requêtes préparées.
- L'installation est verrouillée par `config/installed.lock` après réussite.
- Les fichiers de l'application n'étant pas des fichiers publics ordinaires, **protéger les répertoires privés** sur le serveur.
- HTTPS est fortement recommandé et indispensable si l'interface est utilisée en dehors d'un réseau isolé.
- Les sauvegardes ne sont **pas automatisées** dans cette version. Sauvegarder régulièrement la base MySQL (export SQL depuis l'hébergeur) et les fichiers applicatifs/configuration, dans un emplacement sécurisé.
- Les moteurs PHP/MySQL peuvent imposer des contraintes d'hébergement (permissions, mode SQL, extensions PHP), à confirmer sur l'hébergeur cible.
- La création manuelle de stock de composants nécessite de créer d'abord un emplacement dans le module Rangements.
- Les listes sont plafonnées à 200 ou 500 lignes sur ce socle : une pagination complète arrivera ensuite.

## Arborescence

```text
hydroaktif-manager/
├── index.php              Interface, routes d'actions
├── install.php            Assistant de première installation
├── api.php                API JSON en lecture seule
├── assets/css/app.css     Design et responsive
├── assets/js/app.js       Menu mobile, filtre des bobines
├── app/                   Métier, vues, connexion
├── database/schema.php    Création des tables
├── config/                Configuration créée par l'installateur
├── storage/               Répertoire privé pour extensions futures
└── docs/                  Notes techniques
```

**Nom de projet : Hydroaktif Manager.** Licence de ce code généré : utilisation et modification libres pour ton propre atelier.
