(function ($, window, document) {
    "use strict";

    // ---------- Вспомогательные утилиты ----------
    function parsePipeData(data, start) {
        let i = start || 0;
        const result = { date: '', records: [] };

        function getString() {
            let doc = '';
            while (i < data.length) {
                const ch = data.charAt(i++);
                if (ch === '^') return doc;
                doc += ch;
            }
            return doc;
        }

        result.date = getString();
        let recordStr;
        while ((recordStr = getString()) !== '') {
            const fields = [];
            let pos = 0;
            let field = '';
            while (pos < recordStr.length) {
                const ch = recordStr.charAt(pos++);
                if (ch === '|') {
                    fields.push(field);
                    field = '';
                } else {
                    field += ch;
                }
            }
            fields.push(field); // последнее поле
            result.records.push(fields);
        }
        return result;
    }

    // Рендер пользователей (список ссылок) – возвращает HTML-строку
    function renderUsersList(records) {
        let html = '';
        records.forEach((rec, idx) => {
            if (idx > 0) html += ', ';
            const [id, name, cls, gen, grup, cub, park] = rec;
            html += `<a href="/userdetails.php?id=${id}" class="u${cls}">${name}</a>`;
            if (+gen > 0) html += '<i class="i1 s_dv"></i>';
            if (+grup > 0) html += `<i class="i1 s${grup}"></i>`;
            if (+cub > 0) html += `<i class="i1 cb${cub}"></i>`;
            if (+park > 0) html += '<i class="i1 s_park"></i>';
        });
        return html;
    }

    // Рендер таблицы пиров (сидов/личей)
    function renderPeersTable(records, isLeecher) {
        let html = '';
        records.forEach((rec) => {
            const [id, name, cls, gen, grup, cub, strana,
                   zalil, procz, skachal, procsk, retio, retiocolor,
                   podtime, connect, procent] = rec;

            html += '<tr><td nowrap>';
            html += `<img class="i2 c${strana}" src="/pic/emty.gif"> `;
            html += `<a href="/userdetails.php?id=${id}" class="u${cls}">${name}</a>`;
            if (+gen > 0) html += '<i class="i1 s_dv"></i>';
            if (+grup > 0) html += `<i class="i1 s${grup}"></i>`;
            if (+cub > 0) html += `<i class="i1 cb${cub}"></i>`;
            html += '</td>';
            html += `<td align="right" class="small">${zalil}</td>`;
            html += `<td align="right" class="small">${procz}/с</td>`;
            html += `<td align="right" class="small">${skachal}</td>`;
            html += `<td align="right" class="small">${procsk}/с</td>`;
            html += `<td align="right" class="small"><b><font color="${retiocolor}">${retio}</font></b></td>`;
            if (isLeecher) html += `<td align="right" class="small">${procent}%</td>`;
            html += `<td align="right" class="small">${podtime}</td></tr>`;
        });
        return html;
    }

    // ---------- Модуль для управления контейнером и загрузкой вкладок ----------
    const TabsManager = {
        p_arr2: [],
        tb2_old: -1,
        p_arr: [],
        tb_old: -1,

        settab2(tb) {
            const $tabs = $('#tabs2');
            if (this.tb2_old === -1) {
                this.p_arr2[100] = $tabs.html();
                this.tb2_old = 100;
            }
            $('#tbch2' + this.tb2_old).attr('class', '');
            $('#tbch2' + tb).attr('class', 'mn');
            this.tb2_old = tb;
            if (this.p_arr2[tb]) {
                $tabs.html(this.p_arr2[tb]);
                return false;
            }
            return true;
        },

        showtab2(id, tb) {
            if (this.settab2(tb)) {
                const $tabs = $('#tabs2');
                $tabs.html('<div class="pad5x5">Загрузка...</div>');
                $.get('/ajax/details_get.php?id=' + id + '&sr=' + tb, (s) => {
                    this.p_arr2[tb] = s;
                    $tabs.html(s);
                });
            }
        },

        settab(tb) {
            const $tabs = $('#tabs');
            if (this.tb_old === -1) {
                this.p_arr[100] = $tabs.html();
                this.tb_old = 100;
            }
            $('#tbch' + this.tb_old).attr('class', '');
            $('#tbch' + tb).attr('class', 'mn');
            this.tb_old = tb;
            if (this.p_arr[tb]) {
                $tabs.html(this.p_arr[tb]);
                return false;
            }
            return true;
        },

        showtab(id, tb) {
            if (this.settab(tb)) {
                const $tabs = $('#tabs');
                $tabs.html('Загрузка...');
                $.get('/get_srv_details.php?id=' + id + '&pagesd=' + tb, (s) => {
                    this.p_arr[tb] = s;
                    $tabs.html(s);
                });
            }
        }
    };

    // ---------- Работа с контейнером деталей ----------
    const DetailsContainer = {
        mode: '',
        pg_array: [],

        show() {
            $('#container').show();
        },
        hide() {
            $('#container').hide();
            this.mode = 'none';
        },
        setHeader(title) {
            $('#containerheader').html(`<span class="bulet"></span> ${title}`);
        },

        // Основная функция загрузки
        get_torm(id, act, zag, sub) {
            if (this.mode === act) return;
            this.mode = act;
            this.setHeader(zag);
            this.show();

            const self = this;
            if (self.pg_array[act]) {
                $('#containerdata').html(self.pg_array[act]);
                return;
            }

            $('#containerdata').html('Загрузка...');
            $.get('/get_srv_details.php?id=' + id + '&action=' + act + (sub || ''))
                .done(function (s) {
                    let html = s;
                    const pos = s.indexOf('<!>');
                    if (pos !== -1) {
                        const parts = s.split('<!>');
                        // parts[0] содержит данные до разделителя, parts[1] – возможно, дополнительные данные
                        if (act === 10 || act === 11) {
                            const dataStr = '^' + parts[0];
                            const parsed = parsePipeData(dataStr, 0);
                            const isLeecher = (act === 11);
                            const peersHtml = renderPeersTable(parsed.records, isLeecher);
                            if (peersHtml) {
                                const colSpan = isLeecher ? '7' : '6';
                                const extraCol = isLeecher ? '<td align="right">%</td>' : '';
                                html = `<div class="bx2_0"><table class="tables3 w100p"><tbody>
                                    <tr class="mn">
                                        <td width="140" nowrap>Ник</td>
                                        <td align="right">Залил</td>
                                        <td align="right">Соотн.</td>
                                        <td align="right">Скачал</td>
                                        <td align="right">Соотн.</td>
                                        <td align="right">Рейтинг</td>
                                        ${extraCol}
                                        <td align="right">Подкл.</td>
                                    </tr>${peersHtml}</tbody></table></div>`;
                            } else {
                                html = '';
                            }
                        } else if (act === 3) {
                            // parts[1] – возможно "OK", а parts[0] данные
                            const dataStr = '^' + parts[0];
                            const parsed = parsePipeData(dataStr, 0);
                            const usersHtml = renderUsersList(parsed.records);
                            if (parts[1] === 'OK') {
                                html = usersHtml;
                            }
                        }
                    }
                    self.pg_array[act] = html;
                    $('#containerdata').html(html);
                })
                .fail(function () {
                    $('#containerdata').html('Ошибка загрузки данных.');
                });
        }
    };

    // ---------- Функции комментариев ----------
    const Comments = {
        voted: 0,

        vote(id, rat) {
            if (this.voted > 1) return;
            this.voted++;
            $('#starbar').addClass('user').width((rat * 20) + 'px');
            $('#ratio_get').html('Загрузка...');
            $.get('/get_srv_details.php?id=' + id + '&action=4&rat=' + rat, function (s) {
                $('#ratio_get').html(s);
            });
        },

        c_replay(id, name) {
            const $cm = $('#cm' + id);
            if (!$cm.length) return false;
            const $testcomm = $('#testcomm').html($cm.html());
            $testcomm.find('a').remove();
            $testcomm.find('fieldset').remove();
            $testcomm.find('blockquote').remove();
            const plainText = $testcomm.html()
                .replace(/<br[^>]*>/gi, "\n")
                .replace(/(\r\n|\r|\n)+/g, "\n");
            const trimmed = ($testcomm.text() || plainText).trim();
            $('#text').val('\n\n[quote=' + name + ']' + trimmed + '[/quote]');
            showcomm(1);
            return false;
        },

        c_del(id, subid) {
            if (confirm('Вы уверены, что хотите удалить комментарий №' + id)) {
                $.get('/comment.php', { id: subid, action: 'del', cid: id })
                    .done(function (data) {
                        $('#cm' + id).html(data);
                    });
            }
            return false;
        },

        c_red(id, subid) {
            window.location.href = '/comment.php?id=' + subid + '&action=modifycomment&cid=' + id;
            return false;
        },

        cmt_submit() {
            const form = document.forms['cmt'];
            if (!form || form.text.value.length < 10) {
                alert('Комментарий не может быть меньше 10 символов!');
                return false;
            }
            return true;
        }
    };

    // Управление видимостью формы комментария
    window.showcomm = function (id) {
        const $cmt = $('#cmtcomm');
        if (id === 1) $cmt.show();
        else if (id === 2) $cmt.hide();
        else $cmt.toggle();
        $('#cmfoc').focus();
    };

    // Устаревшая функция больше не нужна, оставим заглушку для совместимости
    window.mess_out = function (mes) {
        return confirm(mes);
    };

    // ---------- Прочие глобальные функции ----------
    window.cat = function (id) {
        document.location.href = '/browse.php?c=' + id;
        return false;
    };

    window.getRetio = function () {
        const $retio = $('#user_retio');
        if (!$retio.html()) {
            $retio.html('<div class="pad5x5 b">Загрузка...</div>');
            $.get('/get_srv_user_retio.php')
                .done(function (html) {
                    $retio.html(html);
                })
                .fail(function () {
                    $retio.html('Ошибка загрузки данных.');
                });
        } else {
            $retio.html('');
        }
        return false;
    };

    window.settab2 = function (tb) { return TabsManager.settab2(tb); };
    window.showtab2 = function (id, tb) { TabsManager.showtab2(id, tb); };
    window.settab = function (tb) { return TabsManager.settab(tb); };
    window.showtab = function (id, tb) { TabsManager.showtab(id, tb); };

    window.showcontainer = function () { DetailsContainer.show(); };
    window.hidecontainer = function () { DetailsContainer.hide(); };
    window.setheader = function (t) { DetailsContainer.setHeader(t); };
    window.get_torm = function (id, act, zag, sub) { DetailsContainer.get_torm(id, act, zag, sub); };

    window.vote = function (id, rat) { Comments.vote(id, rat); };
    window.c_replay = function (id, name) { return Comments.c_replay(id, name); };
    window.c_del = function (id, subid) { return Comments.c_del(id, subid); };
    window.c_red = function (id, subid) { return Comments.c_red(id, subid); };
    window.cmt_submit = function () { return Comments.cmt_submit(); };

    // Удаляем старые глобальные переменные, если они ещё остались (data, i и т.п.)
    // В старом коде они использовались для парсинга, теперь не нужны
    if (window.data) delete window.data;
    if (window.i) delete window.i;

})(jQuery, window, document);
