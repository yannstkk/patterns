function chargeOnglet(id, bouton) {
    document.querySelectorAll('.onglet').forEach(function(o) { o.classList.remove('actif'); });
    document.querySelectorAll('.contenu').forEach(function(c) { c.classList.remove('actif'); });
    bouton.classList.add('actif');
    document.getElementById(id).classList.add('actif');
}

function getChecked() {
    var cb = {
        france: document.querySelector('.pays-liste input[value="france"]'),
        uk:     document.querySelector('.pays-liste input[value="uk"]'),
        italy:  document.querySelector('.pays-liste input[value="italy"]'),
        others: document.querySelector('.pays-liste input[value="others"]')
    };
    return {
        france: !!(cb.france && cb.france.checked),
        uk:     !!(cb.uk     && cb.uk.checked),
        italy:  !!(cb.italy  && cb.italy.checked),
        others: !!(cb.others && cb.others.checked)
    };
}

function updateSlotSources() {
    var c = getChecked();
    document.querySelectorAll('.slot[data-source]').forEach(function(s) {
        var src = s.getAttribute('data-source');
        var show = (src === 'france' && c.france)
                || (src === 'uk'     && c.uk)
                || (src === 'others' && c.others)
                || (src === 'italy'  && c.italy);
        s.style.display = show ? '' : 'none';
    });
}

function toggleOnglet(pays, visible) {
    var onglet  = document.getElementById('onglet-' + pays);
    var contenu = document.getElementById(pays);
    if (!onglet) return;
    if (visible) {
        onglet.style.display = '';
    } else {
        onglet.style.display = 'none';
        onglet.classList.remove('actif');
        if (contenu) contenu.classList.remove('actif');
    }
}

function updateOnglets() {
    var c = getChecked();

    updateSlotSources();

    toggleOnglet('france', c.france || c.others);
    toggleOnglet('uk',     c.uk     || c.others);
    toggleOnglet('italy',  c.italy);
    toggleOnglet('spain',  c.others);

    var nbVisible = (c.france || c.others ? 1 : 0) + (c.uk || c.others ? 1 : 0) + (c.italy ? 1 : 0) + (c.others ? 1 : 0);
    document.getElementById('message-aucun-pays').style.display = nbVisible === 0 ? 'block' : 'none';

    var actif = document.querySelector('.onglet.actif');
    var actifOk = actif && actif.style.display !== 'none';
    if (!actifOk) {
        var premiers = ['france','uk','italy','spain'];
        for (var i = 0; i < premiers.length; i++) {
            var o = document.getElementById('onglet-' + premiers[i]);
            if (o && o.style.display !== 'none') {
                chargeOnglet(premiers[i], o);
                break;
            }
        }
    }

    checkPublishButton();
}

function isSlotRequired(slotInput) {
    var c      = getChecked();
    var slotEl = slotInput.closest ? slotInput.closest('.slot') : null;
    if (!slotEl) return true;
    var src = slotEl.getAttribute('data-source');
    if (!src) return true;
    if (src === 'france') return c.france;
    if (src === 'uk')     return c.uk;
    if (src === 'others') return c.others;
    if (src === 'italy')  return c.italy;
    return true;
}

function checkImageDimension(file, slot, pays, slotIndex) {
    var dataSize = slot.getAttribute('data-size');
    if (!dataSize || dataSize.trim() === '') {
        uploadImage(file, slot, pays, slotIndex);
        return;
    }
    var parts  = dataSize.toLowerCase().split('x');
    var width  = parseInt(parts[0]);
    var height = parseInt(parts[1]);
    var img    = new Image();
    var url    = URL.createObjectURL(file);
    img.src    = url;
    img.onload = function() {
        URL.revokeObjectURL(url);
        if (img.naturalWidth === width && img.naturalHeight === height) {
            uploadImage(file, slot, pays, slotIndex);
        } else {
            var span = slot.querySelector('span');
            span.textContent = 'Incorrect size : ' + img.naturalWidth + 'x' + img.naturalHeight + ' (expected : ' + width + 'x' + height + ')';
            span.style.color = '#cc0000';
            slot.querySelector('input[type="file"]').value = '';
            updateCounter(pays);
        }
    };
}

function uploadImage(file, slot, pays, slotIndex) {
    var span   = slot.querySelector('span');
    var folder = slot.getAttribute('data-folder') || '';
    if (!eventId) {
        span.textContent = 'Save the form first to upload images';
        span.style.color = '#cc0000';
        return;
    }
    span.textContent = 'Uploading...';
    span.style.color = '#888';
    var formData = new FormData();
    formData.append('image',      file);
    formData.append('pays',       pays);
    formData.append('slot_index', slotIndex);
    formData.append('dossier',    folder);
    fetch('./validation/upload_image.php', { method: 'POST', body: formData })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            span.textContent = data.filename;
            span.style.color = '#2e7d32';
            if (!savedImages[pays]) savedImages[pays] = {};
            savedImages[pays][slotIndex] = data.filename;
        } else {
            span.textContent = data.error;
            span.style.color = '#cc0000';
        }
        updateCounter(pays);
    })
    .catch(function() {
        span.textContent = 'Network error';
        span.style.color = '#cc0000';
        updateCounter(pays);
    });
}

function updateCounter(pays) {
    var contenu = document.getElementById(pays);
    if (!contenu) return;
    var missing = 0;
    contenu.querySelectorAll('.slot-input').forEach(function(slot) {
        if (!isSlotRequired(slot)) return;
        var idx          = parseInt(slot.getAttribute('data-slot-index'));
        var fileInput    = slot.querySelector('input[type="file"]');
        var hasNewFile   = fileInput && fileInput.files.length > 0;
        var hasSavedFile = savedImages[pays] && savedImages[pays][idx];
        if (!hasNewFile && !hasSavedFile) missing++;
    });
    var counter = document.getElementById('compteur-' + pays);
    if (counter) counter.textContent = missing;
    checkPublishButton();
}

document.addEventListener('DOMContentLoaded', function() {

    var allSlots         = document.querySelectorAll('.slot-input');
    var statut           = document.querySelector('input[name="statut_actuel"]').value;
    var countrySlotIndex = {};

    allSlots.forEach(function(slot) {
        var pays = slot.closest('.contenu').id;
        if (!countrySlotIndex[pays]) countrySlotIndex[pays] = 0;
        var slotIndex = countrySlotIndex[pays]++;

        slot.setAttribute('data-slot-index', slotIndex);

        if (savedImages[pays] && savedImages[pays][slotIndex]) {
            var span = slot.querySelector('span');
            span.textContent = savedImages[pays][slotIndex];
            span.style.color = '#2e7d32';
        }

        var input         = document.createElement('input');
        input.type        = 'file';
        input.accept      = 'image/png';
        input.style.display = 'none';
        slot.appendChild(input);
        slot.style.cursor = 'pointer';
        slot.style.flex   = '1';

        var checkbox              = document.createElement('input');
        checkbox.type             = 'checkbox';
        checkbox.style.cursor     = 'pointer';
        checkbox.style.flexShrink = '0';
        checkbox.addEventListener('change', checkPublishButton);

        var wrapper              = document.createElement('div');
        wrapper.style.display    = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap        = '6px';
        wrapper.style.width      = '100%';

        slot.parentElement.insertBefore(wrapper, slot);
        wrapper.appendChild(slot);
        if (statut === 'pre-prod' || statut === 'prod') {
            wrapper.appendChild(checkbox);
        }

        slot.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return;
            this.querySelector('input[type="file"]').click();
        });

        (function(capturedSlot, capturedPays, capturedIndex) {
            input.addEventListener('change', function() {
                if (this.files.length === 0) {
                    capturedSlot.querySelector('span').textContent = 'Add PNG picture';
                    capturedSlot.querySelector('span').style.color = '#aaa';
                    updateCounter(capturedPays);
                    return;
                }
                checkImageDimension(this.files[0], capturedSlot, capturedPays, capturedIndex);
            });
        })(slot, pays, slotIndex);
    });

    document.querySelector('input[name="nom_projet"]').addEventListener('input', checkPublishButton);
    document.querySelector('input[name="link"]').addEventListener('input', checkPublishButton);
    document.querySelector('input[name="launching_date"]').addEventListener('change', checkPublishButton);
    document.querySelector('input[name="result_date"]').addEventListener('change', checkPublishButton);
    document.querySelector('input[name="end_date"]').addEventListener('change', checkPublishButton);
    document.querySelectorAll('.pays-liste input[type="checkbox"]').forEach(function(cb) {
        cb.addEventListener('change', checkPublishButton);
    });

    updateOnglets();
    ['france', 'uk', 'italy', 'spain'].forEach(updateCounter);
});

var filtreActif = 'all';

function filtrer(type, bouton) {
    filtreActif = type;
    document.querySelectorAll('.filtre-btn').forEach(function(b) { b.classList.remove('actif'); });
    bouton.classList.add('actif');
    appliquerFiltres();
}

function appliquerFiltres() {
    var champ     = document.getElementById('champ-recherche');
    var recherche = champ ? champ.value.toLowerCase() : '';
    document.querySelectorAll('#corps-tableau tr').forEach(function(ligne) {
        var typeOk      = filtreActif === 'all' || ligne.getAttribute('data-type') === filtreActif;
        var rechercheOk = recherche === '' || ligne.textContent.toLowerCase().includes(recherche);
        ligne.style.display = (typeOk && rechercheOk) ? '' : 'none';
    });
}

function checkPublishButton() {
    var statutEl = document.querySelector('input[name="statut_actuel"]');
    if (!statutEl) return;
    var statut = statutEl.value;
    var btn    = document.querySelector('.btn-publish');
    if (!btn) return;

    btn.disabled = true;
    btn.classList.remove('preprod', 'prod');

    if (statut === 'draft') {
        btn.textContent = 'Pre-publish';

        var nomProjet     = document.querySelector('input[name="nom_projet"]').value.trim();
        var link          = document.querySelector('input[name="link"]').value.trim();
        var launchingDate = document.querySelector('input[name="launching_date"]').value.trim();
        var resultDate    = document.querySelector('input[name="result_date"]').value.trim();
        var endDate       = document.querySelector('input[name="end_date"]').value.trim();
        if (!nomProjet || !link || !launchingDate || !resultDate || !endDate) return;

        var c = getChecked();
        if (!c.france && !c.uk && !c.italy && !c.others) return;

        var tabsToCheck = [];
        if (c.france || c.others) tabsToCheck.push('france');
        if (c.uk     || c.others) tabsToCheck.push('uk');
        if (c.italy)              tabsToCheck.push('italy');
        if (c.others)             tabsToCheck.push('spain');

        var allFilled = true;
        tabsToCheck.forEach(function(pays) {
            var contenu = document.getElementById(pays);
            if (!contenu) return;
            contenu.querySelectorAll('.slot-input').forEach(function(slot) {
                if (!isSlotRequired(slot)) return;
                var idx          = parseInt(slot.getAttribute('data-slot-index'));
                var fileInput    = slot.querySelector('input[type="file"]');
                var hasNewFile   = fileInput && fileInput.files.length > 0;
                var hasSavedFile = savedImages[pays] && savedImages[pays][idx];
                if (!hasNewFile && !hasSavedFile) allFilled = false;
            });
        });

        if (allFilled) {
            btn.disabled = false;
            btn.classList.add('preprod');
        }

    } else if (statut === 'pre-prod') {
        btn.textContent = 'Publish';
        var allChecked = true;
        document.querySelectorAll('.contenu.actif input[type="checkbox"]').forEach(function(chk) {
            if (!chk.checked) allChecked = false;
        });
        if (allChecked) {
            btn.disabled = false;
            btn.classList.add('prod');
        }
    }
}