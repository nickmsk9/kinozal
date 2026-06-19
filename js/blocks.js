function block_switch(id) {
    id = String(id);
    const klappText = document.getElementById('sb' + id);
    const klappBild = document.getElementById('picb' + id);

    if (!klappText || !klappBild) return; // защита от отсутствующих элементов

    // Меняем картинку и определяем направление
    if (klappText.style.display === 'block') {
        klappBild.src = 'pic/plus.gif';
    } else {
        klappBild.src = 'pic/minus.gif';
    }

    // Cookie hb читает PHP через unserialize(), поэтому оставляем PHP-serialized формат.
    let hb = parseHiddenBlockIds(getCookie('hb'));

    if (hb.indexOf(id) !== -1) {
        hb.splice(hb.indexOf(id), 1);
    } else {
        hb.push(id);
    }

    setCookie('hb', serializeHiddenBlockIds(hb));

    // Анимация скольжения (jQuery остается для slideToggle)
    jQuery('#sb' + id).slideToggle('medium');
}

function parseHiddenBlockIds(value) {
    if (!value || value.indexOf('a:') !== 0) return [];

    const ids = [];
    const re = /i:\d+;(?:s:\d+:"([^"]*)";|i:(-?\d+);)/g;
    let match;

    while ((match = re.exec(value)) !== null) {
        ids.push(String(match[1] !== undefined ? match[1] : match[2]));
    }

    return ids;
}

function serializeHiddenBlockIds(ids) {
    const parts = [];

    ids.forEach((id, index) => {
        id = String(id);
        parts.push('i:' + index + ';s:' + id.length + ':"' + id + '";');
    });

    return 'a:' + ids.length + ':{' + parts.join('') + '}';
}

/* ----- Вспомогательные функции для работы с cookie (без jQuery) ----- */
function getCookie(name) {
    const value = '; ' + document.cookie;
    const parts = value.split('; ' + name + '=');
    if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
    return null;
}

function setCookie(name, value, days = 30, path = '/') {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=' + path;
}
