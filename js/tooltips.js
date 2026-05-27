window.onerror = null;

const tooltip = {
    // Настройки — сохраняем оригинальные имена
    attr_name: "tooltip",
    blank_text: "(ссылка откроется в новом окне)",
    newline_entity: "~",
    max_width: 0,
    delay: 0,

    // Элементы и состояния
    t: null,          // сам контейнер подсказки
    timeout: null,
    show: false,

    // Инициализация (запускается один раз по готовности DOM)
    init() {
        this.t = document.createElement("div");
        this.t.id = "tooltip";
        document.body.appendChild(this.t);

        // Переносим title/alt в кастомный атрибут для всех подходящих элементов
        const candidates = document.querySelectorAll('[title], [alt], a[target="_blank"]');
        candidates.forEach(el => {
            let tipText = "";

            const title = el.getAttribute("title");
            const alt = el.getAttribute("alt");
            const isBlank = el.getAttribute("target") === "_blank" && this.blank_text;

            // Если есть title и он строка (защита от IE, где getAttribute мог вернуть объект)
            if (title && typeof title === "string") tipText = title;
            else if (alt && el.complete) tipText = alt; // alt только для загруженных картинок

            if (isBlank) {
                tipText = tipText ? tipText + " " + this.blank_text : this.blank_text;
            }

            if (tipText) {
                el.setAttribute(this.attr_name, tipText);
                // Удаляем исходный атрибут, чтобы не появлялся нативный тултип
                if (title) el.removeAttribute("title");
                if (alt && el.complete) el.removeAttribute("alt");

                // Навешиваем обработчики
                el.addEventListener("mouseenter", this.showTooltip.bind(this));
                el.addEventListener("mouseleave", this.hideTooltip.bind(this));
            }
        });

        // Глобальные слушатели для перемещения и скрытия
        document.addEventListener("mousemove", this.moveTooltip.bind(this));
        window.addEventListener("scroll", this.hideTooltip.bind(this));
        this.hideTooltip(); // спрятать изначально
    },

    // Показать подсказку
    showTooltip(e) {
        const d = e.currentTarget;
        const s = d.getAttribute(this.attr_name);
        if (!s) return;

        // Очищаем и наполняем контейнер
        if (this.newline_entity) {
            // Экранируем HTML, заменяем разделитель на <br>
            const escaped = s
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(new RegExp(this.newline_entity.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), "<br>");
            this.t.innerHTML = escaped;
        } else {
            this.t.textContent = s;
        }

        // Показываем с задержкой
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            this.t.style.visibility = "visible";
        }, this.delay);

        this.show = true;
    },

    // Скрыть подсказку
    hideTooltip() {
        this.t.style.visibility = "hidden";
        if (!this.newline_entity) this.t.textContent = "";
        clearTimeout(this.timeout);
        this.show = false;
        this.setPosition(-99, -99);
    },

    // Обновить позицию по курсору
    moveTooltip(e) {
        if (!this.show) return;

        const canvas = document.documentElement; // всегда HTML в стандартном режиме
        const x = e.clientX + window.scrollX;
        const y = e.clientY + window.scrollY;
        this.setPosition(x, y);
    },

    // Рассчитать и установить координаты
    setPosition(x, y) {
        const canvas = document.documentElement;
        const w_width = canvas.clientWidth + window.scrollX;
        const w_height = window.innerHeight + window.scrollY;

        // Ширина подсказки
        if (this.max_width && this.t.offsetWidth > this.max_width) {
            this.t.style.width = this.max_width + "px";
        } else {
            this.t.style.width = "auto";
        }

        const t_width = this.t.offsetWidth;
        const t_height = this.t.offsetHeight;

        let left = x + 6;
        let top = y + 16;

        // Не вылезать за края окна
        if (x + t_width > w_width - 8) left = w_width - t_width;
        if (y + t_height > w_height - 8) top = w_height - t_height;

        this.t.style.left = left + "px";
        this.t.style.top = top + "px";
    }
};

// Запуск после загрузки DOM
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => tooltip.init());
} else {
    tooltip.init(); // DOM уже готов
}