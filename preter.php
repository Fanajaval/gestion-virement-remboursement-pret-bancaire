<?php
include 'db.php';
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit();
}


function genererNumeroPret($conn) {
    $result = $conn->query("SELECT num_pret FROM preter ORDER BY num_pret DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $lastNum = $result->fetch_assoc()["num_pret"];
        $num = intval(substr($lastNum, 1)) + 1;
        return "P" . str_pad($num, 3, "0", STR_PAD_LEFT);
    } else {
        return "P001";
    }
}

if (isset($_POST['nouveau'])) {
    $num_pret = genererNumeroPret($conn); 
    $numCompte = $_POST['numCompte'];
    $montant_prete = $_POST['montant_prete'];
    $datepret = $_POST['datepret'];
    
    $check_compte = $conn->query("SELECT * FROM client WHERE numCompte = '$numCompte'");
    if ($check_compte->num_rows == 0) {
        $_SESSION['error_message'] = "Erreur : Le compte ($numCompte) n'existe pas !";
    } else {
        $check_dette = $conn->query("SELECT * FROM preter WHERE numCompte = '$numCompte' AND montant_prete > 0");
        if ($check_dette->num_rows > 0) {
            $_SESSION['error_message'] = "Erreur : Le client a déjà un prêt non remboursé !";
        } else {
            $sql = "INSERT INTO preter (num_pret, numCompte, montant_prete, datepret) VALUES ('$num_pret', '$numCompte', '$montant_prete', '$datepret')";
            if ($conn->query($sql) === TRUE) {
                $conn->query("UPDATE client SET solde = solde + (($montant_prete*90)/100) WHERE numCompte = '$numCompte'");

                if (file_exists(__DIR__ . '/phpmailer/src/PHPMailer.php')) {
                    require __DIR__ . '/phpmailer/src/PHPMailer.php';
                    require __DIR__ . '/phpmailer/src/SMTP.php';
                    require __DIR__ . '/phpmailer/src/Exception.php';
                    
                    try {
                        $datepret_obj = new DateTime($datepret);
                        $date_fin_remboursement = clone $datepret_obj;
                        $date_fin_remboursement->add(new DateInterval('P30D'));
                        $days_left = (new DateTime())->diff($date_fin_remboursement)->days;

                        $queryClient = "SELECT mail, nom FROM client WHERE numCompte = '$numCompte'";
                        $resultClient = $conn->query($queryClient);
                        
                        if ($resultClient && $resultClient->num_rows > 0) {
                            $client = $resultClient->fetch_assoc();
                            $emailClient = $client['mail'];
                            $nomClient = $client['nom'];

                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                            
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'andriatoavimanana@gmail.com';
                            $mail->Password = 'qndj ezsu rmea pyvk'; 
                            $mail->SMTPSecure = 'tls';
                            $mail->Port = 587;
                            $mail->CharSet = 'UTF-8';
                          
                            $mail->setFrom($mail->Username, 'BankOnline');
                            $mail->addAddress($emailClient, $nomClient);
                            $mail->Subject = "Confirmation de votre prêt - BankOnline";
                            
                            $message = "Bonjour $nomClient,\n\n";
                            $message .= "Nous avons le plaisir de vous informer que votre demande de prêt a été approuvée.\n\n";
                            $message .= "Détails du prêt :\n";
                            $message .= "- Numéro de prêt : $num_pret\n";
                            $message .= "- Montant du prêt : " . number_format($montant_prete, 2) . " Ar\n";
                            $message .= "- Montant crédité (90%) : " . number_format($montant_prete * 0.90, 2) . " Ar\n";
                            $message .= "- Frais de gestion (10%) : " . number_format($montant_prete * 0.10, 2) . " Ar\n";
                            $message .= "- Date d'octroi : $datepret\n";
                            $message .= "- Date limite de remboursement : " . $date_fin_remboursement->format('Y-m-d') . "\n";
                            $message .= "- Jours restants : $days_left jours\n\n";
                            $message .= "Le montant a été crédité sur votre compte.\n";
                            $message .= "Merci de respecter les délais de remboursement.\n\n";
                            $message .= "Cordialement,\nL'équipe BankOnline";
                            
                            $mail->Body = $message;
                            $mail->send();
                        }
                    } catch (\Exception $e) {
                        //erreur d'envoi d'email
                    }
                }
                
                $_SESSION['success_message'] = "Prêt accordé avec succès !";
            } else {
                $_SESSION['error_message'] = "Erreur SQL : " . $conn->error;
            }

        }
    }
    header("Location: preter.php");
    exit();
}

if (isset($_GET['supprimer_pret']) && isset($_GET['supprimer_compte'])) {
    $numPret = strtoupper($_GET['supprimer_pret']);
    $numCompte = strtoupper($_GET['supprimer_compte']);
    
    // Vérifier si des remboursements existent pour ce prêt
    $queryRemboursement = "
        SELECT r.* FROM rendre r
        INNER JOIN preter p ON r.num_pret = p.num_pret
        WHERE p.numCompte = ? 
        AND p.num_pret = ?
    ";
    $stmtRemboursement = $conn->prepare($queryRemboursement);
    
    if ($stmtRemboursement === false) {
        $_SESSION['error_message'] = "Erreur de préparation de la requête : " . $conn->error;
        header("Location: preter.php");
        exit();
    }
    
    $stmtRemboursement->bind_param("ss", $numCompte, $numPret);
    $stmtRemboursement->execute();
    $resultRemboursement = $stmtRemboursement->get_result();

    if ($resultRemboursement->num_rows > 0) {
        $_SESSION['error_message'] = "Erreur : Impossible de supprimer ce prêt, il a déjà été partiellement ou totalement remboursé.";
    } else {
        $querySolde = "SELECT solde FROM client WHERE numCompte = ?";
        $stmtSolde = $conn->prepare($querySolde);
        
        if ($stmtSolde === false) {
            $_SESSION['error_message'] = "Erreur de préparation de la requête : " . $conn->error;
            header("Location: preter.php");
            exit();
        }
        
        $stmtSolde->bind_param("s", $numCompte);
        $stmtSolde->execute();
        $resultSolde = $stmtSolde->get_result();
        $client = $resultSolde->fetch_assoc();
        $soldeClient = $client['solde'];

        $queryPret = "SELECT montant_prete FROM preter WHERE num_pret = ?";
        $stmtPret = $conn->prepare($queryPret);
        
        if ($stmtPret === false) {
            $_SESSION['error_message'] = "Erreur de préparation de la requête : " . $conn->error;
            header("Location: preter.php");
            exit();
        }
        
        $stmtPret->bind_param("s", $numPret);
        $stmtPret->execute();
        $resultPret = $stmtPret->get_result();
        $pret = $resultPret->fetch_assoc();
        $montantPret = $pret['montant_prete'];

        if (($soldeClient - 0.9 * $montantPret) < 0) {
            $_SESSION['error_message'] = "Erreur : La suppression de ce prêt entraînerait un solde négatif pour ce client.";
        } else {
            $sqlSuppression = "DELETE FROM preter WHERE num_pret = ?";
            $stmtSuppression = $conn->prepare($sqlSuppression);
            
            if ($stmtSuppression === false) {
                $_SESSION['error_message'] = "Erreur de préparation de la requête : " . $conn->error;
                header("Location: preter.php");
                exit();
            }
            
            $stmtSuppression->bind_param("s", $numPret);
            if ($stmtSuppression->execute()) {
                $sqlUpdateSolde = "UPDATE client SET solde = solde - (0.90 * ?) WHERE numCompte = ?";
                $stmtUpdateSolde = $conn->prepare($sqlUpdateSolde);
                
                if ($stmtUpdateSolde === false) {
                    $_SESSION['error_message'] = "Erreur de préparation de la requête : " . $conn->error;
                    header("Location: preter.php");
                    exit();
                }
                
                $stmtUpdateSolde->bind_param("ds", $montantPret, $numCompte);
                $stmtUpdateSolde->execute();
                
                $_SESSION['success_message'] = "Prêt supprimé avec succés et solde mis à jour.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la suppression du prêt : " . $conn->error;
            }
        }
    }

    header("Location: preter.php");
    exit();
}

$result = $conn->query("SELECT * FROM preter ORDER BY datepret ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Prêts</title>
    <link rel="stylesheet" href="preter.css?v=4">
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
                <li class="id"><a href="virement.php">Virements</a></li>
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
        <h1>Gestion des Prêts</h1>
        <button class="btn add-btn" onclick="document.getElementById('addPretModal').style.display='block'">+ Nouveau Prêt</button>

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
                        <th>Numéro du prêt</th>
                        <th>Numéro de compte</th>
                        <th>Montant prêté</th>
                        <th>Bénéfice Banque (10%)</th>
                        <th>Date du prêt</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars(strtoupper($row["num_pret"])) ?></td>
                            <td><?= htmlspecialchars(strtoupper($row["numCompte"])) ?></td>
                            <td><?= htmlspecialchars($row["montant_prete"]) ?></td>
                            <td><?= htmlspecialchars($row["montant_prete"] * 0.10) ?></td>
                            <td><?= $row["datepret"] ?></td>
                            <td class="actions">
                                <a href="modifier_pret.php?num_pret=<?= $row["num_pret"] ?>&numCompte=<?= $row["numCompte"] ?>"><button class="edit-btn">Modifier</button></a>
                                <a href="preter.php?supprimer_pret=<?= $row["num_pret"] ?>&supprimer_compte=<?= $row["numCompte"] ?>" onclick="return confirm('Confirmer la suppression ?')"><button class="delete-btn">Supprimer</button></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-message">Aucun prêt enregistré</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>

    <div id="addPretModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addPretModal').style.display='none'">&times;</span>
            <h2>Accorder un prêt</h2>
            <form id="preterForm" action="preter.php" method="post">
                <label>Numéro de compte</label>
                <input type="text" name="numCompte" required>

                <label>Montant prêté</label>
                <input type="number" name="montant_prete" required>

                <label>Date</label>
                <input type="date" name="datepret" required>

                <button type="submit" name="nouveau" class="btn">Valider</button>
            </form>
        </div>
    </div>
    <footer>
        <p>&copy; 2025 BankOnline - Tous droits réservés.</p>
    </footer>
</html>