# 🏦 BankOnline - Système de Gestion Bancaire

> Système de gestion bancaire complet développé en PHP/MySQL pour gérer clients, virements, prêts et remboursements.

[![PHP Version](https://img.shields.io/badge/PHP-5.5%2B-blue)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-orange)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📋 Table des Matières

- [À propos](#-à-propos)
- [Fonctionnalités](#-fonctionnalités)
- [Installation](#-installation)
- [Technologies](#-technologies)
- [Structure du Projet](#-structure-du-projet)
- [Configuration](#-configuration)
- [Utilisation](#-utilisation)
- [Contribution](#-contribution)
- [Licence](#-licence)
- [Contact](#-contact)

---

## 🎯 À propos

**BankOnline** est une application web de gestion bancaire complète qui permet de gérer efficacement les opérations courantes d'une institution financière. Développée avec PHP et MySQL, elle offre une interface intuitive et sécurisée pour les opérations bancaires quotidiennes.

### Cas d'utilisation

- 🏢 Microfinance et institutions de crédit
- 💼 Coopératives d'épargne et de crédit
- 🏦 Systèmes de gestion interne pour petites banques
- 📚 Projets académiques et formations en développement web

---

## ✨ Fonctionnalités

### 👥 Gestion des Clients
- Création et modification de comptes clients
- Génération automatique des numéros de compte (C001, C002, etc.)
- Recherche et filtrage des clients
- Gestion des soldes en temps réel

### 💸 Virements Bancaires
- Transferts d'argent entre comptes
- Validation automatique des soldes
- Historique complet des transactions
- Prévention des transferts invalides

### 💰 Gestion des Prêts
- Octroi de prêts avec calcul automatique
- Frais de gestion de 10%
- Vérification des prêts en cours
- Notifications par email (optionnel)

### 📊 Remboursements
- Gestion des remboursements partiels ou totaux
- Calcul automatique du solde restant
- Suivi du statut (Payé une part / Tout payé)
- Historique détaillé

### 📈 Tableau de Bord
- Statistiques en temps réel
- Nombre total de clients, virements, prêts
- Montants totaux et bénéfices
- Filtrage par période

### 📄 Rapports PDF
- Export PDF des transactions
- Rapports personnalisables par période
- Inclut virements, prêts et remboursements

### 🔐 Sécurité
- Authentification multi-rôles (Admin/Employé)
- Sessions PHP sécurisées
- Récupération de mot de passe par email
- Validation des données côté serveur

---


---

## 🚀 Installation

### Prérequis

- PHP 5.5 ou supérieur
- MySQL 5.6 ou supérieur
- Apache (XAMPP, WAMP, ou LAMP)
- Extensions PHP : `mysqli`, `mbstring`, `openssl`, `ctype`, `filter`, `hash`

### Installation Rapide

1. **Cloner le repository**
   ```bash
   git clone https://github.com/votre-username/bankonline.git
   cd bankonline
   ```

2. **Configurer la base de données**
   - Ouvrir phpMyAdmin : `http://localhost/phpmyadmin`
   - Créer une nouvelle base de données : `virement_pret_bancaire`
   - Importer le fichier `setup_database.sql`

3. **Configurer la connexion**
   - Ouvrir le fichier `db.php`
   - Modifier les paramètres de connexion si nécessaire :
     ```php
     $host = "localhost";
     $user = "root";
     $password = "";
     $dbname = "virement_pret_bancaire";
     ```

4. **Accéder à l'application**
   ```
   http://localhost/bankonline/login.php
   ```

5. **Se connecter**
   - Créer un premier compte via `inscription.php`
   - Format identifiant : `EMP001` (Employé) ou `ADM001` (Admin)

### Installation Détaillée

Pour une installation pas à pas complète, consultez [README_INSTALLATION.md](README_INSTALLATION.md)

---

## 🛠️ Technologies

| Technologie | Description |
|------------|-------------|
| **PHP 5.5+** | Langage backend |
| **MySQL** | Base de données relationnelle |
| **HTML5/CSS3** | Interface utilisateur |
| **JavaScript** | Interactivité côté client |
| **FPDF** | Génération de PDF |
| **PHPMailer** | Envoi d'emails |
| **Apache** | Serveur web |

---

## 📁 Structure du Projet

```
bankonline/
├── 📄 index.php                    # Redirection vers login
├── 🔐 login.php                    # Page de connexion
├── ✍️ inscription.php               # Inscription (choix du rôle)
├── 🏠 head.php                      # Page d'accueil
├── 👥 client_banq.php               # Gestion des clients
├── 💸 virement.php                  # Gestion des virements
├── 💰 preter.php                    # Gestion des prêts
├── 📊 rendre.php                    # Gestion des remboursements
├── 📈 tableau_bord.php              # Tableau de bord
├── 📄 rapport.php                   # Génération de rapports
├── 📑 export_pdf.php                # Export PDF
├── 🔑 forgot_pwd.php                # Récupération mot de passe
├── ⚙️ db.php                        # Configuration BDD
├── 🗄️ setup_database.sql           # Script SQL
├── 📚 README_INSTALLATION.md       # Guide d'installation
├── fpdf/                           # Bibliothèque FPDF
│   └── fpdf.php
├── phpmailer/                      # Bibliothèque PHPMailer (optionnel)
│   └── src/
└── css/                            # Fichiers de style
```

---

## ⚙️ Configuration

### Base de Données (db.php)

```php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "virement_pret_bancaire";
```

### Bibliothèques Externes

#### FPDF (Génération de PDF)
```bash
# Télécharger FPDF
http://www.fpdf.org/en/download.php

# Placer fpdf.php dans le dossier fpdf/
```

#### PHPMailer (Envoi d'emails - Optionnel)
```bash
# Télécharger PHPMailer
https://github.com/PHPMailer/PHPMailer/releases

# Extraire le dossier src/ dans phpmailer/
```

Pour plus de détails, consultez [README_INSTALLATION.md](README_INSTALLATION.md)

---

## 📖 Utilisation

### Créer un Compte

1. Aller sur `inscription.php`
2. Choisir le type : **Employé** ou **Administrateur**
3. Format requis :
   - Employé : `EMP001`, `EMP002`, etc.
   - Admin : `ADM001`, `ADM002`, etc.

### Gérer les Clients

1. Aller sur **Gestion des Clients**
2. Cliquer sur **+ Nouveau Client**
3. Remplir le formulaire
4. Le numéro de compte est généré automatiquement

### Effectuer un Virement

1. Aller sur **Virements**
2. Cliquer sur **+ Nouveau Virement**
3. Saisir :
   - Compte expéditeur
   - Compte bénéficiaire
   - Montant
4. Le système vérifie automatiquement le solde

### Accorder un Prêt

1. Aller sur **Prêts**
2. Cliquer sur **+ Nouveau Prêt**
3. Saisir le numéro de compte et le montant
4. Le client reçoit 90% du montant (10% de frais)
5. Email de confirmation envoyé (si configuré)

### Générer un Rapport

1. Aller sur **Rapports**
2. Sélectionner une période (date de début et fin)
3. Cliquer sur **Télécharger PDF**
4. Le rapport inclut tous les virements, prêts et remboursements

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. **Fork** le projet
2. **Créer** une branche pour votre fonctionnalité
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. **Commit** vos changements
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```
4. **Push** vers la branche
   ```bash
   git push origin feature/AmazingFeature
   ```
5. **Ouvrir** une Pull Request

### Idées de Contributions

- 🐛 Correction de bugs
- ✨ Nouvelles fonctionnalités
- 📝 Amélioration de la documentation
- 🌐 Traductions
- 🎨 Améliorations de l'interface
- 🔒 Renforcement de la sécurité

---

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 👨‍💻 Auteur

**Votre Nom**

- 🐙 GitHub: [@votre-username](https://github.com/votre-username)
- 📧 Email: votre.email@example.com
- 💼 LinkedIn: [Votre Profil](https://linkedin.com/in/votre-profil)

---

## 🙏 Remerciements

- [FPDF](http://www.fpdf.org/) - Génération de PDF
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - Envoi d'emails
- [XAMPP](https://www.apachefriends.org/) - Environnement de développement

---

## 📞 Support

Si vous rencontrez des problèmes :

1. Consultez le [guide d'installation](README_INSTALLATION.md)
2. Vérifiez les [issues existantes](https://github.com/votre-username/bankonline/issues)
3. Créez une [nouvelle issue](https://github.com/votre-username/bankonline/issues/new)

---

## 🗺️ Roadmap

- [ ] Interface responsive (mobile-friendly)
- [ ] Authentification à deux facteurs (2FA)
- [ ] API REST
- [ ] Graphiques et statistiques avancées
- [ ] Export Excel/CSV
- [ ] Multi-devises
- [ ] Notifications push
- [ ] Historique des modifications

---

<div align="center">

**⭐ Si ce projet vous a été utile, n'hésitez pas à lui donner une étoile ! ⭐**

(https://github.com/votre-username)

</div>
