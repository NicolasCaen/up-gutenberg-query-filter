# Utilisation de `data-count` et classe `inactive`

## Vue d'ensemble

Chaque terme de filtre (`.wp-block-query-filter__term`) possède maintenant un attribut `data-count` qui est **toujours synchronisé** avec le compteur visuel affiché entre parenthèses.

## Attribut `data-count`

```html
<div class="wp-block-query-filter__term" data-count="5" data-term-slug="couleur-rouge">
  <!-- Contenu du terme -->
</div>
```

- **Valeur** : Nombre de posts correspondants pour ce terme
- **Mise à jour** : Automatique via JavaScript après chaque changement de filtre
- **Synchronisation** : Toujours identique au compteur visuel `(5)`

## Classe `inactive`

La classe `inactive` est **automatiquement ajoutée** quand `data-count="0"` :

```html
<div class="wp-block-query-filter__term inactive" data-count="0" data-term-slug="couleur-verte">
  <!-- Contenu du terme -->
</div>
```

### Comportement par défaut (CSS)

```css
.wp-block-query-filter__term.inactive {
  opacity: 0.5;
  pointer-events: none;
}
```

## Exemples de personnalisation CSS

### Cibler les termes à 0 résultat

```css
/* Via la classe inactive */
.wp-block-query-filter__term.inactive {
  opacity: 0.3;
  text-decoration: line-through;
}

/* Via l'attribut data-count */
.wp-block-query-filter__term[data-count="0"] {
  background-color: #f0f0f0;
}
```

### Cibler les termes avec exactement 1 résultat

```css
.wp-block-query-filter__term[data-count="1"] label::after {
  content: " ⭐";
}
```

### Cibler les termes avec beaucoup de résultats

```css
.wp-block-query-filter__term[data-count^="1"]:not([data-count="1"]),
.wp-block-query-filter__term[data-count^="2"],
.wp-block-query-filter__term[data-count^="3"] {
  font-weight: bold;
}
```

### Afficher le compteur en badge

```css
.wp-block-query-filter__term::after {
  content: attr(data-count);
  display: inline-block;
  background: #007cba;
  color: white;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 0.8em;
  margin-left: 5px;
}

.wp-block-query-filter__term[data-count="0"]::after {
  background: #ccc;
}
```

## Avantages de cette approche

1. **Synchronisation garantie** : Le `data-count` et le compteur visuel utilisent la même source de données
2. **Flexibilité CSS** : Possibilité de cibler précisément les termes selon leur nombre de résultats
3. **Accessibilité** : L'attribut `data-count` est accessible via JavaScript pour des besoins avancés
4. **Cohérence** : La classe `inactive` est toujours cohérente avec `data-count="0"`

## JavaScript avancé

Si vous avez besoin d'accéder au compteur en JavaScript :

```javascript
// Récupérer tous les termes inactifs
const inactiveTerms = document.querySelectorAll('.wp-block-query-filter__term.inactive');

// Récupérer le count d'un terme spécifique
const termElement = document.querySelector('[data-term-slug="couleur-rouge"]');
const count = parseInt(termElement.getAttribute('data-count'), 10);

// Écouter les changements de compteurs
const observer = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    if (mutation.attributeName === 'data-count') {
      const newCount = mutation.target.getAttribute('data-count');
      console.log('Count updated to:', newCount);
    }
  });
});

document.querySelectorAll('.wp-block-query-filter__term').forEach((term) => {
  observer.observe(term, { attributes: true });
});
```
