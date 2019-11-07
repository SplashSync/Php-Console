---
lang: fr
permalink: start/docker
title: Environnement de test (Docker)
---

Vous voulez essayer ce module? Ajustez-vous à vos besoins? Ou développer de nouvelles fonctionnalités? D'accord! Construisons un environnement DEV !!

En utilisant Docker & Docker Compose, vous pouvez démarrer un projet de développement complet.

Pour chaque mordule, nous avons prédéfini les installations les plus courantes possibles.

### Exigences sur le système local

* Git [git-scm.com](https://git-scm.com)
* Php Composer [getcomposer.org/](https://getcomposer.org/)
* Docker & Docker-Compose [www.docker.com/](https://www.docker.com/)
* Aucun compte d'utilisateur Splash Sync actif n'est nécessaire!

### Cloner les sources du module

Tout d’abord, vous devez télécharger les sources et compiler le module localement.

```bash
$ git clone {{ site.github.repository_url }} myModule
$ cd myModule
$ composer install --no-dev
```

### Construire tout l'environnement Docker Compose

Ensuite, construisez simplement l'environnement Docker.

```bash
$ docker-compose up
```

Cela peut prendre un certain temps, mais au final, tout votre environnement devrait fonctionner!

### Accéder à votre environnement

Pour faciliter l'accès à vos instances, nous avons figé toutes les adresses IP de conteneurs sur le fichier <code>docker-compose.yml</code>.

Dans ce fichier, vous devriez trouver un commentaire avec les adresses IP cibles de vos conteneurs d’environnement.

Par exemple, pour Prestashop:

```yaml
################################################################################
# Docker Compose File
#
# This Docker File intend to Create a Complete Dev Environment 
# for Splash Modules on Prestashop
#
# To us different PS Version coinfigured, you need to add hosts to /etc/hosts
# 
# 172.102.0.10        latest.prestashop.local
# 172.102.0.16        ps1-6.prestashop.local
# 172.102.0.17        ps1-7.prestashop.local
# 172.102.0.100       toolkit.prestashop.local
#
################################################################################
```

Donc, si vous naviguez sur IP 172.102.0.10, vous devriez trouver votre application qui fonctionne!

Tous les détails utiles de l’installation sont visibles dans le fichier <code>docker-compose.yml</code>.

Pour une utilisation plus facile, vous pouvez couper / coller toutes les adresses IP dans votre /etc/hosts. Cela rendra l'application disponible à partir de l'URL, pas seulement IP.

### Test, Develop & Debug en utilisant notre boîte à outils

Avez-vous vu le dernier conteneur disponible? C'est Splash ToolKit !!

C'est fait pour les développeurs et devrait déjà être pré-configuré pour vous Apps!

Il suffit de parcourir l'URL et de vous connecter avec l'utilisateur "admin" et le mot de passe "admin"!

![]({{ "/assets/img/splash-toolkit.png" | relative_url }})

### Vider le cache?

Si votre configuration a changéz, vous risquez d'avoir une jolie erreur ... vous devez vider le cache!

Comment? Il suffit de lancer cette commande docker:

```bash
$ docker-compose exec toolkit rm -Rf var/cache/*
```

### Testez votre connecteur localement ??

Oui, vous pouvez! Tout ce dont vous avez besoin pour exécuter notre séquence complète de tests de base est déjà fournie par Splash Toolkit.

Comment? Il suffit de lancer phpunit en utilisant cette commande docker:

```bash
$ docker-compose exec toolkit vendor/bin/phpunit
```
