/**
 * Переключает видимость блока с id = 's' + id
 * @param {string|number} id - Идентификатор блока
 * @param {boolean} [updateImage=false] - Обновлять ли картинку и title у элемента 'pic' + id
 */
function toggleBlock(id, updateImage = false) {
    const textEl = document.getElementById('s' + id);
    const imgEl = document.getElementById('pic' + id);

    // Если текстового блока нет на странице – выходим
    if (!textEl) return;

    // Определяем текущее состояние (скрыт или показан)
    const isHidden = textEl.style.display === 'none';

    // Переключаем видимость
    textEl.style.display = isHidden ? 'block' : 'none';

    // При необходимости обновляем иконку и всплывающую подсказку
    if (updateImage && imgEl) {
        imgEl.src = isHidden ? 'pic/minus.gif' : 'pic/plus.gif';
        imgEl.title = isHidden ? 'Скрыть' : 'Показать';
    }
}

// Оригинальные точки входа (имена и сигнатуры без изменений)
function show_hide_no_img(id) {
    toggleBlock(id, false);
}

function show_hide(id) {
    toggleBlock(id, true);
}