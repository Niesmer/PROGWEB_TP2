<?php
/**
 * Modal de confirmation Flowbite réutilisable
 * 
 * @param string $modal_id - ID unique du modal
 * @param string $title - Titre du modal
 * @param string $message - Message de confirmation
 * @param string $form_action - Action du formulaire
 * @param array $hidden_inputs - Inputs cachés du formulaire [name => id]
 * @param string $submit_label - Label du bouton de soumission
 * @param string $submit_color - Couleur du bouton (red, yellow, blue, etc.)
 * @param bool $has_cancel - Afficher le bouton annuler (défaut: true)
 */

$modal_id = $modal_id ?? 'confirmModal';
$title = $title ?? 'Confirmer l\'action';
$message = $message ?? 'Êtes-vous sûr de vouloir effectuer cette action ?';
$form_action = $form_action ?? '#';
$hidden_inputs = $hidden_inputs ?? [];
$submit_label = $submit_label ?? 'Confirmer';
$submit_color = $submit_color ?? 'red';
$has_cancel = $has_cancel ?? true;
$warning_text = $warning_text ?? 'Cette action est irréversible.';
$show_warning = $show_warning ?? true;
?>

<div id="<?= $modal_id ?>" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <button type="button" onclick="document.getElementById('<?= $modal_id ?>').classList.add('hidden')" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
                <span class="sr-only">Fermer</span>
            </button>
            <div class="p-4 md:p-5 text-center">
                <svg class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
                    <?= $title ?>
                </h3>
                <?php if ($show_warning): ?>
                    <p class="mb-5 text-sm text-red-600 dark:text-red-400">
                        <?= $warning_text ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($message)): ?>
                    <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                        <?= $message ?>
                    </p>
                <?php endif; ?>
                <form method="post" action="<?= $form_action ?>" class="inline-flex flex-col sm:flex-row gap-3 w-full justify-center">
                    <?php foreach ($hidden_inputs as $name => $input_id): ?>
                        <input type="hidden" name="<?= htmlspecialchars($name) ?>" id="<?= htmlspecialchars($input_id) ?>">
                    <?php endforeach; ?>
                    <button type="submit" 
                        class="text-white bg-<?= $submit_color ?>-600 hover:bg-<?= $submit_color ?>-800 focus:ring-4 focus:outline-none focus:ring-<?= $submit_color ?>-300 dark:focus:ring-<?= $submit_color ?>-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center justify-center">
                        <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <?= $submit_label ?>
                    </button>
                    <?php if ($has_cancel): ?>
                        <button type="button" onclick="document.getElementById('<?= $modal_id ?>').classList.add('hidden')" 
                            class="py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                            Annuler
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
