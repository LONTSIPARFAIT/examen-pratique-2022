<?php
session_start();
require_once 'db.php'; // Inclure votre fichier de connexion PDO

$success = '';
$error = '';

// Récupérer la liste des personnes pour le menu déroulant
try {
    $stmt_personnes = $conn->prepare("SELECT id_personne, nom, prenom, numero_cni FROM personnes");
    $stmt_personnes->execute();
    $personnes = $stmt_personnes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des personnes : " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destinataire_id = $_POST['destinataire'];
    $message = $_POST['message'];

    // Vérifier que le destinataire existe et récupérer son numéro
    try {
        $stmt_dest = $conn->prepare("SELECT numero_cni FROM personnes WHERE id_personne = :id");
        $stmt_dest->execute(['id' => $destinataire_id]);
        $destinataire = $stmt_dest->fetch(PDO::FETCH_ASSOC);

        if ($destinataire) {
            $destinataire_num = $destinataire['numero_cni']; // Numéro de téléphone
        } else {
            $error = "Destinataire introuvable.";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la vérification du destinataire : " . $e->getMessage();
    }

    if (empty($error)) {
        // Définir l'expéditeur (remplacez par votre valeur, ex: un numéro ou un identifiant)
        $expediteur = "+1234567890"; // Modifiez selon vos besoins
        $date_emission = date('Y-m-d');
        $heure_emission = date('H:i:s');

        // Enregistrer le SMS dans la table sms
        try {
            $stmt_sms = $conn->prepare("INSERT INTO sms (expediteur, destinataire, message, date_emission, heure_emission) 
                                        VALUES (:expediteur, :destinataire, :message, :date_emission, :heure_emission)");
            $stmt_sms->execute([
                'expediteur' => $expediteur,
                'destinataire' => $destinataire_num,
                'message' => $message,
                'date_emission' => $date_emission,
                'heure_emission' => $heure_emission
            ]);
            $success = "SMS enregistré avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enregistrer un SMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Enregistrer un SMS</h2>
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="destinataire">Destinataire</label>
                <select id="destinataire" name="destinataire" required>
                    <option value="">Sélectionner une personne</option>
                    <?php foreach ($personnes as $personne): ?>
                        <option value="<?php echo htmlspecialchars($personne['id_personne']); ?>">
                            <?php echo htmlspecialchars($personne['nom'] . ' ' . $personne['prenom'] . ' (' . $personne['numero_cni'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" required maxlength="160"></textarea>
            </div>
            <button type="submit">Enregistrer</button>
        </form>
    </div>
</body>
</html>
<?php
// Pas besoin de fermer la connexion PDO explicitement, elle sera fermée à la fin du script
?>