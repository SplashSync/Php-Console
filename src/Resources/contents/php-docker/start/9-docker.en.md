---
lang: en
permalink: start/docker
title: Test Environment (Docker) 
---

You want to try this module? Ajust it to your needs? Or develop new features? Ok! Let's build a DEV environment!!

Using Docker & Docker Compose, you can start a full developper project. 

For each mordules, we predefined most common possible installations.

### Requirements on Local System

* Git [git-scm.com](https://git-scm.com)
* Php Composer [getcomposer.org/](https://getcomposer.org/)
* Docker & Docker-Compose [www.docker.com/](https://www.docker.com/)
* No active Splash Sync User Account is needed!

### Clone Module Sources

First, you need to download the sources and compile the module locally.

```bash
$ git clone {{ site.github.repository_url }} myModule
$ cd myModule
$ composer install --no-dev
```

### Build whole Docker Compose Environment

Then, just build the docker environment.

```bash
$ docker compose up
```

This may take a while but in the end, you should have all your environment working!

### Access your Environment

To facilitate access to your instances, we fixed all containers IPs on <code>docker-compose.yml</code> file.

In this file, you should find a comment with target IPs of your environment containers.

I.e. for Prestashop:

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

So, if you browse IP 172.102.0.10, you should find your App working!!

All usefull details of the installtion are visible on <code>docker-compose.yml</code> file.

For an easier usage, you can cut/paste all IPs to your /etc/hosts. This will make the App available from Url, not only IP.

### Test, Develop & Debug using our toolkit

Have you seen this last container available? It's Splash ToolKit!! 

It's done for developpers and should already be pre-setuped for you Apps!

Just browse the url, and log with user "admin" and password "admin"!

![]({{ "/assets/img/splash-toolkit.png" | relative_url }})


