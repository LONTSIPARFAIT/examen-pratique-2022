session_start();
require_once 'db.php'; // Inclure votre fichier de connexion

$success = '';
$error = '';

// Récupérer la liste des personnes pour le menu déroulant
$query_personnes = "SELECT id_personne, nom, prenom, numero_cni FROM personnes";
$result_personnes = mysqli_query($conn, $query_personnes);
if (!$result_personnes) {
    die("Erreur lors de la récupération des personnes : " . mysqli_error($conn));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destinataire_id = mysqli_real_escape_string($conn, $_POST['destinataire']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    // Récupérer le numéro de téléphone du destinataire (supposons que numero_cni contient le numéro)
    $query_dest = "SELECT numero_cni FROM personnes WHERE id_personne = '$destinataire_id'";
    $result_dest = mysqli_query($conn, $query_dest);
    if ($result_dest && mysqli_num_rows($result_dest) > 0) {
        $row = mysqli_fetch_assoc($result_dest);
        $destinataire_num = $row['numero_cni']; // Numéro de téléphone
    } else {
        $error = "Destinataire introuvable.";
    }

    if (empty($error)) {
        // Définir l'expéditeur (remplacez par votre valeur, ex: un numéro ou un identifiant)
        $expediteur = "+1234567890"; // Exemple, modifiez selon vos besoins
        $date_emission = date('Y-m-d');
        $heure_emission = date('H:i:s');

        // Enregistrer le SMS dans la table sms
        $query_sms = "INSERT INTO sms (expediteur, destinataire, message, date_emission, heure_emission) 
                      VALUES ('$expediteur', '$destinataire_num', '$message', '$date_emission', '$heure_emission')";
        if (mysqli_query($conn, $query_sms)) {
            $success = "SMS enregistré avec succès !";
        } else {
            $error = "Erreur lors de l'enregistrement : " . mysqli_error($conn);
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
                    <?php while ($row = mysqli_fetch_assoc($result_personnes)): ?>
                        <option value="<?php echo $row['id_personne']; ?>">
                            <?php echo $row['nom'] . ' ' . $row['prenom'] . ' (' . $row['numero_cni'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
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
// Libérer le résultat et fermer la connexion
mysqli_free_result($result_personnes);
mysqli_close($conn);
?>