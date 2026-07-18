<?php
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
    <link rel="stylesheet" href="accueil.css?v=4">
    <title>BankOnline - Interface de Gestion</title>
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
                <li class="id"><a href="client_banq.php">Clients</a></li>
                <li class="id"><a href="virement.php">Virements</a></li>
                <li class="id"><a href="preter.php">Prets</a></li>
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

    <section class="hero">
        <div class="hero-text">
            <h1>Bienvenue sur BankOnline</h1>
            <p>Gérer efficacement les clients, virements, prêts et remboursements en toute simplicité.</p>
            <a href="tableau_bord.php" class="btn">Accéder au tableau de bord</a>
        </div>
    </section>

    <section class="services">
        <h2>Vue<span id="souligner"> d'ensem</span>ble</h2>
        <div class="service">
            <div class="item">
                <h3>Clients</h3>
                <p><span class="point">.</span> Gestion des comptes et informations</p>
            </div>
            <div class="item">
                <h3>Virements</h3>
                <p><span class="point">.</span>Suivi et validation des transactions</p>
        </div>
            <div class="item">
                <h3>Prêts</h3>
                <p><span class="point">.</span>Gestion et approbation des crédits</p>
            </div>
            <div class="item">
                <h3>Remboursements</h3>
                <p><span class="point">.</span>Suivi des paiements en cours</p>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; 2025 BankOnline - Tous droits réservés.</p>
    </footer>
</body>
</html>
