# AutoChain Emma+ — Guide de démonstration / soutenance

## Démarrage rapide (local)

1. Double-cliquer `start-demo.bat` (lance API `:9001` + frontend `:3000`)
2. Ouvrir http://localhost:3000
3. Connexion : `admin@autochain.com` / `password`

### Autres scripts utiles

| Script | Rôle |
|--------|------|
| `start-backend.bat` | API Laravel seule |
| `start-frontend.bat` | Vue Vite seule |
| `seed-demo.bat` | Recharge utilisateurs + véhicules (prix FCFA) |
| `reset-blockchain-ids.bat` | Remet les IDs on-chain à null (avant Sepolia) |
| `sync-blockchain.bat` | Appelle `POST /api/blockchain/sync` |
| `deploy-sepolia.bat` | Déploie le contrat sur Sepolia |
| `setup-sepolia.bat` | Applique le profil Sepolia |

## Comptes de démo

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Super Admin | admin@autochain.com | password |
| Gestionnaire | manager@autochain.com | password |
| Chauffeur | driver1@autochain.com | password |
| Garagiste | garage@autochain.com | password |
| Auditeur | auditor@autochain.com | password |

En Super Admin : sidebar → changer de rôle pour voir chaque espace + **Profil** dédié.

## Scénario soutenance (8–10 min)

1. **Login admin** → Dashboard (stats flotte)
2. **Profil** → photo + infos admin (ne doit plus planter)
3. **Véhicules** → Actualiser → **Nouveau véhicule** (plaque + prix FCFA) → Enregistrer
4. **Certifier** un véhicule (Sepolia ou local) → voir badge Certifié
5. Si IDs obsolètes (ex-Ganache) → **Re-certifier** ou lancer `reset-blockchain-ids.bat`
6. **Maintenance** → intervention + coût en FCFA
7. **Blockchain** → Synchroniser (ou `sync-blockchain.bat` avec token)
8. **Audit** → traçabilité
9. Sidebar → rôle **Gestionnaire** → dashboard sans bouton « Mon profil » redondant → page Véhicules
10. Rôle **Chauffeur / Garagiste / Auditeur** → menus filtrés + Profil du rôle

## Sepolia

1. MetaMask sur réseau **Sepolia** (compte avec ETH test)
2. `setup-sepolia.bat` puis `deploy-sepolia.bat` si besoin
3. Backend + frontend (pas de Ganache)
4. `reset-blockchain-ids.bat` ou seed-demo puis **Certifier** dans l’UI
5. **Ne jamais coller une clé privée** dans le chat / Discord / e-mail  
   → Si une clé a été exposée : créer un nouveau compte MetaMask testnet et mettre à jour `.env`

## Sécurité checklist

- [ ] Fichiers `.env` non partagés (voir `.gitignore` racine)
- [ ] Clés testnet uniquement (jamais mainnet)
- [ ] Mot de passe démo changé si déploiement public
- [ ] Backend uniquement en local / VPN pour la soutenance

## Ports

- Frontend : http://localhost:3000  
- API : http://127.0.0.1:9001 (`/api/...`)

## Sécurité

Voir **SECURITY.md**. Les profils `sepolia.json` / `local.json` ne doivent plus contenir de vraies clés versionnées — utiliser les `*.example.json`.

## Backup

`backup-db.bat` → dump PostgreSQL dans `backups/`.
