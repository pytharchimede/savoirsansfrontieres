# Administration Savoir Sans Frontières

## Déploiement sécurisé

1. Sur Diablo, récupérer la branche feature et vérifier PHP lint.
2. Appliquer **une seule fois** la migration SQL `database/migrations/002_admin_rbac.sql` dans la base MySQL configurée (via phpMyAdmin ou client mysql). La migration ne crée **aucun mot de passe**.
3. Déployer sur main via `bash deploy/deploy.sh` après les tests.
4. Créer le premier administrateur en SSH : `cd /home/ifmapci/ssf-app && php scripts/create_admin.php admin`. Le mot de passe est saisi sans écho, haché avec password_hash et jamais conservé en clair.
5. Se connecter sur `https://savoirsansfrontieres.com/admin/login`.

**Ne jamais utiliser admin/admin en production.** Le compte initial est `admin` avec un mot de passe fort choisi à l'installation.

## Groupes prédéfinis

- Administrateurs : toutes les permissions (groupe protégé)
- Gestionnaires : voir et exporter les inscriptions
- Lecteurs : voir les inscriptions

Permissions : `registrations.view`, `registrations.export`, `users.manage`, `groups.manage`.

Les nouveaux utilisateurs peuvent être affectés à un ou plusieurs groupes. Les comptes peuvent être désactivés. Les exports respectent les filtres et sont limités à 5000 lignes.

## Vérifications

- `/admin` redirige vers le login hors session.
- Les utilisateurs sans permission export ne voient pas les boutons et reçoivent HTTP 403 en accès direct.
- Les mutations exigent un jeton CSRF.
- Les tentatives de connexion sont limitées à huit échecs sur quinze minutes (nom d'utilisateur ou IP hachée).
- L'extension PHP ZipArchive est nécessaire pour exporter en XLSX. Le PDF utilise un générateur léger sans dépendance externe, avec translittération des caractères et mise en page simple.
- Ne jamais exécuter la création d'administrateur dans un navigateur.
