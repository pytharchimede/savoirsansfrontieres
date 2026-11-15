# Cycle de livraison

1. Développer sur `feature/*`.
2. Créer une pull request `feature/*` vers `develop` et valider les tests.
3. Créer une pull request `develop` vers `main` après recette.
4. Déployer exclusivement `main` sur Diablo, après sauvegarde.

Ne jamais committer `.env`, mots de passe, données personnelles ou exports SQL.
