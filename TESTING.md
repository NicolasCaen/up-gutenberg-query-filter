# Guide de test - Masquage dynamique des termes

## Fonctionnalité
Lorsque plusieurs filtres de taxonomie sont actifs, les termes qui n'ont aucun résultat avec les filtres actuels sont automatiquement masqués.

## Exemple d'utilisation

### Scénario : Filtres Couleur et Taille

Imaginons que vous avez :
- **Taxonomie "Couleur"** : Rouge, Noir, Bleu, Vert
- **Taxonomie "Taille"** : Petit, Moyen, Grand

Et les produits suivants :
- Produit A : Rouge, Petit
- Produit B : Noir, Grand
- Produit C : Bleu, Moyen
- Produit D : Rouge, Grand

### Comportement attendu

1. **État initial** : Tous les termes sont visibles dans les deux filtres

2. **L'utilisateur coche "Petit" dans le filtre Taille** :
   - Le filtre Couleur masque automatiquement "Noir", "Bleu", "Vert"
   - Seul "Rouge" reste visible (car Produit A est Rouge + Petit)

3. **L'utilisateur coche "Rouge" dans le filtre Couleur** :
   - Le filtre Taille masque "Moyen"
   - Seuls "Petit" et "Grand" restent visibles (Produits A et D)

4. **L'utilisateur décoche "Petit"** :
   - Le filtre Couleur se met à jour
   - D'autres couleurs peuvent redevenir visibles selon les produits disponibles

## Configuration requise

### 1. Créer les taxonomies
```php
// Dans functions.php ou un plugin
register_taxonomy('couleur', 'product', [
    'label' => 'Couleur',
    'public' => true,
    'hierarchical' => false,
]);

register_taxonomy('taille', 'product', [
    'label' => 'Taille',
    'public' => true,
    'hierarchical' => false,
]);
```

### 2. Ajouter les blocs dans l'éditeur

1. Créer une page ou un template
2. Ajouter un bloc **Query Loop** (Boucle de requête)
3. À l'intérieur du Query Loop, ajouter :
   - Un bloc **Taxonomy Filter** configuré pour "couleur"
   - Un bloc **Taxonomy Filter** configuré pour "taille"
4. Configurer chaque bloc :
   - Sélectionner la taxonomie appropriée
   - Définir les libellés si nécessaire
   - Choisir l'opérateur (IN ou AND)

### 3. Tester sur le front-end

1. Ouvrir la page sur le front-end
2. Cocher une option dans un filtre
3. Observer que les termes sans résultats dans l'autre filtre sont masqués
4. Les termes cochés restent toujours visibles même s'ils ont 0 résultats

## Points techniques

### Endpoint REST API
- **URL** : `/wp-json/query-filter/v1/available-terms`
- **Méthode** : GET
- **Paramètres** :
  - `taxonomy` (requis) : slug de la taxonomie
  - `query_id` (optionnel) : ID de la query loop
  - `post_type` (optionnel) : type de contenu (défaut: 'post')
  - `filters` (optionnel) : JSON des filtres actifs

### Exemple de requête
```
/wp-json/query-filter/v1/available-terms?taxonomy=couleur&post_type=product&filters={"taille":{"values":["petit"],"operator":"IN"}}
```

### Réponse attendue
```json
{
  "taxonomy": "couleur",
  "terms": [
    {
      "term_id": 1,
      "slug": "rouge",
      "name": "Rouge",
      "count": 2
    },
    {
      "term_id": 2,
      "slug": "noir",
      "name": "Noir",
      "count": 0
    }
  ]
}
```

## Dépannage

### Les termes ne se masquent pas
1. Vérifier que les fichiers ont été compilés : `npm run build`
2. Vider le cache du navigateur
3. Vérifier la console JavaScript pour les erreurs
4. Tester l'endpoint REST API manuellement

### Erreur 404 sur l'endpoint REST API
1. Vérifier que le plugin est activé
2. Rafraîchir les permaliens : Réglages > Permaliens > Enregistrer

### Les transitions CSS ne fonctionnent pas
1. Vérifier que le CSS a été compilé
2. Inspecter les éléments pour voir si les styles sont appliqués
3. Tester dans un navigateur moderne (Chrome, Firefox, Safari récent)

## Améliorations futures possibles

- Ajouter un compteur de résultats à côté de chaque terme
- Permettre de désactiver le masquage via une option
- Ajouter une animation de chargement pendant la requête API
- Mettre en cache les résultats de l'API pour améliorer les performances
