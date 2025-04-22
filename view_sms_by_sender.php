<?php
session_start();
require_once 'db.php'; // Inclure votre fichier de connexion PDO

$success = '';
$error = '';
$debug = '';
$invalid_expediteurs = '';
$sms_list = [];
$selected_sender = '';

// Récupérer la liste des personnes pour le menu déroulant
try {
    $stmt_personnes = $conn->prepare("SELECT id_personne, nom, prenom, numero_cni FROM personnes ORDER BY nom, prenom");
    $stmt_personnes->execute();
    $personnes = $stmt_personnes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des expéditeurs : " . $e->getMessage();
}

// Vérifier les expediteur non valides dans sms
try {
    $stmt_invalid = $conn->prepare("SELECT DISTINCT expediteur 
                                    FROM sms 
                                    WHERE expediteur NOT IN (SELECT numero_cni FROM personnes)");
    $stmt_invalid->execute();
    $invalid_expediteurs_list = $stmt_invalid->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($invalid_expediteurs_list)) {
        $invalid_expediteurs = "Expéditeurs non valides trouvés dans sms : " . implode(', ', array_map('htmlspecialchars', $invalid_expediteurs_list));
    }
} catch (PDOException $e) {
    $error .= "Erreur lors de la vérification des expéditeurs non valides : " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['expediteur'])) {
    $expediteur_id = $_POST['expediteur'];
    $selected_sender = $expediteur_id;

    // Vérifier que l'expéditeur existe et récupérer son numero_cni
    try {
        $stmt_exp = $conn->prepare("SELECT numero_cni, nom, prenom FROM personnes WHERE id_personne = :id");
        $stmt_exp->execute(['id' => $expediteur_id]);
        $expediteur = $stmt_exp->fetch(PDO::FETCH_ASSOC);

        if ($expediteur) {
            $expediteur_num = $expediteur['numero_cni'];
            $debug = "Recherche des SMS pour l'expéditeur : " . htmlspecialchars($expediteur['nom'] . ' ' . $expediteur['prenom'] . ' (' . $expediteur_num . ')');

            // Récupérer tous les SMS envoyés par cet expéditeur avec les infos du destinataire
            $stmt_sms = $conn->prepare("SELECT s.destinataire, s.message, s.date_emission, s.heure_emission, p.nom AS dest_nom, p.prenom AS dest_prenom 
                                        FROM sms s 
                                        LEFT JOIN personnes p ON s.destinataire = p.numero_cni 
                                        WHERE s.expediteur = :expediteur 
                                        ORDER BY s.date_emission DESC, s.heure_emission DESC");
            $stmt_sms->execute(['expediteur' => $expediteur_num]);
            $sms_list = $stmt_sms->fetchAll(PDO::FETCH_ASSOC);

            if (empty($sms_list)) {
                $success = "Aucun SMS trouvé pour cet expéditeur (numero_cni: " . htmlspecialchars($expediteur_num) . ").";
            }
        } else {
            $error = "Expéditeur introuvable.";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération des SMS : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation des SMS par Expéditeur</title>
    <link rel="stylesheet" href="./view_sms_by_sender.css">
</head>
<body>
    <div class="container">
        <h2>Consultation des SMS par Expéditeur</h2>
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($debug): ?>
            <p class="debug"><?php echo htmlspecialchars($debug); ?></p>
        <?php endif; ?>
        <?php if ($invalid_expediteurs): ?>
            <p class="warning"><?php echo htmlspecialchars($invalid_expediteurs); ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="expediteur">Expéditeur</label>
                <select id="expediteur" name="expediteur" required>
                    <option value="">Sélectionner un expéditeur</option>
                    <?php foreach ($personnes as $personne): ?>
                        <option value="<?php echo htmlspecialchars($personne['id_personne']); ?>" 
                                <?php echo ($selected_sender == $personne['id_personne']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($personne['nom'] . ' ' . $personne['prenom'] . ' (' . $personne['numero_cni'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Consulter</button>
        </form>

        <?php if (!empty($sms_list)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Destinataire</th>
                        <th>Nom du destinataire</th>
                        <th>Message</th>
                        <th>Date d'émission</th>
                        <th>Heure d'émission</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sms_list as $sms): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sms['destinataire']); ?></td>
                            <td><?php echo htmlspecialchars(($sms['dest_nom'] ?? '') . ' ' . ($sms['dest_prenom'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($sms['message']); ?></td>
                            <td><?php echo htmlspecialchars($sms['date_emission']); ?></td>
                            <td><?php echo htmlspecialchars($sms['heure_emission']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST" && empty($sms_list) && empty($error)): ?>
            <p class="no-data">Aucun  SMS trouvé pour cet expéditeur.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// matricule:25GL1274
// Pas besoin de fermer la connexion PDO explicitement
?>