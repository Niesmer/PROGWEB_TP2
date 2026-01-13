<?php
// Construction de la requête avec filtres
$sql = "
    SELECT 
        c.code_client,
        c.nom,
        c.prenom,
        c.date_naissance,
        c.num_sec_soc,
        c.date_entree,
        p.libelle AS pays,
        m.libelle AS motif,
        f.libelle AS forme_juridique
    FROM Clients c
    LEFT JOIN Pays p ON c.code_pays = p.code_pays
    LEFT JOIN Motifs m ON c.code_motif = m.code_motif
    LEFT JOIN Formes_Juridiques f ON c.code_forme = f.code_forme
    WHERE 1=1
";

$params = [];

// Filtre recherche (nom ou prénom)
if (!empty($search)) {
    $sql .= " AND (c.nom LIKE :search OR c.prenom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

// Filtre forme juridique
if (!empty($filter_forme)) {
    $sql .= " AND c.code_forme = :forme";
    $params[':forme'] = $filter_forme;
}

if (!empty($filter_motif)) {
    $sql .= " AND c.code_motif = :motif";
    $params[':motif'] = $filter_motif;
}

// Filtre pays
if (!empty($filter_pays)) {
    $sql .= " AND c.code_pays = :pays";
    $params[':pays'] = $filter_pays;
}

$sql .= " ORDER BY c.nom, c.prenom";

$query = $db_connection->prepare($sql);
$query->execute($params);
$clients = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="overflow-x-auto">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-4 py-3">Nom</th>
                <th scope="col" class="px-4 py-3">Prénom</th>
                <th scope="col" class="px-4 py-3">Forme Juridique</th>
                <th scope="col" class="px-4 py-3">Pays</th>
                <th scope="col" class="px-4 py-3">Date d'entrée</th>
                <th scope="col" class="px-4 py-3">
                    <span class="sr-only">Actions</span>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
                <tr class="border-b dark:border-gray-700">
                    <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        <?= htmlspecialchars($client['nom']) ?></th>
                    <td class="px-4 py-3"><?= htmlspecialchars($client['prenom']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($client['forme_juridique'] ?? '-') ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($client['pays'] ?? '-') ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($client['date_entree']) ?></td>
                    <td class="px-4 py-3 flex items-center justify-end">
                        <button id="dropdown-btn-<?= $client['code_client'] ?>"
                            data-dropdown-toggle="dropdown-<?= $client['code_client'] ?>"
                            class="inline-flex items-center p-0.5 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
                            type="button">
                            <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                            </svg>
                        </button>
                        <div id="dropdown-<?= $client['code_client'] ?>"
                            class="hidden z-10 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600">
                            <ul class="py-1 text-sm text-gray-700 dark:text-gray-200"
                                aria-labelledby="dropdown-btn-<?= $client['code_client'] ?>">
                                <li>
                                    <a href="fiche_client.php?code_client=<?= $client['code_client'] ?>"
                                        class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Voir</a>
                                </li>

                            </ul>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>