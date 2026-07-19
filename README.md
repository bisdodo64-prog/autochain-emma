# AutoChain Emma+ - Gestion de Parc Automobile Blockchain

> **Démo / soutenance** : voir [`DEMO.md`](./DEMO.md) — scripts `start-demo.bat`, `seed-demo.bat`, `reset-blockchain-ids.bat`.

AutoChain Emma+ est une solution de gestion de parc automobile utilisant la technologie Blockchain pour garantir l'intégrité de l'historique des véhicules (kilométrage, maintenance) et digitaliser les processus administratifs.

## 🎯 Objectifs

- ✅ Garantir l'intégrité du kilométrage et de l'historique d'entretien
- ✅ Digitaliser l'attribution des véhicules aux chauffeurs
- ✅ Centraliser les documents administratifs (Assurances, Cartes Grises)
- ✅ Réduire les coûts de gestion via l'automatisation des alertes

## 🏗️ Architecture

### Couche Blockchain (Confiance)
- **Technologie**: Smart Contracts développés en Solidity
- **Stockage On-chain**: Preuves (Hashs), kilométrage et statuts critiques
- **Réseau**: Ethereum (testnet recommandé pour le développement)

### Couche Backend (Performance)
- **Framework**: Laravel 10 (uniquement — pas Django / Node / Java)
- **Base de Données**: PostgreSQL
- **Authentification**: Laravel Sanctum + Web3 (MetaMask)
- **Gestion des rôles**: Spatie Laravel Permission

### Couche Frontend (Interface)
- **Framework**: Vue.js 3
- **State Management**: Vuex
- **Styling**: TailwindCSS
- **Web3**: Ethers.js

### Couche Stockage
- **Documents privés**: Stockage local sécurisé
- **Documents publics**: IPFS (InterPlanetary File System)

## 📋 Prérequis

- PHP >= 8.1
- Composer
- Node.js >= 18
- PostgreSQL >= 14
- Truffle (pour le déploiement blockchain)
- IPFS (optionnel, pour le stockage décentralisé)
- MetaMask (pour l'authentification Web3)

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/yourusername/autochain-emma.git
cd autochain-emma
```

### 2. Configuration Backend

```bash
cd backend-laravel/toto
composer install
cp .env.example .env
php artisan key:generate
```

Configurer le fichier `.env`:

```env
APP_NAME=AutoChainEmma
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://127.0.0.1:9001

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=autochain_emma
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Blockchain Configuration
WEB3_RPC_URL=http://localhost:8545
ADMIN_PRIVATE_KEY=your_admin_private_key
GARAGE_PRIVATE_KEY=your_garage_private_key

# IPFS Configuration
IPFS_API_URL=http://localhost:5001
IPFS_GATEWAY_URL=http://localhost:8080
```

Exécuter les migrations et les seeders:

```bash
php artisan migrate
php artisan db:seed
```

Démarrer le serveur (recommandé) :

```bash
# depuis la racine du projet
start-backend.bat
# API: http://127.0.0.1:9001
```

Alternative :

```bash
php artisan serve --host=127.0.0.1 --port=9001
```

### 3. Configuration Blockchain

```bash
cd blockchain
npm install
```

Configurer `truffle-config.js` avec vos informations réseau:

```javascript
module.exports = {
  networks: {
    development: {
      host: "127.0.0.1",
      port: 8545,
      network_id: "*"
    }
  }
};
```

Compiler et déployer les contrats:

```bash
npx truffle compile
npx truffle migrate
```

### 4. Configuration Frontend

```bash
cd frontend-spa
npm install
```

Créer un fichier `.env`:

```env
VITE_API_URL=/api
VITE_APP_CONTRACT_ADDRESS=0xYourContractAddress
VITE_CHAIN_ID=1337
```

> En local Laragon : API sur **http://127.0.0.1:9001** (proxy Vite `/api`).  
> Préférer `start-demo.bat` plutôt que `php artisan serve`.

Démarrer le serveur de développement:

```bash
npm run dev
```

Voir aussi **DEMO.md** et **SECURITY.md**.

## 👥 Rôles et Permissions

### Super Admin
- Gestion des comptes utilisateurs
- Configuration du Smart Contract
- Archivage global
- Toutes les permissions

### Gestionnaire de Parc
- Enregistrement des véhicules
- Affectation aux chauffeurs
- Suivi des coûts
- Gestion des alertes

### Chauffeur
- Déclaration de prise en charge
- Relevé kilométrique fin de trajet
- Consultation de son véhicule assigné

### Garagiste Agrée
- Certification des opérations de maintenance
- Saisie des pièces changées
- Enregistrement sur blockchain

### Auditeur / Acheteur
- Consultation de l'historique public certifié
- Vérification des documents
- Lecture seule

## 🔐 Sécurité et RGPD

- **Aucune donnée nominative** sur la blockchain (seuls les identifiants techniques)
- **Intégrité**: Utilisation de sommes de contrôle (Hash) pour vérifier les documents
- **Double validation**: Transactions critiques nécessitent 2 signatures
- **Chiffrement**: Données sensibles stockées localement chiffrées

## 📱 Fonctionnalités Principales

### Module Actifs Numériques (Blockchain)
- ✅ Registre de Maintenance immuable
- ✅ Compteur Certifié avec horodatage
- ✅ Double signature pour transferts

### Module Gestion Opérationnelle (Backend)
- ✅ Gestion Documentaire avec hash verification
- ✅ Moteur d'Alertes automatiques
- ✅ Suivi de Consommation carburant
- ✅ Attribution de véhicules

### Module Interface Utilisateur (Frontend)
- ✅ Tableau de bord de la flotte
- ✅ Timeline du véhicule (blockchain + admin)
- ✅ Authentification Web3 (MetaMask)
- ✅ Vérification de documents

## 🧪 Tests

### Backend

```bash
cd backend-laravel/toto
php artisan test
```

### Blockchain

```bash
cd blockchain
npx truffle test
```

### Frontend

```bash
cd frontend-spa
npm run smoke
```

## 📝 Comptes de Test

Après l'exécution des seeders, vous pouvez utiliser ces comptes:

| Rôle | Email | Mot de passe | Wallet |
|------|-------|-------------|--------|
| Super Admin | admin@autochain.com | password | 0x1234... |
| Gestionnaire | manager@autochain.com | password | 0x2345... |
| Chauffeur 1 | driver1@autochain.com | password | 0x3456... |
| Chauffeur 2 | driver2@autochain.com | password | 0x4567... |
| Garagiste | garage@autochain.com | password | 0x5678... |
| Auditeur | auditor@autochain.com | password | 0x6789... |

## 🔧 API Endpoints

### Authentification
- `POST /api/auth/login` - Login classique
- `POST /api/auth/web3-login` - Login Web3
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Profil utilisateur

### Véhicules
- `GET /api/vehicles` - Liste des véhicules
- `POST /api/vehicles` - Créer un véhicule
- `GET /api/vehicles/{id}` - Détails véhicule
- `PUT /api/vehicles/{id}/mileage` - Mettre à jour kilométrage
- `POST /api/vehicles/{id}/assign-driver` - Attribuer chauffeur
- `GET /api/vehicles/{id}/timeline` - Timeline véhicule
- `GET /api/vehicles/{id}/documents/{docId}/verify` - Vérifier document

### Maintenance
- `POST /api/vehicles/{vehicleId}/maintenance` - Enregistrer maintenance
- `GET /api/vehicles/{vehicleId}/maintenance` - Historique maintenance

### Chauffeurs
- `GET /api/drivers` - Liste chauffeurs
- `POST /api/drivers` - Créer chauffeur
- `POST /api/drivers/{id}/wallet` - Attribuer wallet

### Documents
- `POST /api/vehicles/{vehicleId}/documents` - Upload document
- `GET /api/vehicles/{vehicleId}/documents` - Liste documents
- `DELETE /api/documents/{id}` - Supprimer document

### Alertes
- `GET /api/alerts` - Liste alertes
- `POST /api/alerts/{id}/dismiss` - Ignorer alerte
- `GET /api/alerts/stats` - Statistiques alertes

### Blockchain (Admin)
- `POST /api/blockchain/authorize-garage` - Autoriser garage
- `POST /api/blockchain/sync` - Synchroniser blockchain

## 🚨 Alertes Automatiques

Le système génère automatiquement des alertes pour:

- **Expiration assurance**: 30 jours, 7 jours, et après expiration
- **Contrôle technique**: 30 jours, 7 jours, et après expiration
- **Vidange**: Tous les 10 000 km
- **Consommation carburant**: Si > 15 L/100km

## 📚 Documentation Technique

### Smart Contract (VehicleRegistry.sol)

Le contrat principal gère:
- Enregistrement des véhicules
- Mise à jour du kilométrage
- Enregistrement des maintenances
- Transferts avec double signature

### Structure de la Base de Données

- `users` - Utilisateurs et rôles
- `vehicles` - Véhicules
- `maintenances` - Historique maintenance
- `mileage_records` - Relevés kilométriques
- `documents` - Documents administratifs
- `fuel_records` - Records de carburant
- `alerts` - Alertes système
- `blockchain_transactions` - Transactions blockchain

## 🤝 Contribution

Les contributions sont les bienvenues! Veuillez suivre ces étapes:

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT.

## 👨‍💻 Auteurs

- **AutoChain Team** - Développement initial

## 🙏 Remerciements

- Laravel Framework
- Vue.js
- Ethereum Foundation
- IPFS
