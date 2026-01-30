# API E-commerce Laravel - Documentation Complète

## 🚀 Fonctionnalités

### 1. **Gestion des Utilisateurs & Authentification**
- Inscription/Connexion avec JWT
- Profils utilisateurs (Client, Vendeur, Admin)
- Vérification email
- Réinitialisation mot de passe
- Gestion des adresses multiples

### 2. **Gestion des Produits**
- CRUD complet des produits
- Catégories et sous-catégories
- Attributs de produits (taille, couleur, etc.)
- Variations de produits
- Gestion des stocks
- Images multiples
- Recherche et filtres avancés
- Produits en promotion

### 3. **Gestion du Panier**
- Ajouter/Supprimer/Modifier articles
- Panier persistant
- Calcul automatique des totaux
- Application des promotions

### 4. **Gestion des Commandes**
- Création de commandes
- Suivi des commandes
- Historique complet
- Statuts multiples (en attente, payé, expédié, livré, annulé)
- Factures PDF

### 5. **Système de Paiement**
- Intégration multi-passerelles (Stripe, PayPal, etc.)
- Paiements sécurisés
- Historique des transactions

### 6. **Gestion des Avis & Notations**
- Avis produits
- Système de notation (1-5 étoiles)
- Modération des avis

### 7. **Gestion des Coupons & Promotions**
- Codes promo
- Réductions en pourcentage/montant fixe
- Limites d'utilisation
- Dates de validité

### 8. **Liste de Souhaits (Wishlist)**
- Ajouter/Supprimer produits favoris
- Partage de wishlist

### 9. **Gestion des Livraisons**
- Méthodes de livraison multiples
- Calcul des frais de port
- Suivi d'expédition

### 10. **Tableau de Bord Admin**
- Statistiques des ventes
- Gestion des utilisateurs
- Gestion des commandes
- Rapports

### 11. **Notifications**
- Email notifications
- Notifications push
- SMS (optionnel)

### 12. **Multi-vendeurs (Marketplace)**
- Inscription vendeurs
- Boutiques vendeurs
- Commission système

## 📋 Prérequis

- PHP >= 8.1
- Composer
- MySQL >= 8.0
- Redis (optionnel, pour cache)

## 🛠️ Installation

```bash
# Cloner le projet
git clone <repository-url>
cd ecommerce-api

# Installer les dépendances
composer install

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Configurer la base de données dans .env
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=

# Créer la base de données
mysql -u root -e "CREATE DATABASE ecommerce"

# Exécuter les migrations
php artisan migrate

# Seeder les données de test
php artisan db:seed

# Générer le secret JWT
php artisan jwt:secret

# Créer le lien symbolique pour le stockage
php artisan storage:link

# Lancer le serveur
php artisan serve
```

## 🔑 Configuration JWT

Ajoutez dans `.env`:
```
JWT_SECRET=your-secret-key
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

## 📡 Endpoints API

### Authentification

```
POST   /api/auth/register          - Inscription
POST   /api/auth/login             - Connexion
POST   /api/auth/logout            - Déconnexion
POST   /api/auth/refresh           - Rafraîchir le token
GET    /api/auth/me                - Profil utilisateur
POST   /api/auth/forgot-password   - Mot de passe oublié
POST   /api/auth/reset-password    - Réinitialiser mot de passe
```

### Produits

```
GET    /api/products               - Liste des produits (avec filtres)
GET    /api/products/{id}          - Détails d'un produit
POST   /api/products               - Créer un produit (Admin/Vendeur)
PUT    /api/products/{id}          - Modifier un produit
DELETE /api/products/{id}          - Supprimer un produit
GET    /api/products/search        - Rechercher des produits
GET    /api/products/featured      - Produits en vedette
```

### Catégories

```
GET    /api/categories             - Liste des catégories
GET    /api/categories/{id}        - Détails d'une catégorie
POST   /api/categories             - Créer une catégorie (Admin)
PUT    /api/categories/{id}        - Modifier une catégorie
DELETE /api/categories/{id}        - Supprimer une catégorie
```

### Panier

```
GET    /api/cart                   - Voir le panier
POST   /api/cart/add               - Ajouter au panier
PUT    /api/cart/update/{id}       - Modifier quantité
DELETE /api/cart/remove/{id}       - Retirer du panier
DELETE /api/cart/clear             - Vider le panier
```

### Commandes

```
GET    /api/orders                 - Liste des commandes
GET    /api/orders/{id}            - Détails d'une commande
POST   /api/orders                 - Créer une commande
PUT    /api/orders/{id}/cancel     - Annuler une commande
GET    /api/orders/{id}/invoice    - Télécharger facture PDF
```

### Avis

```
GET    /api/products/{id}/reviews  - Avis d'un produit
POST   /api/products/{id}/reviews  - Ajouter un avis
PUT    /api/reviews/{id}           - Modifier un avis
DELETE /api/reviews/{id}           - Supprimer un avis
```

### Wishlist

```
GET    /api/wishlist               - Liste de souhaits
POST   /api/wishlist/add           - Ajouter à la wishlist
DELETE /api/wishlist/remove/{id}   - Retirer de la wishlist
```

### Coupons

```
POST   /api/coupons/validate       - Valider un code promo
GET    /api/coupons                - Liste des coupons (Admin)
POST   /api/coupons                - Créer un coupon (Admin)
```

### Admin

```
GET    /api/admin/dashboard        - Statistiques
GET    /api/admin/users            - Gestion utilisateurs
GET    /api/admin/orders           - Gestion commandes
GET    /api/admin/reports          - Rapports
```

## 📝 Exemples de Requêtes

### 1. Inscription
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 2. Connexion
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### 3. Liste des Produits avec Filtres
```bash
curl -X GET "http://localhost:8000/api/products?category=1&min_price=10&max_price=100&sort=price&order=asc" \
  -H "Authorization: Bearer {token}"
```

### 4. Ajouter au Panier
```bash
curl -X POST http://localhost:8000/api/cart/add \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "quantity": 2,
    "variant_id": 3
  }'
```

### 5. Créer une Commande
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "shipping_address_id": 1,
    "payment_method": "stripe",
    "coupon_code": "SAVE10"
  }'
```

## 🔒 Sécurité

- Authentification JWT
- Validation des données
- Protection CSRF
- Rate limiting
- Sanitization des entrées
- Encryption des données sensibles
- HTTPS obligatoire en production

## 📊 Base de Données

### Tables Principales

- `users` - Utilisateurs
- `products` - Produits
- `categories` - Catégories
- `product_images` - Images produits
- `product_variants` - Variantes de produits
- `carts` - Paniers
- `cart_items` - Articles du panier
- `orders` - Commandes
- `order_items` - Articles de commande
- `payments` - Paiements
- `reviews` - Avis
- `wishlists` - Listes de souhaits
- `coupons` - Codes promo
- `addresses` - Adresses
- `shipping_methods` - Méthodes de livraison

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Tests avec coverage
php artisan test --coverage
```

## 📦 Déploiement

1. Configurer les variables d'environnement
2. Optimiser l'application
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
3. Configurer le serveur web (nginx/apache)
4. Configurer les workers de queue
5. Mettre en place les backups automatiques

## 🤝 Contribution

Les contributions sont les bienvenues ! Veuillez créer une issue ou une pull request.

## 📄 Licence

MIT License

## 📧 Support

Pour toute question : support@example.com