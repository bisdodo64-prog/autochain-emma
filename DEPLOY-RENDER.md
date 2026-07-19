# Déployer AutoChain Emma+ sur Render (gratuit)

Ce guide déploie :
- **API Laravel** → Web Service Docker (`autochain-api`)
- **Frontend Vue** → Static Site (`autochain-web`)
- **PostgreSQL** → **Neon** (gratuit, recommandé) ou Render Postgres (souvent payant)

## 0. Prérequis

1. Compte [GitHub](https://github.com) + [Render](https://render.com) + [Neon](https://neon.tech)
2. Code poussé sur GitHub **sans** `.env` ni clés (fichiers déjà prêts : `render.yaml`, `Dockerfile`, CORS)
3. Générer une `APP_KEY` en local :
   ```bash
   cd backend-laravel/toto
   php artisan key:generate --show
   ```

> **Astuce Neon :** tu peux coller uniquement `DATABASE_URL` (connection string) dans Render et laisser les `DB_*` vides — Laravel lit `DATABASE_URL`.

## 1. Base de données Neon (gratuit)

1. Créer un projet Neon  
2. Copier : Host, Database, User, Password, Port (souvent `5432` ou `6543`)  
3. Activer SSL si proposé (`sslmode=require`)

Dans Render (variables API), tu mettras :
```
DB_CONNECTION=pgsql
DB_HOST=ep-xxxx.eu-central-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=********
```

Option Laravel (parfois plus simple) : une seule variable  
`DATABASE_URL=postgresql://user:pass@host/db?sslmode=require`  
→ dans ce cas, configure aussi Laravel pour lire `DATABASE_URL` (déjà supporté si tu l’ajoutes dans `config/database.php` / `.env`).

## 2. Déploiement via Blueprint

1. Render → **New** → **Blueprint**
2. Connecte le repo `autochain-emma`
3. Render lit `render.yaml` à la racine
4. Remplis les variables marquées **sync: false** :

| Variable | Exemple |
|----------|---------|
| `APP_KEY` | `base64:...` (depuis `key:generate --show`) |
| `APP_URL` | `https://autochain-api.onrender.com` (URL fournie après 1er deploy) |
| `FRONTEND_URL` | `https://autochain-web.onrender.com` |
| `DB_*` | Neon |
| `BLOCKCHAIN_*` / `ADMIN_PRIVATE_KEY` | Comme ton `.env` local Sepolia |
| Frontend `VITE_API_URL` | `https://autochain-api.onrender.com/api` |
| Frontend `VITE_APP_CONTRACT_ADDRESS` | Adresse contrat Sepolia |

5. Deploy

> Au 1er lancement, mets `RUN_SEED=true` (déjà dans le blueprint) pour créer admin + données démo.  
> Ensuite passe `RUN_SEED=false` pour ne pas réécraser.

## 3. Déploiement manuel (sans Blueprint)

### A. API

1. **New Web Service** → repo → Root Directory : `backend-laravel/toto`
2. Runtime : **Docker**
3. Dockerfile Path : `./Dockerfile`
4. Health Check Path : `/api/health`
5. Ajoute les env vars (tableau ci-dessus)
6. Deploy

### B. Frontend

1. **New Static Site** → repo → Root : `frontend-spa`
2. Build : `npm install && npm run build`
3. Publish : `dist`
4. Env : `VITE_API_URL=https://TON-API.onrender.com/api`
5. Rewrite SPA : `/*` → `/index.html` (Render UI → Redirects/Rewrites)

## 4. Après le déploiement

1. Ouvre le front Render  
2. Login : `admin@autochain.com` / `password`  
3. Si CORS erreur : vérifie `FRONTEND_URL` = URL exacte du front (https, sans slash final)  
4. Si 500 DB : vérifie Neon + SSL  
5. Free tier : le service **s’endort** après inactivité → 1er chargement lent (30–60 s)

## 5. Commandes utiles (Shell Render)

Dans le dashboard du service API → **Shell** :
```bash
php artisan migrate --force
php artisan db:seed --force --class=TestDataSeeder
php artisan storage:link
php artisan vehicles:reset-blockchain --force
```

## 6. Sécurité

- Ne mets **jamais** `ADMIN_PRIVATE_KEY` dans Git  
- Utilise uniquement Sepolia (testnet)  
- Voir `SECURITY.md`

## Dépannage rapide

| Problème | Solution |
|----------|----------|
| Build Docker échoue | Logs Render → souvent `composer` / extension `pgsql` |
| Front appelle localhost | Rebuild front avec bon `VITE_API_URL` |
| CORS blocked | `FRONTEND_URL` + redéployer API |
| Health check fail | Attendre le cold start, ou désactiver un moment le health check |
| Seed non lancé | `RUN_SEED=true` puis redeploy / Shell seed |

## Ordre résumé

```
GitHub → Neon DB → Render Blueprint (API + Web) → renseigner secrets → Deploy → tester login
```
