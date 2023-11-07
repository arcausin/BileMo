# BileMo

1. Installation du projet  
```
git clone https://github.com/arcausin/BileMo.git
```

2. Configurer les variables d'environnement de base de données dans le fichier ".env.local" à la racine du projet dériver du fichier ".env"  

### ouvrer une invite de commande et rendez-vous dans le répertoire du projet  

3. Installer les dépendances du projet  
```
composer install  
```

4. Créer la base de données.  
```
php bin/console doctrine:database:create
```

5. Générer les structures de table  
```
php bin/console doctrine:migrations:migrate
```

6. générer les données pré-établies pour tester le projet  
```
php bin/console doctrine:fixtures:load
```

7. Configurer les jetons d'authentification JWT  
```
créer un dossier nommé "jwt" dans le répertoire "/config"

openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

8. Configurer les variables d'environnement de JWT dans le fichier ".env.local" à la racine du projet dériver du fichier ".env"  

9. Lancer le serveur local du projet  
```
symfony server:start
```

10. Rendez-vous sur la page web "{localhost}/api/doc" afin d'accéder a la docummentation
