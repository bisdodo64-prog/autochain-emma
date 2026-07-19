# Sécurité — AutoChain Emma+

## Règles absolues

1. **Ne jamais coller** une clé privée MetaMask / Alchemy dans un chat, ticket ou e-mail.
2. Les fichiers `.env` et `config/blockchain-profiles/*.json` (hors `*.example.json`) restent **locaux**.
3. Utiliser **uniquement** des comptes testnet (Sepolia) pour la démo.

## Si une clé a déjà fuité

1. Alchemy : révoquer / régénérer la clé API.
2. MetaMask : créer un **nouveau** compte testnet, transferer l’ETH Sepolia, mettre à jour `.env` (`ADMIN_PRIVATE_KEY`, etc.).
3. Relancer `setup-sepolia.bat` / mettre à jour `sepolia.json` **localement**.
4. `reset-blockchain-ids.bat` puis re-certifier les véhicules dans l’UI.

## Checklist avant soutenance / zip projet

- [ ] Aucune clé réelle dans `sepolia.json` / `local.json` versionnés
- [ ] `git status` ne liste pas de `.env`
- [ ] Mots de passe démo (`password`) OK pour soutenance locale uniquement
- [ ] Backend non exposé sur Internet sans HTTPS / auth

## Stockage

- Avatars : `storage/app/public/avatars` + `php artisan storage:link`
- Documents : disque `local` Laravel (hors web root)
