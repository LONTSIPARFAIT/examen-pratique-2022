<?php
session_start();
require_once 'db.php'; // Inclure votre fichier de connexion PDO

$success = '';
$error = '';
$sms_list = [];
$selected_sender = '';

// Récupérer la liste des expéditeurs pour le menu déroulant
try {
    $stmt_personnes = $conn->prepare("SELECT id_personne, nom, prenom, numero_cni FROM personnes");
    $stmt_personnes->execute();
    $personnes = $stmt_personnes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des expéditeurs : " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['expediteur'])) {
    $expediteur_id = $_POST['expediteur'];
    $selected_sender = $expediteur_id;

    // Vérifier que l'expéditeur existe et récupérer son numéro
    try {
        $stmt_exp = $conn->prepare("SELECT numero_cni FROM personnes WHERE id_personne = :id");
        $stmt_exp->execute(['id' => $expediteur_id]);
        $expediteur = $stmt_exp->fetch(PDO::FETCH_ASSOC);

        if ($expediteur) {
            $expediteur_num = $expediteur['numero_cni'];

            // Récupérer tous les SMS envoyés par cet expéditeur
            $stmt_sms = $conn->prepare("SELECT destinataire, message, date_emission, heure_emission 
                                        FROM sms 
                                        WHERE expediteur = :expediteur 
                                        ORDER BY date_emission DESC, heure_emission DESC");
            $stmt_sms->execute(['expediteur' => $expediteur_num]);
            $sms_list = $stmt_sms->fetchAll(PDO::FETCH_ASSOC);

            if (empty($sms_list)) {
                $success = "Aucun SMS trouvé pour cet expéditeur.";
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
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f0f2f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 600px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #1877f2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #166fe5;
        }
        .success {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .no-data {
            text-align: center;
            color: #555;
            margin-top: 20px;
        }
    </style>
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
                        <th>Message</th>
                        <th>Date d'émission</th>
                        <th>Heure d'émission</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sms_list as $sms): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sms['destinataire']); ?></td>
                            <td><?php echo htmlspecialchars($sms['message']); ?></td>
                            <td><?php echo htmlspecialchars($sms['date_emission']); ?></td>
                            <td><?php echo htmlspecialchars($sms['heure_emission']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST" && empty($sms_list) && empty($error)): ?>
            <p class="no-data">Aucun SMS trouvé pour cet expéditeur.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// Pas besoin de fermer la connexion PDO explicitement, elle sera fermée à la fin du script
?>