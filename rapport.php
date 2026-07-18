<?php include 'db.php'; 
session_start();
if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports</title>
    <link rel="stylesheet" href="rapport.css?v=4">
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
                <li class="id"><a href="rendre.php">Remboursements</a></li>
                <li class="id"><a href="tableau_bord.php">Tableau de bord</a></li>
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

    <section class="rapport-container">
        <h1>Rapports des Transactions</h1>

        <form method="POST" action="rapport.php">
            <label for="date_debut">Date début :</label>
            <input type="date" name="date_debut" required>

            <label for="date_fin">Date fin :</label>
            <input type="date" name="date_fin" required>

            <button type="submit">Afficher le Rapport</button>
        </form>

        <?php
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $date_debut = $_POST["date_debut"];
            $date_fin = $_POST["date_fin"];

            $query_virements = "SELECT * from virement where dateTransfert between '$date_debut' and '$date_fin'";
            $result_virements = $conn->query($query_virements);

            $query_prets = "SELECT * from preter where datepret between '$date_debut' and '$date_fin'";
            $result_prets = $conn->query($query_prets);

            $query_remboursements = "SELECT * from rendre where date_rendu between '$date_debut' and '$date_fin'";
            $result_remboursements = $conn->query($query_remboursements);
        ?>

        <div class="rapport-section">
            <h2>Virements</h2>
            <table>
                <thead>
                    <tr>
                        <th>Expéditeur</th>
                        <th>Bénéficiaire</th>
                        <th>Montant</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_virements->fetch_assoc()) { ?>
                        <tr>
                            <td><?= htmlspecialchars($row["numCompte_expediteur"]) ?></td>
                            <td><?= htmlspecialchars($row["numCompte_beneficiaire"]) ?></td>
                            <td><?= number_format($row["montant"], 2) ?> Ar</td>
                            <td><?= $row["dateTransfert"] ?></td>
                        </tr>
                        <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="rapport-section">
            <h2>Prêts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Numéro Prêt</th>
                        <th>Montant</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_prets->fetch_assoc()) { ?>
                        <tr>
                            <td><?= htmlspecialchars($row["num_pret"]) ?></td>
                            <td><?= number_format($row["montant_prete"], 2) ?> Ar</td>
                            <td><?= $row["datepret"] ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="rapport-section">
            <h2>Remboursements</h2>
            <table>
                <thead>
                    <tr>
                        <th>Numéro Remboursement</th>
                        <th>Prêt</th>
                        <th>Montant</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_remboursements->fetch_assoc()) { ?>
                        <tr>
                            <td><?= htmlspecialchars($row["num_rendu"]) ?></td>
                            <td><?= htmlspecialchars($row["num_pret"]) ?></td>
                            <td><?= htmlspecialchars($row["montant_rembourse"], 2) ?> Ar</td>
                            <td><?= $row["date_rendu"] ?></td>
                        </tr>
                        <?php } ?>
                </tbody>
            </table>
        </div>

        <form method="POST" action="export_pdf.php">
            <input type="hidden" name="date_debut" value="<?= $date_debut ?>">
            <input type="hidden" name="date_fin" value="<?= $date_fin?>">
            <button type="submit">Télécharger en PDF</button>
        </form>
        <?php } ?>
    </section>
    <footer>
        <p>&copy; 2025 BankOnline - Tous droits réservés.</p>
    </footer>
</body>    
</html>