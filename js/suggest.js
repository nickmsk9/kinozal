// Вспомогательная функция, заменяющая закомментированную $
function $(e) {
    if (typeof e === 'string') e = document.getElementById(e);
    return e;
}

function collect(arr, fn) {
    const result = [];
    for (let i = 0; i < arr.length; i++) {
        const val = fn(arr[i]);
        if (val != null) result.push(val);
    }
    return result;
}

const ajax = {
    // Создаёт XMLHttpRequest (без устаревших ActiveX)
    x() {
        return new XMLHttpRequest();
    },

    // Сериализует форму в строку запроса
    serialize(form) {
        const g = tag => Array.from(form.getElementsByTagName(tag));
        const nv = el => el.name ? encodeURIComponent(el.name) + '=' + encodeURIComponent(el.value) : '';

        const inputs = collect(g('input'), i => {
            if ((i.type !== 'radio' && i.type !== 'checkbox') || i.checked) return nv(i);
            return null;
        });
        const selects = collect(g('select'), nv);
        const textareas = collect(g('textarea'), nv);

        return inputs.concat(selects, textareas).join('&');
    },

    // Универсальная отправка запроса
    send(url, callback, method, data) {
        const xhr = this.x();
        xhr.open(method, url, true);
        xhr.onreadystatechange = () => {
            if (xhr.readyState === 4) callback(xhr.responseText);
        };
        if (method === 'POST') {
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        }
        xhr.send(data);
    },

    // GET-запрос
    get(url, callback) { this.send(url, callback, 'GET'); },

    // Синхронный GET (оставлен для обратной совместимости, но НЕ рекомендуется)
    gets(url) {
        const xhr = this.x();
        xhr.open('GET', url, false);
        xhr.send(null);
        return xhr.responseText;
    },

    // POST-запрос
    post(url, callback, data) { this.send(url, callback, 'POST', data); },

    // Загружает содержимое в элемент
    update(url, elm) {
        const e = $(elm);
        this.get(url, r => { e.innerHTML = r; });
    },

    // Отправляет форму и загружает ответ в элемент
    submit(url, elm, frm) {
        const e = $(elm);
        this.post(url, r => { e.innerHTML = r; }, this.serialize(frm));
    }
};

// Глобальные переменные для подсказок
let pos = 0;
let count = 0;

// Закрытие подсказок при клике (не перезаписываем другие обработчики)
document.addEventListener('click', closechoices);

// Запрет отправки формы по Enter, если список подсказок открыт
function noenter(event) {
    const suggcont = document.getElementById('suggcontainer');
    if (suggcont && suggcont.style.display === 'block') {
        const key = event.key || event.keyCode; // поддержка современных и старых браузеров
        if (key === 'Enter' || key === 13) {
            const selected = document.getElementById(pos);
            if (selected) choiceclick(selected);
            event.preventDefault();
            return false;
        }
    }
    return true;
}

// Основная функция обработки ввода в поле поиска
function suggest(event, query) {
    const key = event.key || event.keyCode;

    if (key === 'ArrowUp' || key === 38) {
        goPrev();
    } else if (key === 'ArrowDown' || key === 40) {
        goNext();
    } else if (key !== 'Enter' && key !== 13) {
        if (query.length > 3) {
            const timestamp = new Date().getTime();
            ajax.get('suggest.php?q=' + encodeURIComponent(query) + '&bla=' + timestamp, update);
        } else {
            update('');
        }
    }
}

// Обработка ответа от сервера
function update(result) {
    const suggdiv = document.getElementById('suggestions');
    const suggcont = document.getElementById('suggcontainer');
    if (!suggdiv || !suggcont) return;

    const arr = result.split('\r\n');
    count = arr.length > 10 ? 10 : arr.length;

    if (arr[0].length > 0) {
        suggcont.style.display = 'block';
        suggdiv.innerHTML = '';
        suggdiv.style.height = count * 20 + 'px';

        for (let i = 1; i <= count; i++) {
            const novo = document.createElement('div');
            novo.id = i;
            Object.assign(novo.style, {
                height: '14px',
                padding: '3px',
                cursor: 'pointer'
            });
            novo.onmouseover = () => select(novo, true);
            novo.onmouseout = () => unselect(novo, true);
            novo.onclick = () => choiceclick(novo);
            novo.textContent = arr[i - 1]; // безопасная вставка текста
            suggdiv.appendChild(novo);
        }
    } else {
        suggcont.style.display = 'none';
        count = 0;
    }
}

function select(obj, mouse) {
    if (!obj) return;
    obj.style.backgroundColor = '#3399ff';
    obj.style.color = '#ffffff';
    if (mouse) {
        pos = obj.id;
        unselectAllOther(pos);
    }
}

function unselect(obj, mouse) {
    if (!obj) return;
    obj.style.backgroundColor = '#ffffff';
    obj.style.color = '#000000';
    if (mouse) {
        pos = 0;
    }
}

function goNext() {
    if (pos <= count && count > 0) {
        const curr = document.getElementById(pos);
        if (curr) unselect(curr);
        pos++;
        const next = document.getElementById(pos);
        if (next) select(next);
        else pos = 0;
    }
}

function goPrev() {
    if (count > 0) {
        const curr = document.getElementById(pos);
        if (curr) {
            unselect(curr);
            pos--;
            const prev = document.getElementById(pos);
            if (prev) select(prev);
            else pos = 0;
        } else {
            pos = count;
            const last = document.getElementById(count);
            if (last) select(last);
        }
    }
}

function choiceclick(obj) {
    const searchInput = document.getElementById('searchinput');
    if (!searchInput || !obj) return;
    searchInput.value = obj.textContent || obj.innerHTML;
    count = 0;
    pos = 0;
    const suggcont = document.getElementById('suggcontainer');
    if (suggcont) suggcont.style.display = 'none';
    searchInput.focus();
}

function closechoices() {
    const suggcont = document.getElementById('suggcontainer');
    if (suggcont && suggcont.style.display === 'block') {
        count = 0;
        pos = 0;
        suggcont.style.display = 'none';
    }
}

function unselectAllOther(id) {
    for (let i = 1; i <= count; i++) {
        if (i != id) {
            const el = document.getElementById(i);
            if (el) {
                el.style.backgroundColor = '#ffffff';
                el.style.color = '#000000';
            }
        }
    }
}