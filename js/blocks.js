function block_switch(id) {
    const klappText = document.getElementById('sb' + id);
    const klappBild = document.getElementById('picb' + id);

    if (!klappText || !klappBild) return; // защита от отсутствующих элементов

    // Меняем картинку и определяем направление
    if (klappText.style.display === 'block') {
        klappBild.src = 'pic/plus.gif';
    } else {
        klappBild.src = 'pic/minus.gif';
    }

    // Работа с кукой hb (оставляем serialize / unserialize для совместимости)
    let hb = unserialize(getCookie('hb')) || [];

    if (!Array.isArray(hb)) hb = []; // на случай, если unserialize вернул не массив

    if (hb.includes(id)) {
        hb.splice(hb.indexOf(id), 1);
    } else {
        hb.push(id);
    }

    setCookie('hb', serialize(hb));

    // Анимация скольжения (jQuery остается для slideToggle)
    jQuery('#sb' + id).slideToggle('medium');
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