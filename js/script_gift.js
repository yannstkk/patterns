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

function editorCmd(editorId, cmd) {
    document.getElementById(editorId).focus();
    document.execCommand(cmd, false, null);
}

function editorFontSize(editorId, val) {
    document.getElementById(editorId).focus();
    document.execCommand('fontSize', false, val);
}

function setPhase(phase, btn) {
    document.querySelectorAll('.phase-tab').forEach(function(b) {
        b.classList.remove('phase-tab-actif');
    });
    btn.classList.add('phase-tab-actif');
    document.getElementById('input-active-phase').value = phase;
}

function chargeGiftOnglet(id, bouton) {
    document.querySelectorAll('.onglet').forEach(function(o) { o.classList.remove('actif'); });
    document.querySelectorAll('.contenu').forEach(function(c) { c.classList.remove('actif'); });
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

function checkImageDimension(file, slot, pays, slotIndex, siteName, loginLogout, section) {
    var dataSize = slot.getAttribute('data-size');
    if (!dataSize || dataSize.trim() === '') {
        uploadImage(file, slot, pays, slotIndex, siteName, loginLogout, section);
        return;
    }
    var parts = dataSize.toLowerCase().split('x');
    var width = parseInt(parts[0]);
    var height = parseInt(parts[1]);
    var img = new Image();
    var url = URL.createObjectURL(file);
    img.src = url;
    img.onload = function() {
        URL.revokeObjectURL(url);
        if (img.naturalWidth === width && img.naturalHeight === height) {
            uploadImage(file, slot, pays, slotIndex, siteName, loginLogout, section);
        } else {
            var span = slot.querySelector('span');
            span.textContent = 'Incorrect size : ' + img.naturalWidth + 'x' + img.naturalHeight + ' (expected : ' + width + 'x' + height + ')';
            span.style.color = '#cc0000';
            slot.querySelector('input[type="file"]').value = '';
            updateGiftCounter(pays);
        }
    };
}

function uploadImage(file, slot, pays, slotIndex, siteName, loginLogout, section) {
    var span = slot.querySelector('span');
    if (!eventId) {
        span.textContent = 'Save the form first to upload images';
        span.style.color = '#cc0000';
        return;
    }
    var originalName = file.name.replace(/\.[^.]+$/, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    span.textContent = 'Uploading...';
    span.style.color = '#888';
    var formData = new FormData();
    formData.append('image', file);
    formData.append('pays', pays);
    formData.append('slot_index', slotIndex);
    formData.append('site_name', siteName);
    formData.append('login_logout', loginLogout);
    formData.append('section', section || 'main');
    formData.append('original_name', originalName);
    fetch('./validation/upload_image.php', {method:'POST', body:formData})
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

function uploadGiftAsset(file, assetKey) {
    if (!eventId) { alert('Save the form first to upload images'); return; }
    var originalName = file.name.replace(/\.[^.]+$/, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    var assetIndexMap = {logo:0, arriere_plan:1, image1:2, image2:3};
    var slotIndex = assetIndexMap[assetKey];
    var formData = new FormData();
    formData.append('image', file);
    formData.append('pays', 'gift_assets');
    formData.append('slot_index', slotIndex);
    formData.append('site_name', assetKey);
    formData.append('login_logout', 'na');
    formData.append('section', 'main');
    formData.append('original_name', originalName);
    fetch('./validation/upload_image.php', {method:'POST', body:formData})
    .then(function(res) { return res.json(); })
    .then(function(data) {
        var el = document.querySelector('.gift-asset-input[data-asset="' + assetKey + '"] .gift-asset-name');
        if (el) {
            el.textContent = data.success ? data.filename : data.error;
            el.style.color = data.success ? '#2e7d32' : '#cc0000';
        }
    })
    .catch(function() {});
}

function updateGiftCounter(pays) {
    var contenu = document.getElementById(pays);
    if (!contenu) return;
    var missing = 0;
    var countrySlotIdx = {};
    contenu.querySelectorAll('.slot-input').forEach(function(slot) {
        var p = slot.closest('.contenu').id;
        if (!countrySlotIdx[p]) countrySlotIdx[p] = 0;
        var idx = countrySlotIdx[p]++;
        var hasSaved = savedImages[p] && savedImages[p][idx] !== undefined;
        var fileInput = slot.querySelector('input[type="file"]');
        var hasNew = fileInput && fileInput.files.length > 0;
        if (!hasNew && !hasSaved) missing++;
    });
    var counter = document.getElementById('compteur-' + pays);
    if (counter) counter.textContent = missing;
}

function openPreview() {
    var intro = document.getElementById('introduction-editor').innerHTML;
    var about = document.getElementById('about-editor').innerHTML;
    var w = window.open('', '_blank');
    w.document.write('<html><body style="font-family:Arial;padding:20px;max-width:700px;margin:auto;">' + intro + '<hr>' + about + '</body></html>');
    w.document.close();
}

document.addEventListener('DOMContentLoaded', function() {
    var currentPays = document.querySelector('input[name="pays"]:checked');
    if (currentPays) updateGiftOnglet(currentPays.value);

    var allSlots = document.querySelectorAll('.slot-input');
    var countrySlotIndex = {};

    allSlots.forEach(function(slot) {
        var pays = slot.closest('.contenu').id;
        if (!countrySlotIndex[pays]) countrySlotIndex[pays] = 0;
        var slotIndex = countrySlotIndex[pays]++;
        var parentSlot = slot.parentElement;
        var siteName = parentSlot.getAttribute('data-site') || 'SITE';
        var loginLogout = parentSlot.getAttribute('data-mode') || 'na';
        var grille = slot.closest('.grille');
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
        input.accept = 'image/png,image/jpeg';
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

    document.querySelectorAll('.gift-asset-input').forEach(function(el) {
        var assetKey = el.getAttribute('data-asset');
        var fileInput = el.querySelector('input[type="file"]');
        el.style.cursor = 'pointer';
        el.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return;
            fileInput.click();
        });
        fileInput.addEventListener('change', function() {
            if (this.files.length === 0) return;
            uploadGiftAsset(this.files[0], assetKey);
        });
    });

    document.getElementById('introduction-editor').addEventListener('input', function() {
        document.getElementById('introduction-hidden').value = this.innerHTML;
    });
    document.getElementById('about-editor').addEventListener('input', function() {
        document.getElementById('about-hidden').value = this.innerHTML;
    });

    document.getElementById('gift-form').addEventListener('submit', function() {
        document.getElementById('introduction-hidden').value = document.getElementById('introduction-editor').innerHTML;
        document.getElementById('about-hidden').value = document.getElementById('about-editor').innerHTML;
    });
});