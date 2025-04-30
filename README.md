
# 🌐 CMS - Younes

  

Bienvenue sur le projet **CMS - Younes** :

Un système de gestion de contenu développé avec **Symfony 7.2.3**, **PHP 8.3.15** et **MySQL**.

  

Ce CMS permet à des utilisateurs de publier, modérer et organiser des articles selon des rôles définis.

>  *Projet réalisé dans un cadre scolaire.*

  

---

  

## ⚙️ Versions utilisées

  

- **PHP** : `8.3.15`

- **Symfony** : `7.2.3`

- **Composer** : `2.8.5`

- **Base de données** : MySQL (Doctrine ORM)

  

---

  

## ✅ Prérequis

  

Assurez-vous d’avoir installé :

  

- PHP 8.3 **avec l’extension**  `pdo_mysql`

- Composer (**v2.8.5** ou plus)

- Symfony CLI

- Un serveur MySQL opérationnel *(XAMPP, WAMP, MAMP...)*

  

---

  

## 🚀 Installation rapide

  

### 1. Clonage du projet

  

git clone https://github.com/YougatagaPY/CMS

cd CMS

  
  
  

### 2. Installation des dépendances

  

composer install

  
  
  

### 3. Configuration de la base de données

  

Modifiez le fichier `.env` ou créez un fichier `.env.local` :

  

DATABASE_URL="mysql://root:motdepasse@127.0.0.1:3306/cms_younes"

  
  

> Remplacez **motdepasse** par le mot de passe réel de votre base MySQL.

  

### 4. Création de la base de données et des tables

  

php bin/console doctrine:database:create

php bin/console doctrine:migrations:migrate

  
  

### 5. Lancement du serveur

  

symfony server:start

  
  

---

  

## 👤 Gestion des utilisateurs

  

Ce CMS fonctionne avec un système de rôles :

  

| Rôle | Description |

|------------------|------------------------------------------------------------------|

|  `ROLE_ADMIN`  | Peut publier, modifier, supprimer **tous** les articles |

|  `ROLE_REDACTEUR`  | Peut créer des articles, mais ceux-ci nécessitent une validation |

|  *Aucun rôle*  | Utilisateur sans droits de publication pas d'accès au panel admin |

  

**Exemples d’utilisateurs à créer :**

- `admin@gmail.com` → `["ROLE_ADMIN"]`

- `redacteur@gmail.com` → `["ROLE_REDACTEUR"]`

- `younesBogoss@gmail.com` → `[]`

  

---

  

## 🛡️ Système de validation

  

- Les **admins** peuvent valider, publier ou supprimer les articles.

- Les **rédacteurs** peuvent créer des articles, images et galeries mais ceux-ci sont en attente de validation.

- Les **utilisateurs sans rôle** peuvent consulter les articles publiés, mais ne peuvent pas publier.

  

---

  


  

> Développé par **Younes** – Projet Symfony – 2025
