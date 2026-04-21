function chargeOnglet(id, bouton) {
    document.querySelectorAll('.onglet').forEach(function(o) { o.classList.remove('actif'); });
    document.querySelectorAll('.contenu').forEach(function(c) { c.classList.remove('actif'); });
    bouton.classList.add('actif');
    document.getElementById(id).classList.add('actif');
}

function getChecked() {
    var cb = {
        france: document.querySelector('.pays-liste input[value="france"]'),
        uk: document.querySelector('.pays-liste input[value="uk"]'),
        italy:  document.querySelector('.pays-liste input[value="italy"]'),
        others: document.querySelector('.pays-liste input[value="others"]')
    };
    return {
        france: !!(cb.france && cb.france.checked),
        uk: !!(cb.uk && cb.uk.checked),
        italy: !!(cb.italy  && cb.italy.checked),
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
    toggleOnglet('uk', c.uk || c.others);
    toggleOnglet('italy',  c.italy);
    toggleOnglet('spain',  c.others);

    var nbVisible = (c.france || c.others ? 1 : 0) + (c.uk || c.others ? 1 : 0) + (c.italy ? 1 : 0) + (c.others ? 1 : 0);
    document.getElementById('message-aucun-pays').style.display = nbVisible === 0 ? 'block' : 'none';

    var actif = document.querySelector('.onglet.actif');
    var actifOk = actif && actif.style.display !== 'none';
    if (!actifOk) {
        var premiers = ['france', 'uk', 'italy', 'spain'];
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
    var c= getChecked();
    var slotEl = slotInput.closest ? slotInput.closest('.slot') : null;
    if (!slotEl) return false;
    var src = slotEl.getAttribute('data-source');
    if (!src) return false;
    if (src === 'france') return c.france;
    if (src === 'uk') return c.uk;
    if (src === 'others') return c.others;
    if (src === 'italy') return c.italy;
    return false;
}

function getRequiredSlots() {
    var c = getChecked();
    var required = {};
    var i;

    if (c.france) {
        required['france'] = required['france'] || [];
        for (i = 0; i <= 6; i++)  required['france'].push(i);
        for (i = 12; i <= 18; i++) required['france'].push(i);
    }

    if (c.uk) {
        required['uk'] = [];
        for (i = 0; i <= 17; i++) required['uk'].push(i);
    }

    if (c.others) {
        required['france'] = required['france'] || [];
        for (i = 7; i <= 11; i++) required['france'].push(i);
        for (i = 19; i <= 23; i++) required['france'].push(i);
        required['spain'] = [];
        for (i = 0; i <= 9; i++) required['spain'].push(i);
    }
    if (c.italy) {
    required['italy'] = [0, 1, 2, 3];
    }

    return required;
}

function eventInfoFilled() {
    var nom = document.querySelector('input[name="nom_projet"]');
    var link = document.querySelector('input[name="link"]');
    var launch = document.querySelector('input[name="launching_date"]');
    var result = document.querySelector('input[name="result_date"]');
    var end = document.querySelector('input[name="end_date"]');

    if (!nom || !nom.value.trim()) return false;
    if (link && !link.value.trim())   return false;
    if (launch && !launch.value.trim()) return false;
    if (result && !result.value.trim()) return false;
    if (end && !end.value.trim()) return false;
    return true;
}

function countrySelected() {
    var c = getChecked();
    return c.france || c.uk || c.italy || c.others;
}

function allImagesFilled() {
    var c = getChecked();
    if (!c.france && !c.uk && !c.italy && !c.others) return false;

    var required = getRequiredSlots();

    for (var pays in required) {
        var indices = required[pays];
        for (var i = 0; i < indices.length; i++) {
            var idx = indices[i];
            var hasSaved = savedImages[pays] && savedImages[pays][idx] !== undefined;
            if (!hasSaved) return false;
        }
    }
    return true;
}

function setBtn(btn, enabled, extraClass) {
    if (!btn) return;
    btn.disabled = !enabled;
    btn.style.opacity = enabled ? '1' : '0.4';
    btn.style.cursor = enabled ? 'pointer' : 'not-allowed';
    if (extraClass !== undefined) {
        btn.classList.remove('preprod', 'prod');
        if (enabled) btn.classList.add(extraClass);
    }
}

function checkImageDimension(file, slot, pays, slotIndex, siteName, loginLogout) {
    var dataSize = slot.getAttribute('data-size');
    if (!dataSize || dataSize.trim() === '') {
        uploadImage(file, slot, pays, slotIndex, siteName, loginLogout);
        return;
    }
    var parts  = dataSize.toLowerCase().split('x');
    var width  = parseInt(parts[0]);
    var height = parseInt(parts[1]);
    var img = new Image();
    var url = URL.createObjectURL(file);
    img.src = url;
    img.onload = function() {
        URL.revokeObjectURL(url);
        if (img.naturalWidth === width && img.naturalHeight === height) {
            uploadImage(file, slot, pays, slotIndex, siteName, loginLogout);
        } else {
            var span = slot.querySelector('span');
            span.textContent = 'Incorrect size : ' + img.naturalWidth + 'x' + img.naturalHeight + ' (expected : ' + width + 'x' + height + ')';
            span.style.color = '#cc0000';
            slot.querySelector('input[type="file"]').value = '';
            var indicator = slot.parentElement.querySelector('.slot-indicator');
            if (indicator) { indicator.innerHTML = '<img src="./img/imageNotOk.png" style="width:13px;height:13px;">'; indicator.style.display = ''; }
            updateCounter(pays);
        }
    };
}



function uploadImage(file, slot, pays, slotIndex, siteName, loginLogout) {
    var span = slot.querySelector('span');
    if (!eventId) {
        span.textContent = 'Save the form first to upload images';
        span.style.color = '#cc0000';
        return;
    }

    var originalName = file.name.replace(/\.[^.]+$/, '').toLowerCase()
                                .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

    span.textContent = 'Uploading...';
    span.style.color = '#888';

    var formData = new FormData();
    formData.append('image', file);
    formData.append('pays', pays);
    formData.append('slot_index', slotIndex);
    formData.append('site_name', siteName);
    formData.append('login_logout', loginLogout);
    formData.append('original_name',  originalName);

    fetch('./validation/upload_image.php', { method: 'POST', body: formData })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        var indicator = slot.parentElement.querySelector('.slot-indicator');
        if (data.success) {
            span.textContent = data.filename;
            span.style.color = '#2e7d32';
            if (!savedImages[pays]) savedImages[pays] = {};
            savedImages[pays][slotIndex] = data.filename;
            if (indicator) { indicator.innerHTML = '<img src="./img/imageOk.png" style="width:13px;height:13px;">'; indicator.style.display = ''; }
        } else {
            span.textContent = data.error;
            span.style.color = '#cc0000';
            if (indicator) { indicator.innerHTML = '<img src="./img/imageNotOk.png" style="width:13px;height:13px;">'; indicator.style.display = ''; }
        }
        updateCounter(pays);
    })
    .catch(function() {
        span.textContent = 'Network error';
        span.style.color = '#cc0000';
        var indicator = slot.parentElement.querySelector('.slot-indicator');
        if (indicator) { indicator.innerHTML = '<img src="./img/imageNotOk.png" style="width:13px;height:13px;">'; indicator.style.display = ''; }
        updateCounter(pays);
    });
}

function updateCounter(pays) {
    var contenu = document.getElementById(pays);
    if (!contenu) return;
    var missing = 0;
    contenu.querySelectorAll('.slot-input').forEach(function(slot) {
        if (!isSlotRequired(slot)) return;
        var idx = parseInt(slot.getAttribute('data-slot-index'));
        var fileInput = slot.querySelector('input[type="file"]');
        var hasNewFile = fileInput && fileInput.files.length > 0;
        var hasSavedFile = savedImages[pays] && savedImages[pays][idx] !== undefined;
        if (!hasNewFile && !hasSavedFile) missing++;
    });
    var counter = document.getElementById('compteur-' + pays);
    if (counter) counter.textContent = missing;
    checkPublishButton();
}

document.addEventListener('DOMContentLoaded', function() {

    var allSlots = document.querySelectorAll('.slot-input');
    var statut = document.querySelector('input[name="statut_actuel"]').value;
    var countrySlotIndex = {};

    allSlots.forEach(function(slot) {
        var pays = slot.closest('.contenu').id;
        if (!countrySlotIndex[pays]) countrySlotIndex[pays] = 0;
        var slotIndex = countrySlotIndex[pays]++;
        var parentSlot  = slot.parentElement; 
        var siteName = parentSlot.getAttribute('data-site') || 'SITE';
        var loginLogout = parentSlot.getAttribute('data-mode') || 'na';

        slot.setAttribute('data-slot-index', slotIndex);

        if (savedImages[pays] && savedImages[pays][slotIndex] !== undefined) {
            var span = slot.querySelector('span');
            span.textContent = savedImages[pays][slotIndex];
            span.style.color = '#2e7d32';
        }

        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png';
        input.style.display = 'none';
        slot.appendChild(input);
        slot.style.cursor = 'pointer';
        slot.style.flex = '1';

        var icon = document.createElement('img');
        icon.src = './img/AddPngPicture.png';
        icon.style.width = '14px';
        icon.style.height = '15px';
        icon.style.flexShrink = '0';
        icon.style.pointerEvents = 'none';
        slot.appendChild(icon);

        var wrapper = document.createElement('div'); 
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap = '6px';
        wrapper.style.width = '100%';

        slot.parentElement.insertBefore(wrapper, slot);
        wrapper.appendChild(slot);

        if (statut === 'draft') {
            var indicator = document.createElement('span');
            indicator.className = 'slot-indicator';
            indicator.style.flexShrink = '0';
            indicator.style.fontSize = '16px';
            indicator.style.display = 'none';
            wrapper.appendChild(indicator); 
        }

        if (statut === 'pre-prod') {
            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.style.cursor = 'pointer';
            checkbox.style.flexShrink = '0';

            var filename = savedImages[pays] && savedImages[pays][slotIndex] !== undefined
                        ? savedImages[pays][slotIndex] : null;
            if (filename && checkedImages[filename] === 1) {
                checkbox.checked = true;
            }

            checkbox.addEventListener('change', function() {
                checkPublishButton();

                var fname = savedImages[pays] && savedImages[pays][slotIndex] !== undefined
                            ? savedImages[pays][slotIndex] : null;
                if (!fname) return;

                var formData = new FormData();
                formData.append('name_image', fname);
                formData.append('checked', checkbox.checked ? '1' : '0');

                fetch('./validation/update_image_checked.php', { method: 'POST', body: formData })
                    .catch(function() { console.error('Checkbox sync failed'); });
            });

            wrapper.appendChild(checkbox);
        }

        if (statut === 'prod') {
            slot.style.opacity = '0.4';
            slot.style.pointerEvents = 'none';

            var cadenas = document.createElement('span');
            cadenas.textContent = '🔒';
            cadenas.style.cursor = 'pointer';
            cadenas.style.flexShrink = '0';
            cadenas.style.fontSize = '15px';
            cadenas.title = 'Cliquer pour déverrouiller';
            cadenas.dataset.locked = 'true';

            cadenas.addEventListener('click', function() {
                var isLocked = cadenas.dataset.locked === 'true';
                if (isLocked) {
                    cadenas.textContent = '🔓';
                    cadenas.dataset.locked = 'false';
                    cadenas.title = 'Cliquer pour verrouiller';
                    slot.style.opacity = '1';
                    slot.style.pointerEvents = '';
                } else {
                    cadenas.textContent = '🔒';
                    cadenas.dataset.locked = 'true';
                    cadenas.title = 'Cliquer pour déverrouiller';
                    slot.style.opacity = '0.4';
                    slot.style.pointerEvents = 'none';
                }
            });

            wrapper.appendChild(cadenas);

        }


        slot.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return;
            this.querySelector('input[type="file"]').click();
        });

        (function(capturedSlot, capturedPays, capturedIndex, capturedSite, capturedMode) {
        input.addEventListener('change', function() {
            if (this.files.length === 0) {
                capturedSlot.querySelector('span').textContent = 'Add PNG picture';
                capturedSlot.querySelector('span').style.color = '#aaa';
                updateCounter(capturedPays);
                return;
            }
            checkImageDimension(this.files[0], capturedSlot, capturedPays, capturedIndex, capturedSite, capturedMode);
        });
    })(slot, pays, slotIndex, siteName, loginLogout);
});

    var nomInput = document.querySelector('input[name="nom_projet"]');
    var linkInput = document.querySelector('input[name="link"]');
    var launchInput = document.querySelector('input[name="launching_date"]');
    var resultInput = document.querySelector('input[name="result_date"]');
    var endInput = document.querySelector('input[name="end_date"]');

    if (nomInput) nomInput.addEventListener('input', checkPublishButton);
    if (linkInput) linkInput.addEventListener('input', checkPublishButton);
    if (launchInput) launchInput.addEventListener('change', checkPublishButton);
    if (resultInput) resultInput.addEventListener('change', checkPublishButton);
    if (endInput) endInput.addEventListener('change', checkPublishButton);

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
    var champ = document.getElementById('champ-recherche');
    var recherche = champ ? champ.value.toLowerCase() : '';
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
    var btnSave = document.querySelector('.btn-save');
    var btnPub = document.querySelector('.btn-publish');

    var infoOk = eventInfoFilled();
    var countryOk = countrySelected();
    var imagesOk = allImagesFilled();
    var baseOk = infoOk && countryOk && imagesOk;

    if (statut === 'draft') {
        setBtn(btnSave, true);
        if (btnPub) {
            btnPub.textContent = 'Pre-publish';
            setBtn(btnPub, baseOk, 'preprod');
        }

    } else if (statut === 'pre-prod') {
        setBtn(btnSave, baseOk);
        if (btnPub) {
            btnPub.textContent = 'Publish';
            var allChecked = true;
            document.querySelectorAll('.contenu.actif input[type="checkbox"]').forEach(function(chk) {
                if (!chk.checked) allChecked = false;
            });
            setBtn(btnPub, baseOk && allChecked, 'prod');
        }

   } else if (statut === 'prod') {
    var unlockedAllFilled = true;
    document.querySelectorAll('.contenu span[data-locked="false"]').forEach(function(cadenas) {
        
        var slotEl = cadenas.closest('.wrapper') 
            ? cadenas.parentElement.querySelector('.slot-input')
            : cadenas.previousElementSibling;
        if (!slotEl) return;
        var idx = parseInt(slotEl.getAttribute('data-slot-index'));
        var pays = slotEl.closest('.contenu').id;
        var hasSaved = savedImages[pays] && savedImages[pays][idx] !== undefined;
        var fileInput = slotEl.querySelector('input[type="file"]');
        var hasNew = fileInput && fileInput.files.length > 0;
        if (!hasNew && !hasSaved) unlockedAllFilled = false;
    });
    setBtn(btnSave, infoOk && unlockedAllFilled);
}
}