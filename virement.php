<?php
include 'db.php';
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['nouveau'])) {
    $numCompte_expediteur = $_POST['numCompte_expediteur'];
    $numCompte_beneficiaire = $_POST['numCompte_beneficiaire'];
    $montant = $_POST['montant'];
    $dateTransfert = $_POST['dateTransfert'];

    $check_expediteur = $conn->query("SELECT * FROM client WHERE numCompte ='$numCompte_expediteur'");
    $check_beneficiaire = $conn->query("SELECT * FROM client WHERE numCompte ='$numCompte_beneficiaire'");

    if($check_expediteur->num_rows == 0) {
        $_SESSION['error_message'] = "Erreur : Le compte expéditeur ($numCompte_expediteur) n'existe pas !";
    } elseif($check_beneficiaire->num_rows == 0) {
        $_SESSION['error_message'] = "Erreur : Le compte bénéficiaire ($numCompte_beneficiaire) n'existe pas !";
    } elseif($numCompte_expediteur == $numCompte_beneficiaire) {
        $_SESSION['error_message'] = "Erreur : Un virement ne peut pas être fait sur le même compte !";
    } else {
        $data_expediteur = $check_expediteur->fetch_assoc();
        $data_beneficiaire = $check_beneficiaire->fetch_assoc();

        if ($data_expediteur['solde'] < $montant) {
            $_SESSION['error_message'] = "Erreur : Solde insuffisant pour effectuer ce virement !";
        } else {
            $conn->begin_transaction();
            try {
                // mise a jour soldes
                $nouveau_solde_expediteur = $data_expediteur['solde'] - $montant;
                $conn->query("UPDATE client SET solde = '$nouveau_solde_expediteur' WHERE numCompte = '$numCompte_expediteur'");

                $nouveau_solde_beneficiaire = $data_beneficiaire['solde'] + $montant;
                $conn->query("UPDATE client SET solde = '$nouveau_solde_beneficiaire' WHERE numCompte = '$numCompte_beneficiaire'");

                // insertion du virement
                $stmt = $conn->prepare("INSERT INTO virement (numCompte_expediteur, numCompte_beneficiaire, montant, dateTransfert) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssds", $numCompte_expediteur, $numCompte_beneficiaire, $montant, $dateTransfert);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Virement effectué avec succès !";
                    $conn->commit();
                } else {
                    throw new Exception("Erreur SQL lors de l'insertion du virement : " . $stmt->error);
                }

                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Transaction annulée : " . $e->getMessage();
            }

            header("Location: virement.php");
            exit();
        }
    }
}

if(isset($_GET['supprimer_expediteur']) && isset($_GET['supprimer_beneficiaire'])) {
    $numCompte_expediteur = $_GET['supprimer_expediteur'];
    $numCompte_beneficiaire = $_GET['supprimer_beneficiaire'];

    $result = $conn->query("SELECT montant FROM virement WHERE numCompte_expediteur = '$numCompte_expediteur' AND numCompte_beneficiaire = '$numCompte_beneficiaire'");
    $virement = $result->fetch_assoc();

    if ($virement) {
        $montant = $virement['montant'];

        $expediteur = $conn->query("SELECT solde FROM client WHERE numCompte = '$numCompte_expediteur'")->fetch_assoc();
        $beneficiaire = $conn->query("SELECT solde FROM client WHERE numCompte = '$numCompte_beneficiaire'")->fetch_assoc();

        if ($expediteur) {
            $solde_expediteur_nouveau = $expediteur['solde'] + $montant;
            $conn->query("UPDATE client SET solde = '$solde_expediteur_nouveau' WHERE numCompte = '$numCompte_expediteur'");
        }

        if ($beneficiaire) {
            $solde_beneficiaire_nouveau = $beneficiaire['solde'] - $montant;
            $conn->query("UPDATE client SET solde = '$solde_beneficiaire_nouveau' WHERE numCompte = '$numCompte_beneficiaire'");
        }

        $stmt = $conn->prepare("DELETE FROM virement WHERE numCompte_expediteur = ? AND numCompte_beneficiaire = ?");
        $stmt->bind_param("ss", $numCompte_expediteur, $numCompte_beneficiaire);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Virement supprimé avec succès !"; 
        } else {
            $_SESSION['error_message'] = "Erreur lors de la suppression : " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Erreur : Virement introuvable !";
    }

    header("Location: virement.php");
    exit();
}



$result = $conn->query("SELECT*FROM virement order by dateTransfert asc");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Virements</title>
    <link rel="stylesheet" href="virement.css?v=4">
    <style>
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
            font-weight: bold;
        }
        .alert.error {
            background-color: #f44336;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">Bank<span>Online</span></div>
        <button class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav>   
            <ul class="menu" id="menu">
                <li class="id"><a href="head.php">Accueil</a></li>
                <li class="id"><a href="client_banq.php">Clients</a></li>
                <li class="id"><a href="preter.php">Prêts</a></li>
                <li class="id"><a href="rendre.php">Remboursements</a></li>
                <li class="id"><a href="tableau_bord.php">Tableau de bord</a></li>
                <li class="id"><a href="rapport.php">Rapports</a></li>
                <li class="id"><a href="logout.php" class="btn">Se Déconnecter</a></li>
            </ul>
        </nav>
    </header>
    <script>
        document.getElementById('hamburger').addEventListener('click', function() {
            document.getElementById('menu').classList.toggle('active');
            this.classList.toggle('active');
        });
    </script>

    <section class="container">
        <h1>Gestion des Virements</h1>
        <button class="btn add-btn" onclick="document.getElementById('addVirementModal').style.display='block'">+ Nouveau Virement</button>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert" id="successAlert"><?= $_SESSION['success_message']; ?></div>
            <script>
                document.getElementById("successAlert").style.display = "block";
                setTimeout(() => {
                    document.getElementById("successAlert").style.display = "none";
                }, 3000);
            </script>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert error" id="errorAlert"><?= $_SESSION['error_message']; ?></div>
            <script>
                document.getElementById("errorAlert").style.display = "block";
                setTimeout(() => {
                    document.getElementById("errorAlert").style.display = "none";
                }, 5000);
            </script>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Expéditeur (N° Compte)</th>
                        <th>Bénéficiaire (N° Compte)</th>
                        <th>Montant</th>
                        <th>Date de Transfert</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars(strtoupper($row["numCompte_expediteur"])) ?></td>
                    <td><?= htmlspecialchars(strtoupper($row["numCompte_beneficiaire"])) ?></td>
                    <td><?= htmlspecialchars($row["montant"]) ?></td>
                    <td><?= htmlspecialchars($row["dateTransfert"]) ?></td>
                    <td class="actions">
                        <a href="modifier_virement.php?numCompte_expediteur=<?= $row["numCompte_expediteur"] ?>&numCompte_beneficiaire=<?=$row["numCompte_beneficiaire"] ?>">
                            <button class="edit-btn">Modifier</button>
                        </a>
                        <a href="virement.php?supprimer_expediteur=<?= $row["numCompte_expediteur"] ?>&supprimer_beneficiaire=<?=$row["numCompte_beneficiaire"] ?>" 
                            onclick="return confirm('Confirmer la suppression ?')">
                            <button class="delete-btn">Supprimer</button>
                        </a>
                        <a href="generer_pdf.php?numCompte_expediteur=<?= $row["numCompte_expediteur"] ?>&numCompte_beneficiaire=<?= $row["numCompte_beneficiaire"] ?>">
                            <button class="pdf">Télécharger PDF</button>
                        </a>
                    </td>
                    
                </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-message">Aucun virement enregistré</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>

    <div id="addVirementModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addVirementModal').style.display='none'">&times;</span>
            <h2>Effectuer un virement</h2>
            <form id="virementForm" action="virement.php" method="post">
                <label>Numéro de compte expéditeur</label>
                <input type="text" name="numCompte_expediteur" required>

                <label>Numéro de compte bénéficiaire</label>
                <input type="text" name="numCompte_beneficiaire" required>

                <label>Montant</label>
                <input type="number" name="montant" required>

                <label>Date</label>
                <input type="date" name="dateTransfert" required>

                <button type="submit" name="nouveau" class="btn">Valider</button>
            </form>
        </div>
    </div>
    <footer>
        <p>&copy; 2025 BankOnline - Tous droits réservés.</p>
    </footer>
</body>
</html>