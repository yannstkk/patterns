function chargeOnglet(id, bouton) {
    var allOnglet = document.querySelectorAll('.onglet');
    var AllContent = document.querySelectorAll('.contenu');

    for (var i = 0; i < allOnglet.length; i++) {
        allOnglet[i].classList.remove('actif');
    }
    for (var i = 0; i < AllContent.length; i++) {
        AllContent[i].classList.remove('actif');
    }

    bouton.classList.add('actif');
    document.getElementById(id).classList.add('actif');
}

function updateOnglets() {
    var casesPays = document.querySelectorAll('.pays-liste input[type="checkbox"]');
    var nbSelected = 0;
    var firstCountry = null;

    for (var i = 0; i < casesPays.length; i++) {
        var pays = casesPays[i].value;
        var onglet  = document.getElementById('onglet-' + pays);
        var contenu = document.getElementById(pays);

        if (casesPays[i].checked) {
            onglet.style.display = '';
            nbSelected++;
            if (firstCountry == null) {
                firstCountry = pays;
            }
        } else {
            onglet.style.display = 'none';
            onglet.classList.remove('actif');
            contenu.classList.remove('actif');
        }
    }

    var message = document.getElementById('message-aucun-pays');
    message.style.display = (nbSelected == 0) ? 'block' : 'none';

    var ongletActif = document.querySelector('.onglet.actif');
    if (ongletActif == null && firstCountry != null) {
        chargeOnglet(firstCountry, document.getElementById('onglet-' + firstCountry));
    }
    checkPublishButton();
}

function checkImageDimension(fichier, slot, pays) {
    var span = slot.querySelector('span'); 
    var dataSize = slot.getAttribute('data-size'); 

    if (!dataSize || dataSize.trim() === '') {
        span.textContent = fichier.name;
        span.style.color = '#2e7d32';
        updateCounter(pays);
        return;
    }

    var parts = dataSize.toLowerCase().split('x');
    var Width = parseInt(parts[0]);
    var Heigth = parseInt(parts[1]);

    var img = new Image();
    var url = URL.createObjectURL(fichier);
    img.src = url;

    img.onload = function () {
        var trueWidth = img.naturalWidth;
        var trueHeigth = img.naturalHeight;

        if (trueWidth === Width && trueHeigth === Heigth) {
            span.textContent = fichier.name;
            span.style.color = '#2e7d32';
        } else {
            span.textContent = ' Incorrect size : ' + trueWidth + 'x' + trueHeigth
                             + ' (expected : ' + Width + 'x' + Heigth + ')';
            span.style.color = '#cc0000';
            slot.querySelector('input[type="file"]').value = '';
        }

        URL.revokeObjectURL(url);
        updateCounter(pays);
    };
}

function updateCounter(pays) {
    var contenu = document.getElementById(pays);
    if (!contenu) return;
    var AllSlots = contenu.querySelectorAll('.slot-input');
    var missing = 0;

    for (var i = 0; i < AllSlots.length; i++) {
        var slotIndex = parseInt(AllSlots[i].getAttribute('data-slot-index'));
        var fileInput = AllSlots[i].querySelector('input[type="file"]');
        var hasNewFile    = fileInput && fileInput.files.length > 0;
        var hasSavedFile  = savedImages[pays] && savedImages[pays][slotIndex];

        if (!hasNewFile && !hasSavedFile) {
            missing++;
        }
    }

    var counter = document.getElementById('compteur-' + pays);
    if (counter != null) {
        counter.textContent = missing;
    }
    checkPublishButton();
}

document.addEventListener('DOMContentLoaded', function () {

    updateOnglets();

    var AllSlots = document.querySelectorAll('.slot-input');
    var statut = document.querySelector('input[name="statut_actuel"]').value;
    var countrySlotIndex = {};

    for (var i = 0; i < AllSlots.length; i++) {

        var pays = AllSlots[i].closest('.contenu').id;
        if (!countrySlotIndex[pays]) countrySlotIndex[pays] = 0;
        var slotIndex = countrySlotIndex[pays]++;

        // On stocke l'index sur le DOM pour y accéder depuis updateCounter et checkPublishButton
        AllSlots[i].setAttribute('data-slot-index', slotIndex);

        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png';
        input.style.display = 'none';
        input.name = 'images[' + pays + '][' + slotIndex + ']';

        if (savedImages[pays] && savedImages[pays][slotIndex]) {
            var span = AllSlots[i].querySelector('span');
            span.textContent = savedImages[pays][slotIndex];
            span.style.color = '#2e7d32';
        }

        var checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.style.cursor = 'pointer';
        checkbox.style.flexShrink = '0';

        checkbox.addEventListener('change', function() {
            checkPublishButton();
        });

        var wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap = '6px';
        wrapper.style.width = '100%';

        AllSlots[i].parentElement.insertBefore(wrapper, AllSlots[i]);
        wrapper.appendChild(AllSlots[i]);

        if (statut === 'pre-prod' || statut === 'prod') {
            wrapper.appendChild(checkbox);
        }

        AllSlots[i].appendChild(input);
        AllSlots[i].style.cursor = 'pointer';
        AllSlots[i].style.flex = '1';

        AllSlots[i].addEventListener('click', function (e) {
            if (e.target.tagName === 'INPUT') return;
            this.querySelector('input[type="file"]').click();
        });

        input.addEventListener('change', function () {
            var slot = this.parentElement;
            var pays = slot.closest('.contenu').id;

            if (this.files.length > 0) {
                checkImageDimension(this.files[0], slot, pays);
            } else {
                slot.querySelector('span').textContent = 'Add picture';
                slot.querySelector('span').style.color = '#aaa';
                updateCounter(pays);
            }
        });
    }

    document.querySelector('input[name="nom_projet"]').addEventListener('input', checkPublishButton);
    document.querySelector('input[name="link"]').addEventListener('input', checkPublishButton);
    document.querySelector('input[name="launching_date"]').addEventListener('change', checkPublishButton);
    document.querySelector('input[name="result_date"]').addEventListener('change', checkPublishButton);
    document.querySelector('input[name="end_date"]').addEventListener('change', checkPublishButton);
    document.querySelectorAll('.pays-liste input[type="checkbox"]').forEach(function(cb) {
        cb.addEventListener('change', checkPublishButton);
    });

    // Initialise les compteurs au chargement (images déjà en session)
    ['france', 'uk', 'italy', 'spain'].forEach(function(pays) {
        updateCounter(pays);
    });
});

var filtreActif = 'all';

function filtrer(type, bouton) {
    filtreActif = type;
    document.querySelectorAll('.filtre-btn').forEach(b => b.classList.remove('actif'));
    bouton.classList.add('actif');
    appliquerFiltres();
}

function appliquerFiltres() {
    var recherche = document.getElementById('champ-recherche').value.toLowerCase();
    document.querySelectorAll('#corps-tableau tr').forEach(function(ligne) {
        var typeOk = filtreActif === 'all' || ligne.getAttribute('data-type') === filtreActif;
        var rechercheOk = recherche === '' || ligne.textContent.toLowerCase().includes(recherche);
        ligne.style.display = (typeOk && rechercheOk) ? '' : 'none';
    });
}


function checkPublishButton() {
    var statutEl = document.querySelector('input[name="statut_actuel"]');
    if (!statutEl) return;
    var statut = statutEl.value;
    var btn = document.querySelector('.btn-publish');
    if (!btn) return;

    btn.disabled = true;
    btn.classList.remove('preprod', 'prod');

    // ── Statut draft : vérification pour autoriser Pre-publish ──
    if (statut === 'draft') {

        btn.textContent = 'Pre-publish';

        // 1. Champs texte obligatoires (inclut maintenant "link")
        var nomProjet    = document.querySelector('input[name="nom_projet"]').value.trim();
        var link         = document.querySelector('input[name="link"]').value.trim();
        var launchingDate = document.querySelector('input[name="launching_date"]').value.trim();
        var resultDate   = document.querySelector('input[name="result_date"]').value.trim();
        var endDate      = document.querySelector('input[name="end_date"]').value.trim();

        if (!nomProjet || !link || !launchingDate || !resultDate || !endDate) return;

        // 2. Au moins un pays coché
        var checked = document.querySelectorAll('.pays-liste input[type="checkbox"]:checked');
        if (checked.length === 0) return;

        // 3. Toutes les images des pays sélectionnés doivent être uploadées
        //    (nouveau fichier OU déjà sauvegardé en session via savedImages)
        var allFilled = true;

        checked.forEach(function(cb) {
            var pays    = cb.value;
            var contenu = document.getElementById(pays);
            if (!contenu) return;

            contenu.querySelectorAll('.slot-input').forEach(function(slot) {
                var slotIndex    = parseInt(slot.getAttribute('data-slot-index'));
                var fileInput    = slot.querySelector('input[type="file"]');
                var hasNewFile   = fileInput && fileInput.files.length > 0;
                var hasSavedFile = savedImages[pays] && savedImages[pays][slotIndex];

                if (!hasNewFile && !hasSavedFile) {
                    allFilled = false;
                }
            });
        });

        if (allFilled) {
            btn.disabled = false;
            btn.classList.add('preprod');
        }

    // ── Statut pre-prod : vérification pour autoriser Publish ──
    } else if (statut === 'pre-prod') {

        btn.textContent = 'Publish';

        var checked = document.querySelectorAll('.pays-liste input[type="checkbox"]:checked');
        if (checked.length === 0) return;

        var allChecked = true;

        checked.forEach(function(cb) {
            var contenu = document.getElementById(cb.value);
            if (!contenu) return;
            contenu.querySelectorAll('input[type="checkbox"]').forEach(function(chk) {
                if (!chk.checked) allChecked = false;
            });
        });

        if (allChecked) {
            btn.disabled = false;
            btn.classList.add('prod');
        }
    }
}