<?php 
include 'db.php';
session_start();
if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit();
}

$total_clients = $conn->query("SELECT COUNT(*) as total from client")->fetch_assoc()['total'];

$virements = $conn->query("SELECT COUNT(*) as total, sum(montant) as total_montant from virement")->fetch_assoc();
$total_virements = $virements['total'] ?? 0;
$montant_virements = $virements['total_montant'] ?? 0;

$prets = $conn->query("SELECT count(*) as total, sum(montant_prete) as total_montant from preter")->fetch_assoc();
$total_prets = $prets['total'] ?? 0;
$montant_prets = $prets['total_montant'] ?? 0;

$benefice_banque = $montant_prets * 0.10;

$remboursements = $conn->query("SELECT sum(montant_rembourse) as total from rendre")->fetch_assoc();
$montant_rembourse = $remboursements['total'] ?? 0;

$montant_restant = $montant_prets - $montant_rembourse;

$list_prets = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["date_debut"]) && isset($_POST["date_fin"])) {
    $date_debut = $_POST["date_debut"];
    $date_fin = $_POST["date_fin"];

    $query = "
        SELECT p.num_pret, p.montant_prete, p.datepret
        FROM preter p
        WHERE p.datepret BETWEEN '$date_debut' AND '$date_fin'
    ";
    $result = $conn->query($query);

    if ($result === false) {
        echo "<p>Erreur lors de l'exécution de la requête : " . $conn->error . "</p>";
    } elseif ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $list_prets[] = $row;
        }
    } else {
        echo "<p>Aucun prêt trouvé dans cette période.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord</title>
    <link rel="stylesheet" href="tableau_bord.css?v=4">
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

    <div class="container">
        <h1>Tableau de Bord</h1>
        <div class="stats">
            <div class="card">
                <h3>Nombre Total de Clients</h3>
                <p id="totalClients"><?= $total_clients ?></p>
            </div>
            <div class="card">
                <h3>Nombre Total de Virements</h3>
                <p id="totalVirements"><?= $total_virements ?></p>
            </div>
            <div class="card">
                <h3>Montant Total des Virements</h3>
                <p id="montantVirements"><?= $montant_virements ?> Ar</p>
            </div>
            <div class="card">
                <h3>Nombre Total de Prêts</h3>
                <p id="totalPrets"><?= $total_prets ?></p>
            </div>
            <div class="card">
                <h3>Montant Total des Prêts</h3>
                <p id="montantPrets"><?= $montant_prets ?> Ar</p>
            </div>
            <div class="card">
                <h3>Bénéfice Accumulé (10%)</h3>
                <p id="benefice"><?= $benefice_banque ?> Ar</p>
            </div>
            <div class="card">
                <h3>Montant Total des Remboursements</h3>
                <p id="montantRemboursements"><?= $montant_rembourse ?> Ar</p>
            </div>
            <div class="card">
                <h3>Montant Restant à Rembourser</h3>
                <p id="resteRembourser"><?= $montant_restant ?> Ar</p>
            </div>
        </div>

        <h2>Liste des Prêts</h2>
        <form method="POST" class="date-filter">
            <label for="date_debut">Date de début :</label>
            <input type="date" name="date_debut" required>
            <label for="date_fin">Date de fin :</label>
            <input type="date" name="date_fin" required>
            <button type="submit">Filtrer</button>
        </form>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Numéro du Prêt</th>
                        <th>Montant</th>
                        <th>Situation</th>
                        <th>Restant à Payer</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="tablePrets">
    <?php
foreach ($list_prets as $pret) {
    $num_pret = $pret['num_pret'];
    $montant_prete = $pret['montant_prete'];
    
    // Calculer le total remboursé pour ce prêt
    $query_remboursements = "SELECT SUM(montant_rembourse) as total_rembourse FROM rendre WHERE num_pret = '$num_pret'";
    $result_remboursements = $conn->query($query_remboursements);
    
    $montant_rembourse = 0;
    if ($result_remboursements && $result_remboursements->num_rows > 0) {
        $row_remboursements = $result_remboursements->fetch_assoc();
        $montant_rembourse = $row_remboursements['total_rembourse'] ?? 0;
    }
    
    // Calculer le montant restant à payer
    $restant_a_payer = $montant_prete - $montant_rembourse;
    
    // Déterminer la situation
    if ($montant_rembourse == 0) {
        $situation = 'Aucun paiement effectué';
    } elseif ($restant_a_payer <= 0) {
        $situation = 'Tout payé';
    } else {
        $situation = 'Payé une part';
    }
    
    $datepret = $pret['datepret'] ?? 'Non défini'; 
?>
    <tr>
        <td><?php echo htmlspecialchars($num_pret); ?></td>
        <td><?php echo number_format($montant_prete, 2); ?> Ar</td>
        <td><?php echo htmlspecialchars($situation); ?></td>
        <td><?php echo number_format($restant_a_payer, 2); ?> Ar</td>
        <td><?php echo htmlspecialchars($datepret); ?></td>
    </tr>
<?php
}
?>
                </tbody>
            </table>
        </div>
    </div>
    <footer>
        <p>&copy; 2025 BankOnline - Tous droits réservés.</p>
    </footer>
</body>
</html>