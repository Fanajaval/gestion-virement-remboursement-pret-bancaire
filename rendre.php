<?php
include 'db.php';
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit();
}

function genererNumeroRendu($conn) {
    $result = $conn->query("SELECT num_rendu FROM rendre ORDER BY num_rendu DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $lastNum = $result->fetch_assoc()["num_rendu"];
        $num = intval(substr($lastNum, 1)) + 1;
        return "R" . str_pad($num, 3, "0", STR_PAD_LEFT);
    } else {
        return "R001";
    }
}

if(isset($_POST['nouveau'])) {
    $num_rendu = genererNumeroRendu($conn);
    $num_pret = $_POST['num_pret'];
    $montant_rembourse = $_POST['montant_rembourse'];
    $date_rendu = $_POST['date_rendu'];

    $check = $conn->query("SELECT montant_prete, numCompte FROM preter WHERE num_pret = '$num_pret'");

    if ($check->num_rows == 0) {
        $_SESSION['error_message'] = "Erreur : Le prêt ($num_pret) n'existe pas !";
    } else {
        $data = $check->fetch_assoc();
        $montant_total_pret = $data['montant_prete'];
        $numCompte = $data['numCompte'];

        $rembourse_total = $conn->query("SELECT SUM(montant_rembourse) AS total FROM rendre WHERE num_pret = '$num_pret'");
        $rembourse_data = $rembourse_total->fetch_assoc();
        $total_rembourse = $rembourse_data['total'] + $montant_rembourse;

        if ($total_rembourse > $montant_total_pret) {
            $_SESSION['error_message'] = "Erreur : Le remboursement dépasse le montant total du prêt !";
        } else {
            $solde_client = $conn->query("SELECT solde FROM client WHERE numCompte = '$numCompte'")->fetch_assoc()['solde'];

            if ($solde_client < $montant_rembourse) {
                $_SESSION['error_message'] = "Erreur : Solde insuffisant pour effectuer ce remboursement !";
            } else {
                $sql = "INSERT INTO rendre (num_rendu, num_pret, montant_rembourse, date_rendu) 
                        VALUES ('$num_rendu', '$num_pret', '$montant_rembourse', '$date_rendu')";

                if ($conn->query($sql) === TRUE) {
                    $conn->query("UPDATE client SET solde = solde - $montant_rembourse WHERE numCompte = '$numCompte'");

                    $rest_paye = max(0, $montant_total_pret - $total_rembourse);
                    $situation = ($rest_paye == 0) ? "Tout payé" : "Payé une part";

                    if (file_exists(__DIR__ . '/phpmailer/src/PHPMailer.php')) {
                        require __DIR__ . '/phpmailer/src/PHPMailer.php';
                        require __DIR__ . '/phpmailer/src/SMTP.php';
                        require __DIR__ . '/phpmailer/src/Exception.php';
                        
                        try {
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
                                $mail->Subject = "Confirmation de remboursement - BankOnline";
                                
                                $message = "Bonjour $nomClient,\n\n";
                                $message .= "Nous confirmons la réception de votre remboursement.\n\n";
                                $message .= "Détails du remboursement :\n";
                                $message .= "- Numéro de remboursement : $num_rendu\n";
                                $message .= "- Numéro de prêt : $num_pret\n";
                                $message .= "- Montant remboursé : " . number_format($montant_rembourse, 2) . " Ar\n";
                                $message .= "- Date du remboursement : $date_rendu\n\n";
                                $message .= "Situation du prêt :\n";
                                $message .= "- Montant total du prêt : " . number_format($montant_total_pret, 2) . " Ar\n";
                                $message .= "- Total remboursé : " . number_format($total_rembourse, 2) . " Ar\n";
                                $message .= "- Montant restant à payer : " . number_format($rest_paye, 2) . " Ar\n";
                                $message .= "- Statut : $situation\n\n";
                                
                                if ($rest_paye == 0) {
                                    $message .= "🎉 Félicitations ! Vous avez entièrement remboursé votre prêt.\n";
                                    $message .= "Merci pour votre confiance en BankOnline.\n\n";
                                } else {
                                    $message .= "Il vous reste encore " . number_format($rest_paye, 2) . " Ar à rembourser.\n";
                                    $message .= "Merci de continuer à respecter les délais de remboursement.\n\n";
                                }
                                
                                $message .= "Cordialement,\nL'équipe BankOnline";
                                
                                $mail->Body = $message;
                                $mail->send();
                            }
                        } catch (\Exception $e) {
                            //Erreur d'envoi d'email
                        }
                    }
                    
                    $_SESSION['success_message'] = "Remboursement effectué avec succès !";
                } else {
                    $_SESSION['error_message'] = "Erreur SQL : " . $conn->error;
                }
            }
        }
    }
    header("Location: rendre.php");
    exit();
}

if(isset($_GET['supprimer_rendu']) && isset($_GET['supprimer_pret'])) {
    $num_rendu = $_GET['supprimer_rendu'];
    $num_pret = $_GET['supprimer_pret'];

    $remboursement_info = $conn->query("SELECT montant_rembourse, num_pret FROM rendre WHERE num_rendu = '$num_rendu' AND num_pret = '$num_pret'")->fetch_assoc();
    $montant_rembourse = $remboursement_info['montant_rembourse'];

    $pret_info = $conn->query("SELECT numCompte FROM preter WHERE num_pret = '$num_pret'")->fetch_assoc();
    $numCompte = $pret_info['numCompte'];

    $stmt = $conn->prepare("DELETE FROM rendre WHERE num_rendu = ? AND num_pret = ?");
    $stmt->bind_param("ss", $num_rendu, $num_pret);

    if($stmt->execute()){
        $conn->query("UPDATE client SET solde = solde + $montant_rembourse WHERE numCompte = '$numCompte'");

        $_SESSION['success_message'] = "Remboursement supprimé et solde du client réajusté !";
    } else {
        $_SESSION['error_message'] = "Erreur lors de la suppression du remboursement !'";
    }
    header("Location: rendre.php");
    exit();
    $stmt->close();
}

$result = $conn->query("SELECT * FROM rendre ORDER BY date_rendu ASC");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Remboursements</title>
    <link rel="stylesheet" href="rendre.css?v=3">
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
                <li class="id"><a href="preter.php">Prêts</a></li>
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
        <h1>Gestion des Remboursements</h1>
        <button class="btn add-btn" onclick="document.getElementById('addRemboursementModal').style.display='block'">+ Nouveau Remboursement</button>
        
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
                        <th>Numéro du remboursement</th>
                        <th>Numéro du prêt</th>
                        <th>Montant rembousé</th>
                        <th>Situation</th>
                        <th>Montant restant</th>
                        <th>Date du remboursement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        // Calculer le montant total du prêt et le total remboursé
                        $num_pret = $row["num_pret"];
                        $query_pret = $conn->query("SELECT montant_prete FROM preter WHERE num_pret = '$num_pret'");
                        $montant_pret = 0;
                        if ($query_pret && $query_pret->num_rows > 0) {
                            $montant_pret = $query_pret->fetch_assoc()['montant_prete'];
                        }
                        
                        $query_total_rembourse = $conn->query("SELECT SUM(montant_rembourse) AS total FROM rendre WHERE num_pret = '$num_pret'");
                        $total_rembourse = 0;
                        if ($query_total_rembourse && $query_total_rembourse->num_rows > 0) {
                            $total_rembourse = $query_total_rembourse->fetch_assoc()['total'];
                        }
                        
                        $rest_paye = max(0, $montant_pret - $total_rembourse);
                        $situation = ($rest_paye == 0) ? "Tout payé" : "Payé une part";
                    ?>
                <tr>
                    <td><?= htmlspecialchars($row["num_rendu"]) ?></td>
                    <td><?= htmlspecialchars($row["num_pret"]) ?></td>
                    <td><?= htmlspecialchars($row["montant_rembourse"]) ?> Ar</td>
                    <td><?= htmlspecialchars($situation) ?></td>
                    <td><?= htmlspecialchars(number_format($rest_paye, 2)) ?> Ar</td>
                    <td><?= htmlspecialchars($row["date_rendu"]) ?></td>
                    <td class="actions">
                        <a href="modifier_remboursement.php?num_rendu=<?= $row["num_rendu"] ?>&num_pret=<?= $row["num_pret"]?>"><button class="edit-btn">Modifier</button></a>
                        <a href="rendre.php?supprimer_rendu=<?= $row["num_rendu"] ?>&supprimer_pret=<?=$row["num_pret"] ?>" onclick="return confirm('Confirmer la suppression ?')"><button class="delete-btn">Supprimer</button></a>
                    </td>
                    
                </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-message">Aucun remboursement enregistré</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>

    <div id="addRemboursementModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addRemboursementModal').style.display='none'">&times;</span>
            <h2>Effectuer un remboursement</h2>
            <form id="rendreForm" action="rendre.php" method="post">
                <label>Numéro du prêt</label>
                <input type="text" name="num_pret" required>

                <label>Montant remboursé</label>
                <input type="number" name="montant_rembourse" required>

                <label>Date du remboursement</label>
                <input type="date" name="date_rendu" required>

                <button type="submit" name="nouveau" class="btn">Valider</button>
            </form>
        </div>
    </div>
    <footer>
        <p>&copy; 2025 BankOnline - Tous droits réservés.</p>
    </footer>
</html>