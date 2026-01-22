<?php
$code_client = $_GET['client'] ?? null;
$client_info = null;

$devis = $_GET['devis'] ?? null;
$devis_info = null;

if ($devis && isset($db_connection)) {
    $stmt = $db_connection->prepare("
        SELECT d.*, c.nom, c.prenom
        FROM Devis d
        LEFT JOIN Clients c ON d.code_client = c.code_client
        WHERE d.code_devis = :code_devis
    ");
    $stmt->execute([':code_devis' => $devis]);
    $devis_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($devis_info) {
        $stmt = $db_connection->prepare("
            SELECT ld.*, a.designation, a.forfait_ht, t.taux AS tva_taux, u.libelle AS unite_libelle
            FROM Lignes_Devis ld
            LEFT JOIN Articles a ON ld.code_article = a.code_article
            LEFT JOIN TVA t ON a.code_tva = t.code_tva
            LEFT JOIN Unites u ON a.code_unite = u.code_unite
            WHERE ld.code_devis = :code_devis
        ");
        $stmt->execute([':code_devis' => $devis]);
        $lignes_devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $devis_info['lignes'] = $lignes_devis;
        
        // Use the client from the devis
        $code_client = $devis_info['code_client'];
    }
}

if ($code_client && isset($db_connection)) {
    $stmt = $db_connection->prepare("
        SELECT c.*, p.libelle AS pays_libelle, f.libelle AS forme_libelle
        FROM Clients c
        LEFT JOIN Pays p ON c.code_pays = p.code_pays
        LEFT JOIN Formes_Juridiques f ON c.code_forme = f.code_forme
        WHERE c.code_client = :code_client
    ");
    $stmt->execute([':code_client' => $code_client]);
    $client_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupérer tous les articles disponibles
$articles = [];
if (isset($db_connection)) {
    $stmt = $db_connection->query("
        SELECT a.*, u.libelle AS unite_libelle, t.taux AS tva_taux
        FROM Articles a
        LEFT JOIN Unites u ON a.code_unite = u.code_unite
        LEFT JOIN TVA t ON a.code_tva = t.code_tva
    ");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<section class="bg-white min-h-full dark:bg-gray-900 py-8">
    <div class="max-w-6xl mx-auto px-4">
        <!-- En-tête du devis -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?= $devis_info ? 'Édition Devis SAV #' . htmlspecialchars($devis) : 'Nouveau Devis SAV' ?>
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Date: <?= date('d/m/Y') ?></p>
                </div>
                <div class="text-right">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">POLY Industrie</h2>
                </div>
            </div>
        </div>

        <!-- Informations client -->
        <?php if ($client_info): ?>
            <div class="bg-blue-50 dark:bg-gray-800 rounded-lg p-4 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Client</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Nom:</span>
                        <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($client_info['nom'] . ' ' . $client_info['prenom']) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Forme Juridique:</span>
                        <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($client_info['forme_libelle'] ?? '-') ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Pays:</span>
                        <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($client_info['pays_libelle'] ?? '-') ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">N° Sécurité Sociale:</span>
                        <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($client_info['num_sec_soc'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 mb-6">
                <p class="text-yellow-800 dark:text-yellow-200">
                    <a href="recherche_client.php" class="underline hover:text-yellow-600">Veuillez d'abord sélectionner un client</a>
                </p>
            </div>
        <?php endif; ?>

        <!-- Formulaire du devis -->
        <form id="devisForm" method="POST" action="traitement_devis.php">
            <input type="hidden" name="code_client" value="<?= htmlspecialchars($code_client ?? '') ?>">
            <?php if ($devis): ?>
                <input type="hidden" name="code_devis" value="<?= htmlspecialchars($devis) ?>">
            <?php endif; ?>

            <!-- Zone d'ajout d'article -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ajouter un article</h3>
                <div class="flex gap-4 items-end flex-wrap">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Code Article</label>
                        <div class="relative">
                            <input type="text" id="searchArticle"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="Rechercher un article...">
                            <button type="button" id="btnSearchArticle"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-primary-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                            <!-- Dropdown de recherche -->
                            <div id="articleDropdown"
                                class="hidden absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <?php foreach ($articles as $article): ?>
                                    <div class="article-option p-3 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-0"
                                        data-code="<?= htmlspecialchars($article['code_article']) ?>"
                                        data-designation="<?= htmlspecialchars($article['designation']) ?>"
                                        data-unite="<?= htmlspecialchars($article['unite_libelle']) ?>"
                                        data-forfait="<?= htmlspecialchars($article['forfait_ht']) ?>"
                                        data-tva="<?= htmlspecialchars($article['tva_taux']) ?>">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($article['code_article']) ?> - <?= htmlspecialchars($article['designation']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            Forfait: <?= number_format($article['forfait_ht'], 2, ',', ' ') ?> € HT /
                                            <?= htmlspecialchars($article['unite_libelle']) ?>
                                            | TVA: <?= htmlspecialchars($article['tva_taux']) ?>%
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="w-32">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Quantité</label>
                        <input type="number" id="quantite" min="0.01" step="0.01" value="1"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div class="w-32">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Unité</label>
                        <input type="text" id="unite" readonly
                            class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <button type="button" id="btnAddLine"
                            class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Ajouter
                        </button>
                    </div>
                </div>
                <!-- Champs cachés pour l'article sélectionné -->
                <input type="hidden" id="selectedCode" value="">
                <input type="hidden" id="selectedDesignation" value="">
                <input type="hidden" id="selectedForfait" value="">
                <input type="hidden" id="selectedTva" value="">
            </div>

            <!-- Tableau des lignes du devis -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Désignation</th>
                            <th class="px-4 py-3 text-center">Quantité</th>
                            <th class="px-4 py-3 text-center">Unité</th>
                            <th class="px-4 py-3 text-right">Prix Unit. HT</th>
                            <th class="px-4 py-3 text-right">TVA %</th>
                            <th class="px-4 py-3 text-right">Montant HT</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="lignesDevis">
                        <?php if (!$devis_info || empty($devis_info['lignes'])): ?>
                            <tr id="emptyRow">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                    Aucun article ajouté. Utilisez la recherche ci-dessus pour ajouter des articles.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Totaux -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                <div class="flex justify-end">
                    <div class="w-full max-w-md">
                        <!-- Total HT -->
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Total HT</span>
                            <span class="font-semibold text-gray-900 dark:text-white" id="totalHT">0,00 €</span>
                        </div>

                        <!-- Détail TVA -->
                        <div id="detailTVA" class="border-b border-gray-200 dark:border-gray-700">
                            <!-- Les lignes de TVA seront ajoutées dynamiquement -->
                        </div>

                        <!-- Total TVA -->
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Total TVA</span>
                            <span class="font-semibold text-gray-900 dark:text-white" id="totalTVA">0,00 €</span>
                        </div>

                        <!-- Total TTC -->
                        <div class="flex justify-between py-3 text-lg">
                            <span class="font-bold text-gray-900 dark:text-white">Total TTC</span>
                            <span class="font-bold text-primary-600 dark:text-primary-400" id="totalTTC">0,00 €</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-between mt-6">
                <a href="fiche_client.php?client=<?= urlencode($code_client ?? '') ?>"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700">
                    ← Retour
                </a>
                <div class="flex gap-3">
                    <button type="button" id="btnPrintPDF"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Imprimer PDF
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700">
                        <?= $devis_info ? 'Mettre à jour' : 'Enregistrer' ?> le devis
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
    // Données des articles pour JavaScript
    const articlesData = <?= json_encode($articles) ?>;
    let ligneIndex = 0;
    let lignesDevis = [];

    // Charger les lignes existantes si on est en mode édition
    <?php if ($devis_info && !empty($devis_info['lignes'])): ?>
    const lignesExistantes = <?= json_encode($devis_info['lignes']) ?>;
    lignesExistantes.forEach((ligne, idx) => {
        lignesDevis.push({
            index: idx,
            code: ligne.code_article,
            designation: ligne.designation,
            quantite: parseFloat(ligne.quantite),
            unite: ligne.unite_libelle,
            forfait: parseFloat(ligne.forfait_ht),
            tva: parseFloat(ligne.tva_taux),
            montantHT: parseFloat(ligne.quantite) * parseFloat(ligne.forfait_ht)
        });
        
        ajouterLigneDOM(idx, ligne.code_article, ligne.designation, parseFloat(ligne.quantite), 
                        ligne.unite_libelle, parseFloat(ligne.forfait_ht), parseFloat(ligne.tva_taux));
        ligneIndex = idx + 1;
    });
    calculerTotaux();
    <?php endif; ?>

    // Gestion de la recherche d'articles
    const searchInput = document.getElementById('searchArticle');
    const articleDropdown = document.getElementById('articleDropdown');
    const btnSearch = document.getElementById('btnSearchArticle');

    function toggleDropdown(show) {
        articleDropdown.classList.toggle('hidden', !show);
    }

    function filterArticles(query) {
        const options = articleDropdown.querySelectorAll('.article-option');
        const lowerQuery = query.toLowerCase();
        let hasVisible = false;
        options.forEach(opt => {
            const code = opt.dataset.code.toLowerCase();
            const designation = opt.dataset.designation.toLowerCase();
            const match = code.includes(lowerQuery) || designation.includes(lowerQuery);
            opt.style.display = match ? 'block' : 'none';
            if (match) hasVisible = true;
        });
        return hasVisible;
    }

    searchInput.addEventListener('focus', () => {
        filterArticles(searchInput.value);
        toggleDropdown(true);
    });
    
    searchInput.addEventListener('input', (e) => {
        filterArticles(e.target.value);
        toggleDropdown(true);
    });

    btnSearch.addEventListener('click', () => toggleDropdown(true));

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#searchArticle') && !e.target.closest('#articleDropdown') && !e.target.closest('#btnSearchArticle')) {
            toggleDropdown(false);
        }
    });

    // Sélection d'un article
    articleDropdown.querySelectorAll('.article-option').forEach(opt => {
        opt.addEventListener('click', () => {
            document.getElementById('selectedCode').value = opt.dataset.code;
            document.getElementById('selectedDesignation').value = opt.dataset.designation;
            document.getElementById('selectedForfait').value = opt.dataset.forfait;
            document.getElementById('selectedTva').value = opt.dataset.tva;
            document.getElementById('unite').value = opt.dataset.unite;
            searchInput.value = opt.dataset.code + ' - ' + opt.dataset.designation;
            toggleDropdown(false);
        });
    });

    function ajouterLigneDOM(index, code, designation, quantite, unite, forfait, tva) {
        const montantHT = quantite * forfait;
        
        // Supprimer la ligne vide si présente
        const emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.remove();

        // Ajouter la ligne au tableau
        const tbody = document.getElementById('lignesDevis');
        const tr = document.createElement('tr');
        tr.id = 'ligne-' + index;
        tr.className = 'border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700';
        tr.innerHTML = `
            <input type="hidden" name="lignes[${index}][code]" value="${code}">
            <input type="hidden" name="lignes[${index}][quantite]" value="${quantite.toFixed(2)}">
            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">${code}</td>
            <td class="px-4 py-3">${designation}</td>
            <td class="px-4 py-3 text-center">
                <input id="quantite-${index}" 
                    class="bg-transparent border-transparent text-center w-20 focus:bg-gray-50 focus:border-gray-300 rounded" 
                    type="number" 
                    step="0.01" 
                    min="0.01"
                    value="${quantite.toFixed(2)}">
            </td>
            <td class="px-4 py-3 text-center">${unite}</td>
            <td class="px-4 py-3 text-right">${forfait.toFixed(2).replace('.', ',')} €</td>
            <td class="px-4 py-3 text-right">${tva.toFixed(2).replace('.', ',')} %</td>
            <td id="montantHT-${index}" class="px-4 py-3 text-right font-medium">${montantHT.toFixed(2).replace('.', ',')} €</td>
            <td class="px-4 py-3 text-center">
                <button type="button" onclick="supprimerLigne(${index})" 
                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </td>
        `;
        tbody.appendChild(tr);

        // Ajouter l'événement de changement de quantité
        const qtyInput = document.getElementById('quantite-' + index);
        const hiddenQty = tr.querySelector(`input[name="lignes[${index}][quantite]"]`);
        
        qtyInput.addEventListener('input', (e) => {
            const newQuantite = parseFloat(e.target.value);
            if (newQuantite > 0) {
                const ligne = lignesDevis.find(l => l.index === index);
                if (ligne) {
                    ligne.quantite = newQuantite;
                    ligne.montantHT = newQuantite * ligne.forfait;
                    hiddenQty.value = newQuantite.toFixed(2);
                    document.getElementById('montantHT-' + index).textContent = 
                        ligne.montantHT.toFixed(2).replace('.', ',') + ' €';
                    calculerTotaux();
                }
            }
        });
    }

    // Ajout d'une ligne au devis
    document.getElementById('btnAddLine').addEventListener('click', () => {
        const code = document.getElementById('selectedCode').value;
        const designation = document.getElementById('selectedDesignation').value;
        const forfait = parseFloat(document.getElementById('selectedForfait').value);
        const tva = parseFloat(document.getElementById('selectedTva').value);
        const quantite = parseFloat(document.getElementById('quantite').value);
        const unite = document.getElementById('unite').value;

        if (!code || !quantite || quantite <= 0) {
            alert('Veuillez sélectionner un article et saisir une quantité valide.');
            return;
        }

        const montantHT = quantite * forfait;

        // Ajouter la ligne aux données
        lignesDevis.push({
            index: ligneIndex,
            code, designation, quantite, unite, forfait, tva, montantHT
        });

        ajouterLigneDOM(ligneIndex, code, designation, quantite, unite, forfait, tva);
        ligneIndex++;

        // Réinitialiser les champs
        searchInput.value = '';
        document.getElementById('quantite').value = '1';
        document.getElementById('unite').value = '';
        document.getElementById('selectedCode').value = '';
        document.getElementById('selectedDesignation').value = '';
        document.getElementById('selectedForfait').value = '';
        document.getElementById('selectedTva').value = '';

        calculerTotaux();
    });

    function supprimerLigne(index) {
        lignesDevis = lignesDevis.filter(l => l.index !== index);
        const tr = document.getElementById('ligne-' + index);
        if (tr) tr.remove();

        if (lignesDevis.length === 0) {
            const tbody = document.getElementById('lignesDevis');
            tbody.innerHTML = `
                <tr id="emptyRow">
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                        Aucun article ajouté. Utilisez la recherche ci-dessus pour ajouter des articles.
                    </td>
                </tr>
            `;
        }

        calculerTotaux();
    }

    function calculerTotaux() {
        let totalHT = 0;
        const tvaMap = {};

        lignesDevis.forEach(ligne => {
            totalHT += ligne.montantHT;
            const tauxTVA = ligne.tva;
            const montantTVA = ligne.montantHT * (tauxTVA / 100);

            if (!tvaMap[tauxTVA]) {
                tvaMap[tauxTVA] = 0;
            }
            tvaMap[tauxTVA] += montantTVA;
        });

        // Afficher le total HT
        document.getElementById('totalHT').textContent = totalHT.toFixed(2).replace('.', ',') + ' €';

        // Afficher le détail TVA
        const detailTVA = document.getElementById('detailTVA');
        detailTVA.innerHTML = '';
        let totalTVA = 0;

        Object.keys(tvaMap).sort((a, b) => parseFloat(a) - parseFloat(b)).forEach(taux => {
            const montant = tvaMap[taux];
            totalTVA += montant;

            const div = document.createElement('div');
            div.className = 'flex justify-between py-2 text-sm';
            div.innerHTML = `
                <span class="text-gray-500 dark:text-gray-400">TVA ${parseFloat(taux).toFixed(2).replace('.', ',')}%</span>
                <span class="text-gray-700 dark:text-gray-300">${montant.toFixed(2).replace('.', ',')} €</span>
            `;
            detailTVA.appendChild(div);
        });

        // Afficher le total TVA
        document.getElementById('totalTVA').textContent = totalTVA.toFixed(2).replace('.', ',') + ' €';

        // Afficher le total TTC
        const totalTTC = totalHT + totalTVA;
        document.getElementById('totalTTC').textContent = totalTTC.toFixed(2).replace('.', ',') + ' €';
    }

    // Impression PDF
    document.getElementById('btnPrintPDF').addEventListener('click', () => {
        if (lignesDevis.length === 0) {
            alert('Veuillez ajouter au moins un article au devis.');
            return;
        }

        // Préparer les données pour le PDF
        const formData = new FormData(document.getElementById('devisForm'));
        formData.append('action', 'print_pdf');
        formData.append('lignes_json', JSON.stringify(lignesDevis));

        // Créer un formulaire temporaire pour le POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'generer_pdf.php';
        form.target = '_blank';

        for (const [key, value] of formData.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });
</script>