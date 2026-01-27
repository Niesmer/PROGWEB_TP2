<?php
require_once 'Ressources_communes.php';

$code_client = $_GET['client'] ?? null;
$client_info = null;
$devis_created = $_GET['devis_created'] ?? null;
$devis_updated = $_GET['devis_updated'] ?? null;
$code_devis = $_POST['code_devis'] ?? null;

if (!$code_client) {
    header('Location: recherche_client.php');
    exit;
}

$stmt = $db_connection->prepare("
    SELECT 
        c.*,
        p.libelle AS pays_libelle,
        f.libelle AS forme_libelle,
        m.libelle AS motif_libelle
    FROM Clients c
    LEFT JOIN Pays p ON c.code_pays = p.code_pays
    LEFT JOIN Formes_Juridiques f ON c.code_forme = f.code_forme
    LEFT JOIN Motifs m ON c.code_motif = m.code_motif
    WHERE c.code_client = :code_client
");
$stmt->execute([':code_client' => $code_client]);
$client_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client_info) {
    header('Location: recherche_client.php');
    exit;
}

// Récupérer les devis du client
$stmt = $db_connection->prepare("
    SELECT d.*, 
           (SELECT COUNT(*) FROM Lignes_Devis ld WHERE ld.code_devis = d.code_devis) AS nb_lignes,
             d.status_devis

    FROM Devis d
    WHERE d.code_client = :code_client
    ORDER BY d.date_devis DESC, d.code_devis DESC
");
$stmt->execute([':code_client' => $code_client]);
$devis_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les filtres de recherche de devis  si soumis
$date_debut = $_GET['date_debut'] ?? null;
$montant_min = $_GET['montant_min'] ?? null;
$montant_max = $_GET['montant_max'] ?? null;

// Construction dynamique de la requête
$sql_devis = "
    SELECT d.*, 
           (SELECT COUNT(*) FROM Lignes_Devis ld WHERE ld.code_devis = d.code_devis) AS nb_lignes,
           d.status_devis
    FROM Devis d
    WHERE d.code_client = :code_client
";

$params = [':code_client' => $code_client];

// Filtre date
if (!empty($date_debut)) {
    $sql_devis .= " AND d.date_devis >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

// Filtre montant HT
if (!empty($montant_min)) {
    $sql_devis .= " AND d.montant_ht >= :montant_min";
    $params[':montant_min'] = $montant_min;
}
if (!empty($montant_max)) {
    $sql_devis .= " AND d.montant_ht <= :montant_max";
    $params[':montant_max'] = $montant_max;
}

$sql_devis .= " ORDER BY d.date_devis DESC, d.code_devis DESC";

$stmt = $db_connection->prepare($sql_devis);
$stmt->execute($params);
$devis_list = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Calculer l'âge si date de naissance disponible
$age = null;
if ($client_info['date_naissance']) {
    $birthDate = new DateTime($client_info['date_naissance']);
    $today = new DateTime();
    $age = $birthDate->diff($today)->y;
}

// Calculer l'ancienneté
$anciennete = null;
if ($client_info['date_entree']) {
    $entreeDate = new DateTime($client_info['date_entree']);
    $today = new DateTime();
    $diff = $entreeDate->diff($today);
    $anciennete = $diff->y . ' an' . ($diff->y > 1 ? 's' : '') . ' et ' . $diff->m . ' mois';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./global.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.css" rel="stylesheet" />
    <title>Fiche Client - SAV</title>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>

    <?php include("./components/navbar.php") ?>

    <main>
        <section class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-7xl mx-auto px-4">

                <!-- Breadcrumb -->
                <nav class="flex mb-5" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                        <li class="inline-flex items-center">
                            <a href="./"
                                class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg class="w-3 h-3 me-2.5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z" />
                                </svg>
                                Accueil
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" fill="none"
                                    viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Fiche
                                    Client</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Message de succès création -->
                <?php if ($devis_created): ?>
                    <div class="flex items-center p-4 mb-4 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
                        role="alert">
                        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only">Info</span>
                        <div class="ms-3 text-sm font-medium">
                            <strong>Succès !</strong> Devis #<?= htmlspecialchars($devis_created) ?> créé avec succès!
                        </div>
                        <button type="button"
                            class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700"
                            data-dismiss-target="#alert-1" aria-label="Close"
                            onclick="this.closest('[role=alert]').remove()">
                            <span class="sr-only">Fermer</span>
                            <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 14 14">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Message de succès envoi email -->
                <?php $email_sent = $_GET['email_sent'] ?? null; ?>
                <?php if ($email_sent): ?>
                    <div class="flex items-center p-4 mb-4 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
                        role="alert">
                        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        <span class="sr-only">Info</span>
                        <div class="ms-3 text-sm font-medium">
                            <strong>Email envoyé !</strong> Le devis #<?= htmlspecialchars($email_sent) ?> a été envoyé avec succès!
                        </div>
                        <button type="button"
                            class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700"
                            onclick="this.closest('[role=alert]').remove()">
                            <span class="sr-only">Fermer</span>
                            <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Message d'erreur -->
                <?php $error = $_GET['error'] ?? null; ?>
                <?php if ($error): ?>
                    <div class="flex items-center p-4 mb-4 text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400"
                        role="alert">
                        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM10 15a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1-4a1 1 0 0 1-2 0V6a1 1 0 0 1 2 0v5Z"/>
                        </svg>
                        <span class="sr-only">Erreur</span>
                        <div class="ms-3 text-sm font-medium">
                            <strong>Erreur !</strong> 
                            <?php 
                            $error_messages = [
                                'missing_params' => 'Paramètres manquants pour l\'envoi de l\'email.',
                                'invalid_email' => 'L\'adresse email n\'est pas valide.',
                                'devis_not_found' => 'Le devis demandé n\'a pas été trouvé.',
                                'pdf_generation_failed' => 'La génération du PDF a échoué.',
                                'email_failed' => 'L\'envoi de l\'email a échoué: ' . ($_GET['message'] ?? 'Erreur inconnue')
                            ];
                            echo $error_messages[$error] ?? 'Une erreur inconnue s\'est produite.';
                            ?>
                        </div>
                        <button type="button"
                            class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700"
                            onclick="this.closest('[role=alert]').remove()">
                            <span class="sr-only">Fermer</span>
                            <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Message de succès mise à jour -->
                <?php if ($devis_updated): ?>
                    <div
                        class="mb-6 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span><strong>Mis à jour !</strong> Devis #<?= htmlspecialchars($devis_updated) ?> modifié avec
                                succès!</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-blue-700 hover:text-blue-900">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Bouton retour -->
                <div class="mb-6">
                    <a href="./"
                        class="text-white bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-600 dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800 inline-flex items-center">
                        <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour à la recherche
                    </a>
                </div>

                <!-- En-tête client -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="px-6 py-8">
                        <div class="flex justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                                    <span class="text-3xl font-bold dark:text-white">
                                        <?= strtoupper(substr($client_info['prenom'], 0, 1) . substr($client_info['nom'], 0, 1)) ?>
                                    </span>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold dark:text-white">
                                        <?= htmlspecialchars($client_info['prenom'] . ' ' . $client_info['nom']) ?>
                                    </h1>
                                    <p class="dark:text-white mt-1">
                                        <?= htmlspecialchars($client_info['forme_libelle'] ?? 'Particulier') ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <a href="devis_client.php?client=<?= $client_info['code_client'] ?>"
                                    class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Créer un nouveau devis
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Informations détaillées -->
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Informations personnelles
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nom -->
                            <div
                                class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Nom</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?= htmlspecialchars($client_info['nom']) ?>
                                </p>
                            </div>

                            <!-- Prénom -->
                            <div
                                class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Prénom</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?= htmlspecialchars($client_info['prenom']) ?>
                                </p>
                            </div>

                            <!-- Date de naissance -->
                            <div
                                class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Date de
                                    naissance</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?php if ($client_info['date_naissance']): ?>
                                        <?= date('d/m/Y', strtotime($client_info['date_naissance'])) ?>
                                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(<?= $age ?>
                                            ans)</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Non renseignée</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <!-- Numéro de sécurité sociale -->
                            <div
                                class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">N° Sécurité
                                    Sociale</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1 font-mono">
                                    <?php if ($client_info['num_sec_soc']): ?>
                                        <?= htmlspecialchars($client_info['num_sec_soc']) ?>
                                    <?php else: ?>
                                        <span class="text-gray-400 font-sans">Non renseigné</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <!-- Pays -->
                            <div
                                class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Pays</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?= htmlspecialchars($client_info['pays_libelle'] ?? '-') ?>
                                    <span
                                        class="text-sm font-normal text-gray-500 dark:text-gray-400">(<?= htmlspecialchars($client_info['code_pays'] ?? '') ?>)</span>
                                </p>
                            </div>

                            <!-- Forme juridique -->
                            <div
                                class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Forme
                                    Juridique</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?= htmlspecialchars($client_info['forme_libelle'] ?? '-') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations client -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Relation client
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Code client -->
                        <div
                            class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Code Client</label>
                            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">
                                #<?= htmlspecialchars($client_info['code_client']) ?>
                            </p>
                        </div>

                        <!-- Date d'entrée -->
                        <div
                            class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Client
                                depuis</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                <?= date('d/m/Y', strtotime($client_info['date_entree'])) ?>
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= $anciennete ?>
                            </p>
                        </div>

                        <!-- Motif d'entrée -->
                        <div
                            class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Motif
                                d'entrée</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <?php if ($client_info['motif_libelle'] === 'Téléphone'): ?>
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    <?php elseif ($client_info['motif_libelle'] === 'Mail'): ?>
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    <?php elseif ($client_info['motif_libelle'] === 'Web'): ?>
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($client_info['motif_libelle'] ?? '-') ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Liste des devis -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Devis du client
                        </h2>
                        <!-- Formulaire de recherche de devis -->
                        <form method="get" class="flex gap-4 flex-wrap items-end">
                            <input type="hidden" name="client" value="<?= htmlspecialchars($code_client ?? '') ?>">

                            <div>
                                <label for="date_debut"
                                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Date à
                                    partir</label>
                                <input type="date" id="date_debut" name="date_debut"
                                    value="<?= htmlspecialchars($date_debut ?? '') ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                            </div>

                            <div>
                                <label for="montant_min"
                                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Montant min
                                    (€)</label>
                                <input type="number" id="montant_min" step="0.01" name="montant_min"
                                    value="<?= htmlspecialchars($montant_min ?? '') ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                            </div>

                            <div>
                                <label for="montant_max"
                                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Montant max
                                    (€)</label>
                                <input type="number" id="montant_max" step="0.01" name="montant_max"
                                    value="<?= htmlspecialchars($montant_max ?? '') ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                            </div>

                            <div>
                                <button type="submit"
                                    class="text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                    </svg>
                                    Filtrer
                                </button>
                            </div>
                        </form>

                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <?= count($devis_list) ?> devis
                        </span>
                    </div>

                    <?php if (empty($devis_list)): ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Aucun devis pour ce client</p>
                            <a href="devis_client.php?client=<?= $client_info['code_client'] ?>"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Créer le premier devis
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead
                                    class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">N° Devis</th>
                                        <th class="px-4 py-3">Date</th>
                                        <th class="px-4 py-3 text-center">Lignes</th>
                                        <th class="px-4 py-3 text-right">Montant HT</th>
                                        <th class="px-4 py-3 text-right">Montant TTC</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devis_list as $devis): ?>
                                        <?php
                                        $labels_status = [
                                            DEVIS_STATUS::ONGOING->value => 'En cours',
                                            DEVIS_STATUS::PRINTED->value => 'Imprimé',
                                            DEVIS_STATUS::ACCEPTED->value => 'Validé',
                                            DEVIS_STATUS::REJECTED->value => 'Refusé'
                                        ];
                                        $color_status = [
                                            DEVIS_STATUS::ONGOING->value => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                            DEVIS_STATUS::PRINTED->value => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            DEVIS_STATUS::ACCEPTED->value => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            DEVIS_STATUS::REJECTED->value => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                        ];
                                        ?>
                                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                                #<?= htmlspecialchars($devis['code_devis']) ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?= date('d/m/Y', strtotime($devis['date_devis'])) ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    class="inline-flex items-center justify-center w-6 h-6 text-xs font-semibold text-primary-800 bg-primary-100 rounded-full dark:bg-primary-900 dark:text-primary-200">
                                                    <?= htmlspecialchars($devis['nb_lignes']) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <?= number_format($devis['montant_ht'], 2, ',', ' ') ?> €
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                                <?= number_format($devis['montant_ttc'], 2, ',', ' ') ?> €
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-sm font-semibold <?= $color_status[$devis['status_devis']] ?>">
                                                    <?= $labels_status[$devis['status_devis']] ?>
                                                </span>
                                            </td>


                                            <td class="px-4 py-3 text-center">
                                                <div class="flex justify-center gap-2">
                                                    <button type="button" onclick="openEmailModal(<?= $devis['code_devis'] ?>, <?= $code_client ?>)"
                                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800">
                                                        <svg class="w-4 h-4 stroke-blue-700 dark:stroke-blue-300"
                                                            viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                                                stroke-linejoin="round"></g>
                                                            <g id="SVGRepo_iconCarrier">
                                                                <path
                                                                    d="M4 7L10.94 11.3375C11.5885 11.7428 12.4115 11.7428 13.06 11.3375L20 7M5 18H19C20.1046 18 21 17.1046 21 16V8C21 6.89543 20.1046 6 19 6H5C3.89543 6 3 6.89543 3 8V16C3 17.1046 3.89543 18 5 18Z"
                                                                    stroke-width="2" stroke-linecap="round"
                                                                    stroke-linejoin="round"></path>
                                                            </g>
                                                        </svg>
                                                        Envoyer
                                                    </button>
                                                    <!-- Voir/Modifier -->
                                                    <?php if ($devis['status_devis'] != DEVIS_STATUS::ACCEPTED->value): ?>
                                                        <a href="devis_client.php?client=<?= $code_client ?>&devis=<?= $devis['code_devis'] ?>"
                                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-100 rounded-lg hover:bg-primary-200 dark:bg-primary-900 dark:text-primary-300 dark:hover:bg-primary-800"
                                                            title="Modifier le devis">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                            Modifier
                                                        </a>
                                                    <?php endif; ?>

                                                    <!-- voir -->
                                                    <a href="consulter_devis.php?devis=<?= $devis['code_devis'] ?>"
                                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                                        title="Voir le devis">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Consulter
                                                    </a>
                                                    <!-- Supprimer -->
                                                    <button type="button"
                                                        onclick="confirmerSuppression(<?= $devis['code_devis'] ?>)"
                                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-100 rounded-lg hover:bg-red-200 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800"
                                                        title="Supprimer le devis">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Supprimer
                                                    </button>
                                                    <!-- valider -->
                                                    <?php if ($devis['status_devis'] == DEVIS_STATUS::ONGOING->value): ?>
                                                        <form method="post" action="valider_devis.php" class="inline">
                                                            <input type="hidden" name="code_devis"
                                                                value="<?= $devis['code_devis'] ?>">
                                                            <button id="btnValiderDevis" type="submit"
                                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 dark:bg-green-900 dark:hover:bg-green-800"
                                                                title="Valider le devis">
                                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                Valider
                                                            </button>
                                                            <!-- <button id="btnValiderDevis" data-devis="<?= $code_devis ?>">Valider le devis</button> -->

                                                        </form>
                                                    <?php endif; ?>

                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-700">
                                    <tr class="font-semibold text-gray-900 dark:text-white">
                                        <td class="px-4 py-3" colspan="3">Total</td>
                                        <td class="px-4 py-3 text-right">
                                            <?= number_format(array_sum(array_column($devis_list, 'montant_ht')), 2, ',', ' ') ?>
                                            €
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <?= number_format(array_sum(array_column($devis_list, 'montant_ttc')), 2, ',', ' ') ?>
                                            €
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Confirmer la suppression</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        Êtes-vous sûr de vouloir supprimer ce devis ? Cette action est irréversible.
                    </p>
                    <div class="flex justify-center gap-3">
                        <button type="button" onclick="fermerModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Annuler
                        </button>
                        <form id="deleteForm" method="POST" action="supprimer_devis.php" class="inline">
                            <input type="hidden" name="code_devis" id="deleteDevisId" value="">
                            <input type="hidden" name="code_client" value="<?= $code_client ?>">
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'envoi d'email -->
    <div id="emailModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Envoyer le devis par email
                    </h3>
                    <button type="button" onclick="fermerEmailModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form id="emailForm" method="POST" action="envoyer_devis.php">
                    <input type="hidden" id="emailDevisId" name="code_devis" value="">
                    <input type="hidden" id="emailClientId" name="code_client" value="">
                    
                    <div class="mb-4">
                        <label for="emailTo" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email du destinataire</label>
                        <input type="email" id="emailTo" name="email_to" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="client@exemple.com">
                    </div>
                    
                    <div class="mb-4">
                        <label for="emailSubject" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Objet</label>
                        <input type="text" id="emailSubject" name="email_subject" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            value="Votre devis POLY Industrie">
                    </div>
                    
                    <div class="mb-4">
                        <label for="emailBody" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Message</label>
                        <textarea id="emailBody" name="email_body" rows="6" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Écrivez votre message ici...">Bonjour,

Veuillez trouver ci-joint votre devis.

Cordialement,
L'équipe POLY Industrie</textarea>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="fermerEmailModal()"
                            class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">
                            Annuler
                        </button>
                        <button type="submit"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation validation -->
    <div id="devisvalidationModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Devis validé</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        Le devis #<span id="validatedDevisId"></span> a été validé avec succès et ne pourra plus être
                        modifié.
                    </p>
                    <button type="button" onclick="fermerValidationModal()"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>



    <script>    function openEmailModal(codeDevis, codeClient) {
        document.getElementById('emailDevisId').value = codeDevis;
        document.getElementById('emailClientId').value = codeClient;
        document.getElementById('emailModal').classList.remove('hidden');
    }

    function fermerEmailModal() {
        document.getElementById('emailModal').classList.add('hidden');
    }

    // Fermer le modal email en cliquant à l'extérieur
    document.getElementById('emailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            fermerEmailModal();
        }
    });
        function confirmerSuppression(codeDevis) {
            document.getElementById('deleteDevisId').value = codeDevis;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function fermerModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('deleteModal').addEventListener('click', function (e) {
            if (e.target === this) {
                fermerModal();
            }
        });
        //recharger le devis
        document.getElementById('btnValiderDevis').addEventListener('click', function (e) {
            e.preventDefault(); // Empêche le submit classique du formulaire
            const codeDevis = this.closest('form').querySelector('input[name="code_devis"]').value;

            fetch('valider_devis.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'code_devis=' + codeDevis
            })
                .then(response => response.text())
                .then(data => {
                    // Remplir l'ID dans le modal
                    document.getElementById('validatedDevisId').textContent = codeDevis;
                    // Afficher le modal
                    document.getElementById('devisvalidationModal').classList.remove('hidden');
                })
                .catch(err => alert("Erreur lors de la validation : " + err));
        });

        // Fonction de fermeture du modal de validation
        function fermerValidationModal() {
            document.getElementById('devisvalidationModal').classList.add('hidden');
        }

        // Fermer le modal si clic en dehors
        document.getElementById('devisvalidationModal').addEventListener('click', function (e) {
            if (e.target === this) {
                fermerValidationModal();
            }
            const codeDevis = this.dataset.devis;

            fetch('valider_devis.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'code_devis=' + codeDevis
            })
                .then(response => response.text())
                .then(data => {
                    alert("Le devis a bien été validé !");
                    location.reload()

                })
                .catch(err => alert("Erreur lors de la validation : " + err));
        });

    </script>
</body>

</html>