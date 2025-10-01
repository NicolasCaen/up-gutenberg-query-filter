# Option "Show Reset Button"

## Description
L'option **Show Reset Button** permet de contrôler l'affichage du bouton de réinitialisation "Tous" (ou "All") dans les filtres de taxonomie.

## Emplacement
Cette option se trouve dans le panneau de réglages du bloc **Taxonomy Filter** :

1. Sélectionner le bloc Taxonomy Filter dans l'éditeur
2. Ouvrir le panneau de réglages à droite
3. Dans la section "Taxonomy Settings", trouver l'option **Show Reset Button**

## Fonctionnement

### Activé (par défaut)
- Le bouton "Tous" est visible
- Les utilisateurs peuvent cliquer dessus pour réinitialiser tous les filtres de cette taxonomie
- Le libellé du bouton peut être personnalisé via le champ "Empty Choice Label"

### Désactivé
- Le bouton "Tous" est masqué
- Les utilisateurs doivent décocher manuellement les cases pour réinitialiser les filtres
- Le champ "Empty Choice Label" n'est plus affiché dans les réglages

## Cas d'usage

### Quand activer le bouton
- Interface avec de nombreux termes où la réinitialisation rapide est utile
- Expérience utilisateur nécessitant un contrôle explicite de la réinitialisation
- Design avec un bouton "Tous" stylisé

### Quand désactiver le bouton
- Design minimaliste où le bouton n'est pas nécessaire
- Interface où les utilisateurs doivent toujours avoir au moins un filtre actif
- Cas où la réinitialisation n'a pas de sens (ex: filtres obligatoires)

## Exemple de code

### Dans block.json
```json
{
  "attributes": {
    "showResetButton": {
      "type": "boolean",
      "default": true
    }
  }
}
```

### Dans render.php
```php
<?php
$show_reset_button = $attributes['showResetButton'] ?? true;
?>
<?php if ( $show_reset_button ) : ?>
    <button type="button" class="wp-block-query-filter__reset" data-wp-on--click="actions.clearTerms">
        <?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?>
    </button>
<?php endif; ?>
```

## Compatibilité
- Cette option est disponible depuis la version 1.1.2
- Compatible avec tous les thèmes WordPress
- Fonctionne en back-office (éditeur) et en front-end

## Notes techniques
- L'attribut est stocké dans les métadonnées du bloc
- La valeur par défaut est `true` pour maintenir la compatibilité avec les blocs existants
- Le bouton utilise l'action `actions.clearTerms` de l'API d'interactivité WordPress
