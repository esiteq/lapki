/* =========================================================
   Lapki — вбудований віджет пошуку тварин притулку
   Використання: <script src="https://lapki.help/integration/lapki.js?organization_id=1"></script>
   Необов'язкові параметри рядка запиту:
     limit  — скільки тварин показати (за замовчуванням 12)
     status — статус тварин (за замовчуванням "adoptable")
   Самодостатній vanilla JS — без зовнішніх залежностей (як і /js/animals.js),
   вставляє форму пошуку + сітку результатів, без шапки/футера/меню сайту lapki.help.
   ========================================================= */
(function () {
    'use strict';

    var scriptEl = document.currentScript;
    if (!scriptEl) {
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            if (scripts[i].src && scripts[i].src.indexOf('/integration/lapki.js') !== -1) {
                scriptEl = scripts[i];
                break;
            }
        }
    }
    if (!scriptEl) {
        return;
    }

    var scriptUrl = new URL(scriptEl.src, window.location.href);
    var params = scriptUrl.searchParams;
    var orgId = params.get('organization_id');
    var limit = params.get('limit') || 12;
    var status = params.get('status') || 'adoptable';
    var apiBase = scriptUrl.origin + '/wp-json/lapki/v1';
    var siteBase = scriptUrl.origin;

    var PAW_SVG = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:-2px"><path d="M4.5 12.5c1.1 0 2-1.12 2-2.5s-.9-2.5-2-2.5-2 1.12-2 2.5.9 2.5 2 2.5zm5-4c1.1 0 2-1.12 2-2.5S10.6 3.5 9.5 3.5s-2 1.12-2 2.5 1 2.5 2 2.5zm5 0c1.1 0 2-1.12 2-2.5S15.6 3.5 14.5 3.5s-2 1.12-2 2.5 1 2.5 2 2.5zm4.5 4c1.1 0 2-1.12 2-2.5s-.9-2.5-2-2.5-2 1.12-2 2.5.9 2.5 2 2.5zM12 12.25c-2.5 0-6.5 2.15-6.5 5.1 0 1.2 1 2.15 2.2 2.15.9 0 1.5-.35 2.5-.35s1.75.35 2.5.35c1 0 1.5-.35 2.5-.35s1.6.35 2.5.35c1.2 0 2.2-.95 2.2-2.15 0-2.95-4-5.1-6.5-5.1z"/></svg>';
    var PIN_SVG = '<svg width="11" height="11" viewBox="0 0 24 24" fill="#EA4335" style="vertical-align:-1px"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';
    var SEARCH_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>';

    var TYPE_LABELS = { dog: 'Собаки', cat: 'Коти', bird: 'Птахи', rabbit: 'Кролики', other: 'Інші' };
    var AGE_LABELS = { baby: 'Малюк', young: 'Молодий', adult: 'Дорослий', senior: 'Похилого віку' };

    var uid = 'lapki-iw-' + Math.random().toString(36).slice(2, 9);

    var root = document.createElement('div');
    root.id = uid;
    root.className = 'lapki-iw';
    scriptEl.parentNode.insertBefore(root, scriptEl.nextSibling);

    injectStyles();
    root.innerHTML = '<div class="lapki-iw__loading">Завантаження…</div>';

    if (!orgId) {
        root.innerHTML = '<div class="lapki-iw__empty">Не вказано organization_id у посиланні на скрипт.</div>';
        return;
    }

    fetchJson(apiBase + '/organizations/' + encodeURIComponent(orgId)).then(function (organization) {
        if (!organization || organization.code) {
            root.innerHTML = '<div class="lapki-iw__empty">Організацію не знайдено.</div>';
            return;
        }
        renderShell(organization);
        search({});
    }).catch(function () {
        root.innerHTML = '<div class="lapki-iw__empty">Не вдалося завантажити дані. Спробуйте, будь ласка, пізніше.</div>';
    });

    function fetchJson(url) {
        return fetch(url).then(function (r) { return r.json(); });
    }

    function renderShell(organization) {
        root.innerHTML =
            '<div class="lapki-iw__grid" id="' + uid + '-grid"></div>' +
            '<div class="lapki-iw__footer">Powered by <a href="' + escAttr(siteBase + '/') + '" target="_blank" rel="noopener">lapki.help</a></div>';

        /* ── Плашка з назвою притулку + пошукова форма (тип/місто/вік) —
           вимкнені на прохання користувача: лишити тільки картки тварин,
           без жодної рамки/плашки навколо (притулок в одному місті, форма
           поки не потрібна). Закоментовано (не видалено), щоб легко
           повернути назад пізніше.

        var orgLink = '<a class="lapki-iw__title" href="' + escAttr(siteBase + '/organizations/' + organization.id + '/') + '" target="_blank" rel="noopener">' + PAW_SVG + ' Тварини притулку «' + escHtml(organization.name) + '»</a>';

        var typeOptions = '<option value="">Всі тварини</option>';
        for (var t in TYPE_LABELS) {
            if (TYPE_LABELS.hasOwnProperty(t)) {
                typeOptions += '<option value="' + t + '">' + TYPE_LABELS[t] + '</option>';
            }
        }

        var ageOptions = '<option value="">Будь-який вік</option>';
        for (var a in AGE_LABELS) {
            if (AGE_LABELS.hasOwnProperty(a)) {
                ageOptions += '<option value="' + a + '">' + AGE_LABELS[a] + '</option>';
            }
        }

        root.innerHTML =
            '<div class="lapki-iw__hero">' +
                '<div class="lapki-iw__header">' + orgLink + '</div>' +
                '<form class="lapki-iw__form">' +
                    '<div class="lapki-iw__field">' +
                        '<label>Вид тварини</label>' +
                        '<select name="type">' + typeOptions + '</select>' +
                    '</div>' +
                    '<div class="lapki-iw__field">' +
                        '<label>Місто</label>' +
                        '<input type="text" name="location" placeholder="Будь-яке місто" autocomplete="off">' +
                    '</div>' +
                    '<div class="lapki-iw__field">' +
                        '<label>Вік</label>' +
                        '<select name="age">' + ageOptions + '</select>' +
                    '</div>' +
                    '<button type="submit" class="lapki-iw__btn">' + SEARCH_SVG + ' Шукати</button>' +
                '</form>' +
            '</div>' +
            '<div class="lapki-iw__grid" id="' + uid + '-grid"></div>' +
            '<div class="lapki-iw__footer">Powered by <a href="' + escAttr(siteBase + '/') + '" target="_blank" rel="noopener">lapki.help</a></div>';

        var form = root.querySelector('.lapki-iw__form');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(form);
            search({ type: fd.get('type'), location: fd.get('location'), age: fd.get('age') });
        });

        */
    }

    function search(filters) {
        var grid = document.getElementById(uid + '-grid');
        if (!grid) return;
        grid.innerHTML = '<div class="lapki-iw__loading">Завантаження…</div>';

        var qs = new URLSearchParams({ organization_id: orgId, status: status, limit: limit });
        if (filters.type) qs.set('type', filters.type);
        if (filters.location) qs.set('location', filters.location);
        if (filters.age) qs.set('age', filters.age);

        fetchJson(apiBase + '/animals?' + qs.toString()).then(function (data) {
            var animals = Array.isArray(data) ? data : (data.data || []);
            if (!animals.length) {
                grid.innerHTML = '<div class="lapki-iw__empty">Нічого не знайдено.</div>';
                return;
            }
            grid.innerHTML = animals.map(buildCard).join('');
        }).catch(function () {
            grid.innerHTML = '<div class="lapki-iw__empty">Помилка завантаження.</div>';
        });
    }

    function buildCard(animal) {
        var name = animal.name || 'Без імені';
        var city = animal.address_city || '';
        var type = animal.type || '';
        var age = animal.age || '';
        var gender = animal.gender || '';

        var typeLabels = { dog: '🐕 Собака', cat: '🐈 ' + (gender === 'female' ? 'Кішка' : 'Кіт'), bird: '🐦 Птах', rabbit: '🐇 Кролик', other: 'Інше' };
        var imgSrc = (animal.primary_photo && (animal.primary_photo.thumbnail_url || animal.primary_photo.url)) || '';
        var url = siteBase + '/animals/' + animal.id + '/';

        var imgHtml = imgSrc
            ? '<img src="' + escAttr(imgSrc) + '" alt="' + escAttr(name) + '" loading="lazy">'
            : '<div class="lapki-iw-card__noimg">' + PAW_SVG + '</div>';

        var tags = [];
        if (typeLabels[type]) tags.push('<span class="lapki-iw-tag lapki-iw-tag--type">' + typeLabels[type] + '</span>');
        if (AGE_LABELS[age]) tags.push('<span class="lapki-iw-tag lapki-iw-tag--age">' + AGE_LABELS[age] + '</span>');
        if (city) tags.push('<span class="lapki-iw-tag lapki-iw-tag--city">' + PIN_SVG + ' ' + escHtml(city) + '</span>');

        return (
            '<a class="lapki-iw-card" href="' + escAttr(url) + '" target="_blank" rel="noopener">' +
                '<div class="lapki-iw-card__img">' + imgHtml + '</div>' +
                '<div class="lapki-iw-card__body">' +
                    '<div class="lapki-iw-card__name">' + escHtml(name) + '</div>' +
                    (tags.length ? '<div class="lapki-iw-card__tags">' + tags.join('') + '</div>' : '') +
                '</div>' +
            '</a>'
        );
    }

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function escAttr(str) {
        return escHtml(str);
    }

    function injectStyles() {
        if (document.getElementById('lapki-iw-styles')) {
            return;
        }
        var style = document.createElement('style');
        style.id = 'lapki-iw-styles';
        // Кольори — ті самі, що на головній сторінці lapki.help (--lapki-green
        // #006400/#004b00 для hero-градієнта, --lapki-btn-green #ea580c для кнопки
        // пошуку). Поки що захардкоджено — конфігуратор кольорів/полів планується пізніше.
        style.textContent =
            '.lapki-iw{box-sizing:border-box;width:100%;font-family:"Nunito",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#3a3b45;}' +
            '.lapki-iw *{box-sizing:border-box;}' +
            '.lapki-iw__loading,.lapki-iw__empty{padding:2rem 1rem;text-align:center;color:#858796;font-size:.95rem;}' +
            '.lapki-iw__hero{background:linear-gradient(135deg,#006400 0%,#004b00 100%);border-radius:.35rem;padding:1.5rem;margin-bottom:1.5rem;}' +
            '.lapki-iw__header{text-align:center;margin-bottom:0;}' +
            '.lapki-iw__title{font-size:1.3rem;font-weight:800;color:#fff;text-decoration:none;text-shadow:0 1px 6px rgba(0,0,0,.15);}' +
            '.lapki-iw__title:hover{text-decoration:underline;}' +
            '.lapki-iw__form{background:#fff;border-radius:.35rem;padding:.6rem;box-shadow:0 12px 30px rgba(0,0,0,.18);display:flex;flex-wrap:wrap;align-items:stretch;gap:.5rem;max-width:900px;margin:0 auto;}' +
            '.lapki-iw__field{flex:1 1 160px;display:flex;flex-direction:column;justify-content:center;gap:2px;min-width:0;padding:.4rem .9rem;border-radius:.35rem;transition:background .15s;}' +
            '.lapki-iw__field:hover,.lapki-iw__field:focus-within{background:#e2efe2;}' +
            '.lapki-iw__field label{font-size:.68rem;font-weight:700;color:#858796;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;}' +
            '.lapki-iw__field select,.lapki-iw__field input{border:none;outline:none;font-size:.95rem;font-weight:600;color:#3a3b45;background:transparent;width:100%;font-family:inherit;padding:0;appearance:none;-webkit-appearance:none;}' +
            '.lapki-iw__field input::placeholder{color:#adb5bd;font-weight:500;}' +
            '.lapki-iw__btn{flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:0 1.5rem;border:none;border-radius:.35rem;background:#ea580c;color:#fff;font-weight:700;font-size:.95rem;cursor:pointer;transition:background .15s;}' +
            '.lapki-iw__btn:hover{background:#c2410c;}' +
            '.lapki-iw__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:1rem;width:100%;}' +
            '.lapki-iw-card{display:block;background:#fff;border:1px solid #e3e6f0;border-radius:.35rem;overflow:hidden;text-decoration:none;color:inherit;box-shadow:0 .125rem .25rem 0 rgba(58,59,69,.2);transition:box-shadow .15s,transform .15s;}' +
            '.lapki-iw-card:hover{box-shadow:0 .15rem 1.75rem 0 rgba(58,59,69,.15);transform:translateY(-2px);}' +
            '.lapki-iw-card__img{width:100%;aspect-ratio:4/3;background:#f8f9fc;overflow:hidden;display:flex;align-items:center;justify-content:center;color:#d1d3e2;}' +
            '.lapki-iw-card__img img{width:100%;height:100%;object-fit:cover;display:block;}' +
            '.lapki-iw-card__noimg{font-size:2.5rem;width:2.5rem;height:2.5rem;}' +
            '.lapki-iw-card__body{padding:.6rem .75rem .75rem;}' +
            '.lapki-iw-card__name{font-weight:700;font-size:.95rem;margin-bottom:.3rem;color:#3a3b45;text-align:center;}' +
            '.lapki-iw-card__tags{display:flex;flex-wrap:wrap;justify-content:center;gap:.3rem;}' +
            '.lapki-iw-tag{display:inline-flex;align-items:center;gap:.2rem;font-size:.68rem;padding:.15rem .5rem;border-radius:999px;background:#eaecf4;color:#6f42c1;line-height:1;}' +
            '.lapki-iw-tag svg{display:inline-flex;align-items:center;}' +
            '.lapki-iw-tag--type{background:#fdf3de;color:#dda20a;}' +
            '.lapki-iw-tag--age{background:#e1f3f6;color:#2a96a5;}' +
            '.lapki-iw-tag--city{background:#e2efe2;color:#004b00;}' +
            '.lapki-iw__footer{margin-top:1rem;font-size:.75rem;color:#858796;text-align:right;}' +
            '.lapki-iw__footer a{color:#ea580c;text-decoration:none;font-weight:700;}' +
            '@media (max-width:576px){.lapki-iw__form{flex-direction:column;}.lapki-iw__btn{width:100%;}}';
        document.head.appendChild(style);
    }
})();
