# Notes techniques Hydroaktif Manager

## Architecture

PHP classique sans framework, PDO/MySQL, HTML/CSS, JavaScript natif. Navigation via `index.php?page=...` (aucune réécriture d'URL requise). Les actions utilisent `POST action=...`, validation CSRF et redirection HTTP 303. L'API initiale de lecture est `api.php`.

## Organisation métier

- `app/bootstrap.php` : initialisation, accès PDO, CSRF et fonctions communes.
- `app/auth.php` : authentification et déconnexion.
- `app/actions.php` : écritures transactionnelles et validation des formulaires.
- `app/views.php` : vues des modules.
- `app/layout.php` : navigation, en-tête et composants communs.
- `database/schema.php` : définition de schéma, uniquement lors de l'installation.

## Modèle physique

- `storage_locations` : hiérarchie autoréférencée de codes uniques. Les coordonnées `pos_x_mm`, `pos_y_mm` serviront au futur éditeur, mais ne sont pas éditables dans 0.1.
- `components` et `component_stocks` : séparation du catalogue de références et du stock par emplacement. `component_movements` conserve les entrées/sorties.
- `filament_products` : type de filament, commun à plusieurs bobines ; `filament_spools` : bobine physique et poids ; `filament_movements` : historisation des ajustements.
- `projects`, `equipment` : registres initiaux volontairement limités.

## Étapes recommandées

1. Construire l'éditeur SVG libre et les représentations face/dessus.
2. Introduire les API fournisseur derrière des adaptateurs PHP (`MouserProvider`, `DigiKeyProvider`) avec clés serveur et limitations d'appels.
3. Ajouter les pièces jointes et médias stockés hors du répertoire web public.
4. Ajouter les nomenclatures projets, réservations et consommations.
5. Préparer migrations idempotentes, tests d'intégration MySQL, rôles et supervision.

## Tests

Commande de contrôle syntaxique (si accès PHP CLI) :

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Scénario fonctionnel manuel : installer sur base vide, se connecter, créer Zone A, ajouter référence PLA et bobine FIL-001, ajuster le poids, créer composant avec stock dans Zone A, enregistrer une sortie, créer projet et équipement, vérifier le tableau de bord et API JSON. Toute erreur doit être consignée sans mots de passe.
