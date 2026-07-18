<?php
include 'db.php';
session_start();
if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit();
}

function genererNumeroCompte($conn) {
    $result = $conn->query("SELECT numCompte FROM client ORDER BY numCompte DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $lastNum = $result->fetch_assoc()["numCompte"];
        $num = intval(substr($lastNum, 1)) + 1; 
        return "C" . str_pad($num, 3, "0", STR_PAD_LEFT); 
    } else {
        return "C001"; 
    }
}

$searchQuery = "";
$searchTerm = "";
if (isset($_POST['search'])) {
    $searchTerm = $_POST['searchTerm'];
    if (!empty($searchTerm)) {
        $searchQuery = " WHERE numCompte LIKE ? OR Nom LIKE ?";
    }
} elseif (isset($_POST['afficherTout'])) {
    $searchTerm = "";
    $searchQuery = "";
}

$sql = "SELECT * FROM client" . $searchQuery;
$stmt = $conn->prepare($sql);

if (!empty($searchQuery)) {
    $searchTermWithWildcards = "%$searchTerm%";
    $stmt->bind_param("ss", $searchTermWithWildcards, $searchTermWithWildcards);
}

$stmt->execute();

$result = $stmt->get_result();

if (isset($_POST['ajouter'])) {
    $numCompte = genererNumeroCompte($conn);
    $nom = $_POST['nom'];
    $prenoms = $_POST['prenoms'];
    $tel = $_POST['tel'];
    $mail = $_POST['mail'];
    $solde = $_POST['solde'];

    $sql = "INSERT INTO client (numCompte, Nom, Prenoms, Tel, mail, solde) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssd", $numCompte, $nom, $prenoms, $tel, $mail, $solde);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Client ajouté avec succès !";
        header("Location: client_banq.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Erreur lors de l'ajout : " . $stmt->error;
    }
}

if (isset($_GET['supprimer'])) {
    $numCompte = $_GET['supprimer'];

    $stmt = $conn->prepare("DELETE FROM client WHERE numCompte = ?");
    $stmt->bind_param("s", $numCompte);

    if ($stmt->execute()) {
        $_SESSION['error_message'] = "Client supprimé avec succès !";
        header("Location: client_banq.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Erreur lors de la suppression : " . $stmt->error;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="client.css?v=4">
    <title>Gestion des Clients - BankOnline</title>
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
    <div class="wrapper">
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
                <li class="id"><a href="virement.php">Virements</a></li>
                <li class="id"><a href="preter.php">Prets</a></li>
                <li class="id"><a href="rendre.php">Remboursements</a></li>
                <li class="id"><a href="tableau_bord.php">Tableau de bord</a></li>
                <li class="id"><a href="rapport.php">Rapports</a></li>
                <li class="id"><a href="logout.php" class="btn">Se Deconnecter</a></li>
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
        <h1>Gestion des Clients</h1>

        <form method="POST" action="client_banq.php" class="search-form">
            <input type="text" name="searchTerm" placeholder="N° de compte ou Nom" class="search-bar" value="<?= htmlspecialchars($searchTerm) ?>">
            <button type="submit" name="search" class="btn">Rechercher</button>
            <button type="submit" name="afficherTout" class="btn">Afficher tout</button>
        </form>

        <button class="btn add-btn" onclick="document.getElementById('addClientModal').style.display='block'">+ Ajouter un client</button>

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

        <div class="tableau">
    <table>
        <thead>
            <tr>
                <th>N° de Compte</th>
                <th>Nom</th>
                <th>Prenoms</th>
                <th>Telephone</th>
                <th>Email</th>
                <th>Solde</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
            ?>
                        <tr>
                            <td><?= htmlspecialchars($row["numCompte"]) ?></td>
                            <td><?= htmlspecialchars($row["Nom"]) ?></td>
                            <td><?= htmlspecialchars($row["Prenoms"]) ?></td>
                            <td><?= htmlspecialchars($row["Tel"]) ?></td>
                            <td><?= htmlspecialchars($row["mail"]) ?></td>
                            <td><?= number_format($row["solde"], 2) ?> Ar</td>
                            <td class="actions">
                                <a href="modifier_client.php?numCompte=<?= $row["numCompte"] ?>"><button class="edit-btn">Modifier</button></a>
                                <a href="client_banq.php?supprimer=<?= $row["numCompte"] ?>" onclick="return confirm('Voulez-vous vraiment supprimer ce client ?')"><button class="delete-btn">Supprimer</button></a>
                            </td>
                        </tr>
            <?php
                    }
                } else {
                    if (empty($searchTerm)) {
                        echo "<tr><td colspan='7' class='empty-message'>Aucun client enregistré</td></tr>";
                    } else {
                        echo "<tr><td colspan='7' class='empty-message'>Aucun résultat trouvé pour '<strong>" . htmlspecialchars($searchTerm) . "</strong>'</td></tr>";
                    }
                }
            ?>
        </tbody>
    </table>
</div>
    </section>

    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addClientModal').style.display='none'">&times;</span>
            <h2>Ajouter un Client</h2>
            <form id="clientForm" action="client_banq.php" method="post">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required>

                <label for="prenoms">Prénoms</label>
                <input type="text" id="prenoms" name="prenoms" required>

                <label for="tel">Téléphone</label>
                <input type="text" id="tel" name="tel" required>

                <label for="mail">Email</label>
                <input type="email" id="mail" name="mail" required>

                <label for="solde">Solde</label>
                <input type="number" step="1" id="solde" name="solde" required>

                <button type="submit" name="ajouter" class="btn">Enregistrer</button>
            </form>
        </div>
    </div>
    <footer>
        <p>&copy; 2025 BankOnline - Tous droits réservés.</p>
    </footer>
    </div>
</body>
</html>