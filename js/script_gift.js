var urlsPreProd = {
    'P':{login:'',logout:''},'DCM':{login:'',logout:''},'TDP':{login:'',logout:''},
    'AP':{login:'',logout:''},'APPS':{login:'',logout:''},'MDO':{login:'',logout:''},
    'RS':{login:'',logout:''}
};
var urlsProd = {
    'P':{login:'',logout:''},'DCM':{login:'',logout:''},'TDP':{login:'',logout:''},
    'AP':{login:'',logout:''},'APPS':{login:'',logout:''},'MDO':{login:'',logout:''},
    'RS':{login:'',logout:''}
};


var phaseData = {
    'collection':    { introduction: '', about_association: '', image1: '', image2: '' },
    'pre-donation':  { introduction: '', about_association: '', image1: '', image2: '' },
    'post-donation': { introduction: '', about_association: '', image1: '', image2: '' }
};

var currentPhase = activePhase || 'collection';

function phaseToId(phase) {
    return phase.replace(/-/g, '_');
}


function initPhaseData() {
    ['collection', 'pre-donation', 'post-donation'].forEach(function(ph) {
        var cfg = giftConfig[ph] || {};
        phaseData[ph].introduction = cfg.introduction || '';
        phaseData[ph].about_association = cfg.about_association || '';
        phaseData[ph].image1 = cfg.image1 || '';
        phaseData[ph].image2 = cfg.image2 || '';
    });
}

function saveCurrentPhaseToData() {
    var introEl = document.getElementById('introduction-editor');
    var aboutEl = document.getElementById('about-editor');
    if (introEl) phaseData[currentPhase].introduction      = introEl.innerHTML;
    if (aboutEl) phaseData[currentPhase].about_association = aboutEl.innerHTML;
}

function syncAllHiddenInputs() {
    ['collection', 'pre-donation', 'post-donation'].forEach(function(ph) {
        var pid = phaseToId(ph);
        var introInput = document.getElementById('hidden-intro-' + pid);
        var aboutInput = document.getElementById('hidden-about-' + pid);
        if (introInput) introInput.value = phaseData[ph].introduction;
        if (aboutInput) aboutInput.value = phaseData[ph].about_association;
    });
}

function loadPhaseIntoEditors(phase) {
    var introEl = document.getElementById('introduction-editor');
    var aboutEl = document.getElementById('about-editor');
    if (introEl) introEl.innerHTML = phaseData[phase].introduction;
    if (aboutEl) aboutEl.innerHTML = phaseData[phase].about_association;
}

function updatePhaseImagesDisplay(phase) {

    var img1Name = document.getElementById('phase-image1-name');
    var img2Name = document.getElementById('phase-image2-name');

    if (img1Name) {
        img1Name.textContent = phaseData[phase].image1 || 'Add picture';
        img1Name.style.color = phaseData[phase].image1 ? '#2e7d32' : '#aaa';
    }
    if (img2Name) {
        img2Name.textContent = phaseData[phase].image2 || 'Add picture';
        img2Name.style.color = phaseData[phase].image2 ? '#2e7d32' : '#aaa';
    }
}


function setPhase(phase, btn) {
    saveCurrentPhaseToData();
    syncAllHiddenInputs();

    currentPhase = phase;
    document.getElementById('input-active-phase').value = phase;

    loadPhaseIntoEditors(phase);
    updatePhaseImagesDisplay(phase);

    document.querySelectorAll('.phase-tab').forEach(function(b) { b.classList.remove('phase-tab-actif'); });
    btn.classList.add('phase-tab-actif');
}

function editorCmd(editorId, cmd) {
    document.getElementById(editorId).focus();
    document.execCommand(cmd, false, null);
}

function editorFontSize(editorId, val) {
    document.getElementById(editorId).focus();
    document.execCommand('fontSize', false, val);
}

function openPreview() {
    var intro = document.getElementById('introduction-editor').innerHTML;
    var about = document.getElementById('about-editor').innerHTML;
    var w = window.open('', '_blank');
    w.document.write('<html><body style="font-family:Arial;padding:20px;max-width:700px;margin:auto;">'
        + intro + '<hr>' + about + '</body></html>');
    w.document.close();
}


function uploadGiftAsset(file, assetType, phase) {
    if (!eventId) {
        alert('Save the form first to upload images');
        return;
    }

    var originalName = file.name.replace(/\.[^.]+$/, '').toLowerCase()
                               .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

    var formData = new FormData();
    formData.append('image', file);
    formData.append('asset_type', assetType);
    formData.append('phase', phase || '');
    formData.append('original_name', originalName);

    var nameEl = (assetType === 'image1' || assetType === 'image2')
        ? document.getElementById('phase-' + assetType + '-name')
        : document.querySelector('[data-asset="' + assetType + '"] .gift-asset-name');
    if (nameEl) { nameEl.textContent = 'Uploading…'; nameEl.style.color = '#888'; }

    fetch('./validation/upload_gift_asset.php', { method: 'POST', body: formData })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                var fn = data.filename;
                if (assetType === 'logo' || assetType === 'arriere_plan') {
                    ['collection', 'pre-donation', 'post-donation'].forEach(function(ph) {
                        if (!giftConfig[ph]) giftConfig[ph] = {};
                        giftConfig[ph][assetType] = fn;
                    });
                    var el = document.querySelector('[data-asset="' + assetType + '"] .gift-asset-name');
                    if (el) { el.textContent = fn; el.style.color = '#2e7d32'; }
                } else {
                    var targetPhase = phase || currentPhase;
                    phaseData[targetPhase][assetType] = fn;
                    if (!giftConfig[targetPhase]) giftConfig[targetPhase] = {};
                    giftConfig[targetPhase][assetType] = fn;
                    if (targetPhase === currentPhase) {
                        updatePhaseImagesDisplay(currentPhase);
                    }
                }
            } else {
                if (nameEl) { nameEl.textContent = data.error || 'Error'; nameEl.style.color = '#cc0000'; }
            }
        })
        .catch(function() {
            if (nameEl) { nameEl.textContent = 'Network error'; nameEl.style.color = '#cc0000'; }
        });
}

function checkImageDimension(file, slot, pays, slotIndex, siteName, loginLogout, section) {
    var dataSize = slot.getAttribute('data-size');
    if (!dataSize || dataSize.trim() === '') {
        uploadBannerImage(file, slot, pays, slotIndex, siteName, loginLogout, section);
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
            uploadBannerImage(file, slot, pays, slotIndex, siteName, loginLogout, section);
        } else {
            var span = slot.querySelector('span');
            span.textContent = 'Incorrect size : ' + img.naturalWidth + 'x' + img.naturalHeight
                               + ' (expected : ' + width + 'x' + height + ')';
            span.style.color = '#cc0000';
            slot.querySelector('input[type="file"]').value = '';
            updateGiftCounter(pays);
        }
    };
}

function uploadBannerImage(file, slot, pays, slotIndex, siteName, loginLogout, section) {
    var span = slot.querySelector('span');
    if (!eventId) {
        span.textContent = 'Save the form first to upload images';
        span.style.color = '#cc0000';
        return;
    }
    var originalName = file.name.replace(/\.[^.]+$/, '').toLowerCase()
                                .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    span.textContent = 'Uploading…';
    span.style.color = '#888';

    var formData = new FormData();
    formData.append('image', file);
    formData.append('pays', pays);
    formData.append('slot_index', slotIndex);
    formData.append('site_name', siteName);
    formData.append('login_logout', loginLogout);
    formData.append('section', section || 'main');
    formData.append('original_name', originalName);

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
            updateGiftCounter(pays);
        })
        .catch(function() {
            span.textContent = 'Network error';
            span.style.color = '#cc0000';
            updateGiftCounter(pays);
        });
}

function updateGiftCounter(pays) {
    var contenu = document.getElementById(pays);
    if (!contenu) return;
    var missing = 0;
    var idx = 0;
    contenu.querySelectorAll('.slot-input').forEach(function(slot) {
        var hasSaved = savedImages[pays] && savedImages[pays][idx] !== undefined;
        var fileInput = slot.querySelector('input[type="file"]');
        var hasNew    = fileInput && fileInput.files.length > 0;
        if (!hasNew && !hasSaved) missing++;
        idx++;
    });
    var counter = document.getElementById('compteur-' + pays);
    if (counter) counter.textContent = missing;
}

function chargeGiftOnglet(id, bouton) {
    document.querySelectorAll('.onglet').forEach(function(o)   { o.classList.remove('actif'); });
    document.querySelectorAll('.contenu').forEach(function(c)  { c.classList.remove('actif'); });
    bouton.classList.add('actif');
    document.getElementById(id).classList.add('actif');
}

function updateGiftOnglet(selected) {
    var ongletIds = ['france', 'uk', 'italy', 'others'];
    ongletIds.forEach(function(id) {
        var onglet = document.getElementById('onglet-' + id);
        if (!onglet) return;
        if (id === selected) {
            onglet.style.display = '';
            chargeGiftOnglet(id, onglet);
        } else {
            onglet.style.display = 'none';
        }
    });
    updateGiftCounter(selected);
}

document.addEventListener('DOMContentLoaded', function() {

    initPhaseData();
    loadPhaseIntoEditors(currentPhase);
    updatePhaseImagesDisplay(currentPhase);
    syncAllHiddenInputs();

       ['logo', 'arriere_plan'].forEach(function(asset) {
        var nameEl = document.querySelector('.gift-asset-input[data-asset="' + asset + '"] .gift-asset-name');
        if (nameEl) {
            var txt = nameEl.textContent.trim();
            if (txt !== '' && txt !== 'Add picture') {
                nameEl.style.color = '#2e7d32';
            }
        }
    });

    var checkedRadio = document.querySelector('input[name="pays"]:checked');
    if (checkedRadio) updateGiftOnglet(checkedRadio.value);

    document.getElementById('introduction-editor').addEventListener('input', function() {
        phaseData[currentPhase].introduction = this.innerHTML;
        document.getElementById('hidden-intro-' + phaseToId(currentPhase)).value = this.innerHTML;
    });
    document.getElementById('about-editor').addEventListener('input', function() {
        phaseData[currentPhase].about_association = this.innerHTML;
        document.getElementById('hidden-about-' + phaseToId(currentPhase)).value = this.innerHTML;
    });

    document.getElementById('gift-form').addEventListener('submit', function() {
        saveCurrentPhaseToData();
        syncAllHiddenInputs();
    });

    [
        { btnId: 'gift-logo-btn', fileId: 'gift-logo-file',    asset: 'logo'         },
        { btnId: 'gift-arriere-btn', fileId: 'gift-arriere-file', asset: 'arriere_plan' },
    ].forEach(function(cfg) {
        var btn  = document.getElementById(cfg.btnId);
        var file = document.getElementById(cfg.fileId);
        if (!btn || !file) return;
        btn.style.cursor = 'pointer';
        btn.addEventListener('click', function(e) { if (e.target.tagName !== 'INPUT') file.click(); });
        file.addEventListener('change', function() {
            if (this.files.length === 0) return;
            uploadGiftAsset(this.files[0], cfg.asset, null);
        });
    });

    ['image1', 'image2'].forEach(function(imgKey) {
        var btn  = document.getElementById('phase-' + imgKey + '-btn');
        var file = document.getElementById('phase-' + imgKey + '-file');
        if (!btn || !file) return;
        btn.addEventListener('click', function(e) { if (e.target.tagName !== 'INPUT') file.click(); });
        file.addEventListener('change', function() {
            if (this.files.length === 0) return;
            var self = this; 
            uploadGiftAsset(this.files[0], imgKey, currentPhase);
            self.value = ''; 
        });
    });
    var allSlots = document.querySelectorAll('.slot-input');
    var countrySlotIndex = {};

    allSlots.forEach(function(slot) {
        var pays = slot.closest('.contenu').id;
        if (!countrySlotIndex[pays]) countrySlotIndex[pays] = 0;
        var slotIndex = countrySlotIndex[pays]++;
        var parentSlot  = slot.parentElement;
        var siteName = parentSlot.getAttribute('data-site') || 'SITE';
        var loginLogout = parentSlot.getAttribute('data-mode') || 'na';

        var grille  = slot.closest('.grille');
        var section = 'main';
        if (grille) {
            var prev = grille.previousElementSibling;
            while (prev) {
                if (prev.tagName === 'H3') {
                    section = prev.textContent.toLowerCase().indexOf('result') !== -1 ? 'result' : 'main';
                    break;
                }
                prev = prev.previousElementSibling;
            }
        }

        slot.setAttribute('data-slot-index', slotIndex);

        if (savedImages[pays] && savedImages[pays][slotIndex] !== undefined) {
            var span = slot.querySelector('span');
            span.textContent = savedImages[pays][slotIndex];
            span.style.color = '#2e7d32';
        }

        var input = document.createElement('input');
        input.type = 'file';
        input.accept  = 'image/png,image/jpeg';
        input.style.display = 'none';
        slot.appendChild(input);
        slot.style.cursor = 'pointer';
        slot.style.flex   = '1';

        var icon = document.createElement('img');
        icon.src = './img/AddPngPicture.png';
        icon.style.cssText = 'width:14px;height:15px;flex-shrink:0;pointer-events:none;';
        slot.appendChild(icon);

        var wrapper = document.createElement('div');
        wrapper.style.cssText = 'display:flex;align-items:center;gap:6px;width:100%;';
        slot.parentElement.insertBefore(wrapper, slot);
        wrapper.appendChild(slot);

        slot.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return;
            this.querySelector('input[type="file"]').click();
        });

        (function(capturedSlot, capturedPays, capturedIndex, capturedSite, capturedMode, capturedSection) {
            input.addEventListener('change', function() {
                if (this.files.length === 0) {
                    capturedSlot.querySelector('span').textContent = 'Add PNG picture';
                    capturedSlot.querySelector('span').style.color = '#aaa';
                    updateGiftCounter(capturedPays);
                    return;
                }
                checkImageDimension(this.files[0], capturedSlot, capturedPays, capturedIndex, capturedSite, capturedMode, capturedSection);
            });
        })(slot, pays, slotIndex, siteName, loginLogout, section);

        updateGiftCounter(pays);
    });
});