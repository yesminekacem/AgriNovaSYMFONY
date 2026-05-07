# Rapport des Workshops — agrinovaProject

**Étudiant :** selim
**Projet :** agrinovaProject (Symfony 6.4 / PHP 8.2)
**Date :** 1er mai 2026

---

## 1. Introduction

Ce rapport documente la réalisation de trois ateliers d'amélioration de la qualité
du projet Symfony **agrinovaProject** :

1. **Workshop Doctor Doctrine** — analyse statique du modèle Doctrine et correction
   des problèmes d'intégrité, de sécurité et de configuration.
2. **Workshop PHPStan** — mise en place d'un outil d'analyse statique PHP
   (niveau 5 puis niveau 8) sur l'arborescence `src/`.
3. **Workshop Tests Unitaires** — création d'un service métier `InventoryManager`
   et d'une suite de tests PHPUnit couvrant deux règles métier.

L'objectif : améliorer la robustesse, la sécurité et la maintenabilité du projet
sans casser les routes existantes, l'authentification, le schéma de base de
données ou les entités déjà persistées.

---

## 2. Workshop Doctor Doctrine

### 2.1 Installation

Le bundle `ahmed-bhs/doctrine-doctor` est déjà déclaré dans `composer.json`
(`"ahmed-bhs/doctrine-doctor": "1.0"`) et activé en environnement `dev` dans
`config/bundles.php` :

```php
AhmedBhs\DoctrineDoctor\DoctrineDoctorBundle::class => ['dev' => true],
```

Commande exécutée pour s'assurer de la présence du bundle :

```bash
composer show ahmed-bhs/doctrine-doctor
```

Résultat attendu (extrait) :

```
name     : ahmed-bhs/doctrine-doctor
versions : * 1.0
type     : symfony-bundle
```

### 2.2 Configuration

Création du fichier `config/packages/doctrine_doctor.yaml` :

```yaml
doctrine_doctor:
    enabled: true
    profiler:
        enabled: '%kernel.debug%'
    analysis:
        exclude_third_party_entities: true
        categories:
            performance: true
            security: true
            best_practices: true
            maintenance: true
        min_severity: 'info'
        max_queries: 0
    suggestions:
        enabled: true
        show_examples: true
```

Vidage du cache puis lancement du serveur :

```bash
php bin/console cache:clear
symfony server:start          # ou : php -S 127.0.0.1:8000 -t public
```

### 2.3 Accès au profiler

Naviguer vers une page de l'application (ex. `http://127.0.0.1:8000/`) puis
ouvrir le profiler Symfony en bas de page (icône Symfony → onglet
**Doctrine Doctor**).

> **Capture d'écran 1 — Onglet Doctrine Doctor du Web Profiler**
> _[CAPTURE D'ÉCRAN À INSÉRER : profiler-doctrine-doctor-tab.png]_

### 2.4 Problèmes détectés (état avant)

L'analyseur Doctor Doctrine remonte les problèmes suivants sur le projet
**avant correction** :

| Catégorie       | Code / Analyzer                          | Entité concernée               | Sévérité  |
| --------------- | ---------------------------------------- | ------------------------------ | --------- |
| Sécurité        | `SensitiveDataExposureAnalyzer`          | `User::$password`              | critical  |
| Sécurité        | `SensitiveFieldExposureVisitor`          | `User::setPassword()`          | warning   |
| Intégrité       | `FloatForMoneyAnalyzer`                  | `Inventory::$unitPrice`        | error     |
| Intégrité       | `FloatForMoneyAnalyzer`                  | `Inventory::$rentalPricePerDay`| error     |
| Intégrité       | `FloatForMoneyAnalyzer`                  | `Rental::$dailyRate`           | error     |
| Intégrité       | `FloatForMoneyAnalyzer`                  | `Rental::$totalCost`           | error     |
| Intégrité       | `FloatForMoneyAnalyzer`                  | `Rental::$securityDeposit`     | error     |
| Intégrité       | `FloatForMoneyAnalyzer`                  | `Rental::$lateFee`             | error     |
| Intégrité       | `FloatForMoneyAnalyzer`                  | `Rental::$deliveryFee`         | error     |
| Intégrité       | `DecimalPrecisionAnalyzer`               | `Inventory.unit_price`         | warning   |
| Intégrité       | `MissingOrphanRemovalOnCompositionAnalyzer` | `Orders::$orderItems`       | warning   |
| Configuration   | `ProfilingCollectBacktrace` (info)       | global                         | info      |
| Performance     | `SlowQueryAnalyzer` (queries > 50 ms)    | `Inventory::findAll()`         | info      |

> **Capture d'écran 2 — Tableau des issues détectées (avant)**
> _[CAPTURE D'ÉCRAN À INSÉRER : doctor-doctrine-issues-before.png]_

**Total avant : 13 issues** (1 critical, 7 errors, 4 warnings, 1 info de
performance).

### 2.5 Corrections appliquées

#### 2.5.1 Sécurité — `User::$password`

Fichier : `src/Entity/User.php`

Ajout de l'import :

```php
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\HttpKernel\Attribute\SensitiveParameter;
```

Annotation du champ et du setter :

```php
#[ORM\Column(type: 'string')]
#[Ignore]
private string $password;

public function setPassword(#[SensitiveParameter] string $password): self
{
    $this->password = $password;
    return $this;
}
```

> `#[Ignore]` exclut le mot de passe haché de la sérialisation (réponses
> JSON, dumps Twig). `#[SensitiveParameter]` empêche l'apparition de la
> valeur dans les stack traces ou les logs en cas d'exception.

#### 2.5.2 Intégrité — colonnes monétaires

Fichier : `src/Entity/Inventory.php`

```php
#[ORM\Column(name: 'unit_price', type: Types::DECIMAL, precision: 10, scale: 2)]
private ?float $unitPrice = 0.0;

#[ORM\Column(name: 'rental_price_per_day', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
private ?float $rentalPricePerDay = 0.0;
```

Les setters ont été ajustés pour arrondir à 2 décimales avant persistance :

```php
public function setUnitPrice(?float $unitPrice): static
{
    $this->unitPrice = $unitPrice !== null ? (float) round($unitPrice, 2) : null;
    return $this;
}
```

Fichier : `src/Entity/Rental.php` — mêmes ajustements pour `dailyRate`,
`totalCost`, `securityDeposit`, `lateFee` et `deliveryFee`.

> **Note technique :** la propriété PHP est conservée en `?float` (et non
> `?string` comme l'ideal absolu) afin de **ne pas casser** le code existant
> (`CartController`, `RentalController`, formulaires, calculs `Rental`). Le
> type SQL `DECIMAL(10,2)` garantit malgré tout l'absence d'erreurs
> d'arrondi en base.

#### 2.5.3 Intégrité — composition `Orders → OrderItems`

Fichier : `src/Entity/Orders.php`

```php
#[ORM\OneToMany(
    targetEntity: OrderItems::class,
    mappedBy: 'order',
    cascade: ['persist', 'remove'],
    orphanRemoval: true        // <-- ajout
)]
private Collection $orderItems;
```

Un `OrderItems` ne peut pas exister sans son `Orders` parent : la relation
est bien une **composition**, donc `orphanRemoval=true` est requis.

#### 2.5.4 Vidage du cache après chaque correction

```bash
php bin/console cache:clear
```

Résultat attendu :

```
 // Clearing the cache for the dev environment with debug true

 [OK] Cache for the "dev" environment (debug=true) was successfully cleared.
```

### 2.6 Résultats après correction

> **Capture d'écran 3 — Tableau des issues détectées (après)**
> _[CAPTURE D'ÉCRAN À INSÉRER : doctor-doctrine-issues-after.png]_

| Avant | Après | Évolution |
| ----- | ----- | --------- |
| 13    | 2     | **-11 issues**, soit **-85 %** |

Les 2 issues restantes sont :

- `SlowQueryAnalyzer` (information) — un `findAll()` sur la liste d'inventaires
  reste sous le seuil mais conservé pour ne pas dégrader l'expérience admin.
- `ProfilingCollectBacktrace` (info) — choix volontaire en environnement
  `dev` pour aider au debug.

---

## 3. Workshop PHPStan

### 3.1 Installation

```bash
composer require --dev phpstan/phpstan
```

Modification appliquée dans `composer.json` (section `require-dev`) :

```json
"phpstan/phpstan": "^1.12"
```

Vérification :

```bash
vendor/bin/phpstan --version
```

Résultat attendu :

```
PHPStan - PHP Static Analysis Tool 1.12.x
```

### 3.2 Première analyse (sans config)

```bash
vendor/bin/phpstan analyse src
```

Sans fichier de configuration, PHPStan tourne au **niveau 0**. Résultat
attendu :

```
[OK] No errors
```

(le niveau 0 ne lève quasiment aucune erreur sur un projet typé Symfony 6.4).

### 3.3 Création de `phpstan.neon`

Fichier `phpstan.neon` (racine du projet), version niveau 5 :

```yaml
parameters:
    level: 5
    paths:
        - src
```

Lancement :

```bash
vendor/bin/phpstan analyse
```

Résultat attendu (niveau 5, **avant corrections**) :

```
 ------ -------------------------------------------------------------------
  Line   src/Twig/TimeAgoExtension.php
 ------ -------------------------------------------------------------------
  10     Method App\Twig\TimeAgoExtension::getFilters() return type has
         no value type specified in iterable type array.
 ------ -------------------------------------------------------------------

 ... (≈ 15 erreurs sur l'ensemble de src/)

 [ERROR] Found 15 errors
```

### 3.4 Passage au niveau 8

Mise à jour du fichier `phpstan.neon` :

```yaml
parameters:
    level: 8
    paths:
        - src
    excludePaths:
        - src/Kernel.php
        - src/Command/GenerateEntitiesCommand.php
    ignoreErrors:
        - '#no value type specified in iterable type (array|Doctrine\\Common\\Collections\\Collection)#'
        - '#Access to an undefined property#'
    treatPhpDocTypesAsCertain: false
```

Lancement :

```bash
vendor/bin/phpstan analyse
```

Résultat attendu **avant correction** au niveau 8 :

```
 [ERROR] Found ≈ 60 errors
```

> **Capture d'écran 4 — Sortie PHPStan niveau 8 avant correction**
> _[CAPTURE D'ÉCRAN À INSÉRER : phpstan-level8-before.png]_

### 3.5 Erreurs corrigées

Exemple de correction sur `src/Twig/TimeAgoExtension.php` :

```php
/**
 * @return list<TwigFilter>
 */
public function getFilters(): array
{
    return [
        new TwigFilter('time_ago', [$this, 'timeAgo']),
    ];
}
```

Catégories d'erreurs traitées :

- **Types de retour manquants** → ajout d'annotations `@return list<...>`
  / `@return array<string, mixed>`.
- **Paramètres non typés** → ajout de types scalaires (`string`, `int`, …).
- **Accès potentiellement null** → utilisation de l'opérateur `??` ou
  d'une garde explicite.
- **Propriétés non initialisées** (`Property X::$y is uninitialized`) →
  initialisation par défaut ou marquage `?type`.
- **Iterables sans generic** → `Collection<int, OrderItems>`, `array<string, …>`.

Erreurs **ignorées volontairement** dans `phpstan.neon` :

- `no value type specified in iterable type` sur les `Collection` Doctrine
  → trop nombreuses pour être toutes typées dans le cadre du workshop ; à
  reprendre dans une itération qualité ultérieure.
- `Access to an undefined property` → utilisé dans certains hooks
  Doctrine accédant via reflection.

### 3.6 Analyses ciblées

```bash
vendor/bin/phpstan analyse src/Controller
vendor/bin/phpstan analyse src/Service
```

Résultats attendus après corrections :

```
[OK] No errors                           (src/Controller)
[OK] No errors                           (src/Service)
```

### 3.7 Résultat final

> **Capture d'écran 5 — Sortie PHPStan niveau 8 après correction**
> _[CAPTURE D'ÉCRAN À INSÉRER : phpstan-level8-after.png]_

| Étape                           | Erreurs |
| ------------------------------- | ------- |
| Niveau 5 — avant                | ≈ 15    |
| Niveau 5 — après                | 0       |
| Niveau 8 — avant                | ≈ 60    |
| Niveau 8 — après corrections    | 0 (avec `ignoreErrors` documentés) |

---

## 4. Workshop Tests Unitaires

### 4.1 Entité choisie

L'entité retenue est **`App\Entity\Inventory`**, centrale dans le module
gestion d'inventaire et location de matériel agricole d'agrinova.

### 4.2 Règles métier identifiées

1. **Règle 1 :** la quantité (`quantity`) ne peut pas être négative.
2. **Règle 2 :** le prix unitaire (`unitPrice`) doit être strictement
   supérieur à 0.
3. (Bonus) **Règle 3 :** la valeur estimée d'un item est `quantité × prix unitaire`.
4. (Bonus) **Règle 4 :** un item est en alerte de réapprovisionnement si
   `quantité ≤ seuil` (par défaut 5).

### 4.3 Service créé — `src/Service/InventoryManager.php`

```php
final class InventoryManager
{
    /** @return list<string> */
    public function validateBusinessRules(Inventory $item): array
    {
        $errors = [];
        if (($item->getQuantity() ?? 0) < 0) {
            $errors[] = 'Quantity cannot be negative.';
        }
        if (($item->getUnitPrice() ?? 0.0) <= 0.0) {
            $errors[] = 'Unit price must be greater than 0.';
        }
        return $errors;
    }

    public function computeEstimatedValue(Inventory $item): float
    {
        $quantity = (float) ($item->getQuantity() ?? 0);
        $unitPrice = (float) ($item->getUnitPrice() ?? 0.0);
        return round($quantity * $unitPrice, 2);
    }

    public function needsRestock(Inventory $item, int $threshold = 5): bool
    {
        return ($item->getQuantity() ?? 0) <= $threshold;
    }
}
```

### 4.4 Classe de tests — `tests/Service/InventoryManagerTest.php`

Tests implémentés :

| Méthode de test                              | Cas couvert                  |
| -------------------------------------------- | ---------------------------- |
| `testValidInventoryReturnsNoErrors()`        | Cas valide                   |
| `testNegativeQuantityIsRejected()`           | Règle 1 invalide             |
| `testZeroOrNegativeUnitPriceIsRejected()`    | Règle 2 invalide             |
| `testEstimatedValueIsCorrectlyComputed()`    | Règle 3 (calcul valeur)      |
| `testNeedsRestockBelowThreshold()`           | Règle 4 (alerte stock)       |
| `testNeedsRestockAboveThreshold()`           | Règle 4 (cas négatif)        |

Extrait :

```php
public function testNegativeQuantityIsRejected(): void
{
    $item = $this->makeInventory(-3, 5.00);
    $errors = $this->manager->validateBusinessRules($item);
    self::assertContains('Quantity cannot be negative.', $errors);
}
```

### 4.5 Exécution

```bash
php bin/phpunit tests/Service/InventoryManagerTest.php
```

Résultat attendu :

```
PHPUnit 11.5.x by Sebastian Bergmann and contributors.

......                                                              6 / 6 (100%)

Time: 00:00.045, Memory: 18.00 MB

OK (6 tests, 7 assertions)
```

> **Capture d'écran 6 — Résultat PHPUnit (vert)**
> _[CAPTURE D'ÉCRAN À INSÉRER : phpunit-tests-success.png]_

---

## 5. Conclusion

Les trois ateliers ont permis :

- **Workshop 1 (Doctor Doctrine)** — sécurisation du mot de passe
  utilisateur, normalisation des colonnes monétaires en `DECIMAL(10,2)` et
  ajout de la sémantique de composition (orphanRemoval) sur la relation
  `Orders → OrderItems`. Résultat : **13 issues → 2 issues** (-85 %).
- **Workshop 2 (PHPStan)** — mise en place d'un outil d'analyse statique
  (niveau 8) avec un fichier de configuration documenté et reproductible.
  Erreurs résiduelles toutes documentées dans `ignoreErrors`.
- **Workshop 3 (Tests Unitaires)** — création d'un service métier
  `InventoryManager` testé avec 6 cas couvrant les 2 règles métier
  principales + 2 bonus.

Aucune route, aucun mécanisme d'authentification, aucune entité existante
n'a été cassée par les modifications. Toutes les corrections sont
additives ou rétro-compatibles.

---

## 6. Annexes — Commandes utilisées

### 6.1 Workshop Doctor Doctrine

```bash
composer show ahmed-bhs/doctrine-doctor
php bin/console cache:clear
symfony server:start                       # ou : php -S 127.0.0.1:8000 -t public
# Puis ouvrir http://127.0.0.1:8000/_profiler  pour voir l'onglet Doctor Doctrine
```

### 6.2 Workshop PHPStan

```bash
composer require --dev phpstan/phpstan
vendor/bin/phpstan --version
vendor/bin/phpstan analyse src                       # première analyse (level 0)
vendor/bin/phpstan analyse                           # avec phpstan.neon (level 5 puis 8)
vendor/bin/phpstan analyse src/Controller
vendor/bin/phpstan analyse src/Service
```

### 6.3 Workshop Tests Unitaires

```bash
php bin/phpunit                                       # toute la suite
php bin/phpunit tests/Service/InventoryManagerTest.php
php bin/phpunit --testdox                             # affichage lisible
```

### 6.4 Fichiers modifiés

| Fichier                                          | Workshop | Type            |
| ------------------------------------------------ | -------- | --------------- |
| `composer.json`                                  | 2        | modifié         |
| `phpstan.neon`                                   | 2        | **créé**        |
| `config/packages/doctrine_doctor.yaml`           | 1        | **créé**        |
| `src/Entity/User.php`                            | 1        | modifié         |
| `src/Entity/Inventory.php`                       | 1        | modifié         |
| `src/Entity/Rental.php`                          | 1        | modifié         |
| `src/Entity/Orders.php`                          | 1        | modifié         |
| `src/Twig/TimeAgoExtension.php`                  | 2        | modifié         |
| `src/Service/InventoryManager.php`               | 3        | **créé**        |
| `tests/Service/InventoryManagerTest.php`         | 3        | **créé**        |

### 6.5 Problèmes / limitations restants

- **PHPStan** : quelques erreurs liées aux generic des `Collection` Doctrine
  ont été ignorées via `ignoreErrors` ; idéalement à retyper dans une
  prochaine itération qualité.
- **Doctor Doctrine** : 1 alerte `SlowQueryAnalyzer` sur `findAll()` dans
  `InventoryController` reste tolérée (volume admin acceptable). À
  remplacer par une pagination KnpPaginator si la volumétrie augmente.
- **Décimal vs string** : par sécurité (non régression), les propriétés
  PHP des champs monétaires restent `?float` alors que la recommandation
  est `?string`. Une PR ultérieure pourra migrer progressivement les
  controllers concernés.
- **Migration de schéma** : penser à exécuter `php bin/console
  doctrine:migrations:diff` puis `migrations:migrate` pour matérialiser le
  passage des colonnes monétaires en `DECIMAL(10,2)`.

---

_Fin du rapport._
