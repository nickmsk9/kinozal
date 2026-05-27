// Глобальные настройки (сохраняем глобально, если они нужны другим скриптам)
window.do_linked_resize = 1;
window.resize_percent = 50;

add_onload_event(fix_linked_image_sizes);

function fix_linked_image_sizes() {
    if (window.do_linked_resize !== 1) return true;

    const images = document.querySelectorAll('img.linked-image');
    const padding = 2;
    const resizedIcon = '<img src="pic/img-resized.png" style="vertical-align:middle" border="0" alt="" />';
    const maxWidth = screen.width * (window.resize_percent / 100);
    let count = 0;

    images.forEach((img) => {
        count++;
        if (img.width <= maxWidth) return;

        const originalWidth = img.width;
        const originalHeight = img.height;
        img.width = maxWidth;

        const percent = Math.ceil((img.width / originalWidth) * 100);
        img.id = '--ipb-img-resizer-' + count;
        img._resized = 1;
        img._width = originalWidth;

        const div = document.createElement('div');
        div.innerHTML = `${resizedIcon}&nbsp;Уменьшено: ${percent}% от оригинала [ ${originalWidth} x ${originalHeight} ] - Нажмите для просмотра полного изображения`;
        div.style.cssText = [
            `width: ${img.width - padding * 2}px`,
            'text-align: left',
            'font-weight: normal',
            `padding: ${padding}px`,
        ].join('; ');
        div.className = 'resized-linked-image';
        div._is_div = 1;
        div._resize_id = count;
        div.onclick = fix_linked_images_onclick;
        div.onmouseover = fix_linked_images_mouseover;
        div.title = 'Нажмите для просмотра полного изображения';
        div._src = img.src;

        img.parentNode.insertBefore(div, img);
    });
}

function fix_linked_images_onclick(e) {
    PopUp(this._src, 'popup', screen.width, screen.height, 1, 1, 1);
    return false;
}

function fix_linked_images_mouseover(e) {
    try { this.style.cursor = 'pointer'; } catch (ignore) {}
}
// Оставляем дублирующую функцию для обратной совместимости
function fix_attach_images_mouseover(e) {
    try { this.style.cursor = 'pointer'; } catch (ignore) {}
}

function PopUp(url, name, width, height, center, resize, scroll, posleft, postop) {
    let X = posleft || 0;
    let Y = postop || 0;
    if (center) {
        X = (screen.width - width) / 2;
        Y = (screen.height - height) / 2;
    }
    const showx = X > 0 ? `,left=${X}` : '';
    const showy = Y > 0 ? `,top=${Y}` : '';

    // Исходное поведение: вместо открытия окна – переход на url
    // (оригинальный window.open закомментирован)
    window.location = url;
}

function add_onload_event(func) {
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        // DOM уже загружен, выполняем сразу
        setTimeout(func, 0);
    } else {
        window.addEventListener('load', func, { once: true });
    }
}