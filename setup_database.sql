-- ============================================
-- Script de création des tables (à importer dans une base déjà créée)
-- Projet : Gestion de virements et prêts bancaires
-- Version : PRODUCTION (sans données de test)
-- ============================================

-- La base est créée par l'hébergeur (ou sélectionnée dans phpMyAdmin).

-- ============================================
-- Table : utilisateur
-- ============================================
CREATE TABLE utilisateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenoms VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('client', 'employe', 'admin') DEFAULT 'client',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- Table : client
-- ============================================
CREATE TABLE client (
    numCompte VARCHAR(10) PRIMARY KEY,
    Nom VARCHAR(100) NOT NULL,
    Prenoms VARCHAR(100) NOT NULL,
    Tel VARCHAR(15) NOT NULL,
    mail VARCHAR(150) NOT NULL,
    solde DECIMAL(15,2) DEFAULT 0.00,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- Table : virement
-- ============================================
CREATE TABLE virement (
    numTransfert INT AUTO_INCREMENT PRIMARY KEY,
    numCompte_expediteur VARCHAR(10) NOT NULL,
    numCompte_beneficiaire VARCHAR(10) NOT NULL,
    montant DECIMAL(15,2) NOT NULL,
    dateTransfert DATE NOT NULL,
    description TEXT,
    FOREIGN KEY (numCompte_expediteur) REFERENCES client(numCompte) ON DELETE CASCADE,
    FOREIGN KEY (numCompte_beneficiaire) REFERENCES client(numCompte) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- Table : preter (prêts)
-- ============================================
CREATE TABLE preter (
    num_pret INT AUTO_INCREMENT PRIMARY KEY,
    numCompte VARCHAR(10) NOT NULL,
    montant_prete DECIMAL(15,2) NOT NULL,
    datepret DATE NOT NULL,
    taux_interet DECIMAL(5,2) DEFAULT 5.00,
    duree_mois INT NOT NULL,
    statut ENUM('en_cours', 'rembourse', 'en_retard') DEFAULT 'en_cours',
    FOREIGN KEY (numCompte) REFERENCES client(numCompte) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- Table : rendre (remboursements)
-- ============================================
CREATE TABLE rendre (
    num_rendu INT AUTO_INCREMENT PRIMARY KEY,
    num_pret INT NOT NULL,
    montant_rembourse DECIMAL(15,2) NOT NULL,
    date_rendu DATE NOT NULL,
    FOREIGN KEY (num_pret) REFERENCES preter(num_pret) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- Table : password_reset_tokens
-- ============================================
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expiration DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- BASE DE DONNÉES PRÊTE À L'EMPLOI
-- ============================================

SELECT 'Base de données créée avec succès!' AS Status;
SELECT 'Tables créées : utilisateur, client, virement, preter, rendre, password_reset_tokens' AS Info;
SELECT 'Vous pouvez maintenant créer vos utilisateurs via l\'interface d\'inscription' AS Instructions;
