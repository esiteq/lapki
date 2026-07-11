/**
 * Lapki Admin JavaScript
 * Всі операції через REST API
 */

(function($) {
    'use strict';

    const LapkiAdmin = {
        apiBase: '/wp-json/lapki/v1',
        attributesCache: {},
        typesCache: {},
        breedsCache: {},
        organizationsCache: {},
        map: null,
        marker: null,
        dropzone: null,
        currentAnimalId: null,

        /**
         * Ініціалізація
         */
        init: function() {
            const self = this;

            // Сторінки без кешу
            this.initAttributesList();
            this.initOrganizationsList();
            this.initOrganizationForm();

            // Завантажити кеш атрибутів перед ініціалізацією
            this.loadAttributesCache(function() {
                self.initAnimalsList();
                self.initAnimalForm();
                self.initMap();
                self.initMediaGallery();
            });
        },

        /**
         * API Request Helper
         */
        apiRequest: function(endpoint, method = 'GET', data = null) {
            const url = this.apiBase + endpoint;

            const options = {
                url: url,
                method: method,
                dataType: 'json',
                beforeSend: function(xhr) {
                    // Додати nonce якщо потрібно
                    if (typeof lapkiAdmin !== 'undefined' && lapkiAdmin.nonce) {
                        xhr.setRequestHeader('X-WP-Nonce', lapkiAdmin.nonce);
                    }
                }
            };

            if (data && (method === 'POST' || method === 'PUT')) {
                options.contentType = 'application/json';
                options.data = JSON.stringify(data);
            }

            return $.ajax(options);
        },

        /**
         * Завантажити кеш атрибутів для перекладів
         */
        loadAttributesCache: function(callback) {
            const self = this;
            let completed = 0;
            const total = 2;

            function checkComplete() {
                completed++;
                if (completed === total && callback) {
                    callback();
                }
            }

            // Завантажити типи тварин
            this.apiRequest('/types?lang=uk')
                .done(function(response) {
                    response.types.forEach(function(type) {
                        self.typesCache[type.type] = type.display_name;
                    });
                })
                .always(checkComplete);

            // Завантажити всі атрибути
            this.apiRequest('/types/all?lang=uk')
                .done(function(response) {
                    self.attributesCache = response.attributes || {};
                })
                .always(checkComplete);
        },

        /**
         * Отримати display name для атрибуту
         */
        getAttributeDisplay: function(attrName, value) {
            if (!value || value === '-') return '-';

            // Перевірити в кеші атрибутів
            if (this.attributesCache[attrName]) {
                const found = this.attributesCache[attrName].find(function(item) {
                    return item.value === value;
                });
                if (found) return found.display_name;
            }

            return value;
        },

        /**
         * Отримати display name для типу
         */
        getTypeDisplay: function(type) {
            return this.typesCache[type] || type || '-';
        },

        /**
         * Форматувати дату (MySQL datetime) у dd.mm.rrrr
         */
        formatDate: function(value) {
            if (!value) return '-';
            const d = new Date(value.replace(' ', 'T'));
            if (isNaN(d.getTime())) return '-';
            return d.toLocaleDateString('uk-UA', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        /**
         * Завантажити breed display name
         */
        getBreedDisplay: function(type, breedValue, callback) {
            const self = this;
            const cacheKey = type + '_' + breedValue;

            // Перевірити кеш
            if (this.breedsCache[cacheKey]) {
                callback(this.breedsCache[cacheKey]);
                return;
            }

            if (!type || !breedValue) {
                callback(breedValue || '-');
                return;
            }

            // Завантажити породи для типу
            this.apiRequest('/types/' + type + '/breeds?lang=uk')
                .done(function(response) {
                    if (response.breeds) {
                        response.breeds.forEach(function(breed) {
                            // API повертає 'value', а не 'name'
                            self.breedsCache[type + '_' + breed.value] = breed.display_name;
                        });
                    }
                    callback(self.breedsCache[cacheKey] || breedValue);
                })
                .fail(function() {
                    callback(breedValue || '-');
                });
        },

        /**
         * Показати повідомлення
         */
        showNotice: function(message, type = 'success') {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            $('.wrap h1').after(notice);

            // Авто-закриття через 5 секунд
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Ініціалізація списку тварин
         */
        initAnimalsList: function() {
            const self = this;
            const $table = $('#lapki-animals-table');

            if ($table.length === 0) return;

            // Завантажити тварин
            this.loadAnimals();

            // Пошук
            $('#animal-search-input').on('keyup', function(e) {
                if (e.key === 'Enter') {
                    self.loadAnimals();
                }
            });

            // Видалення
            $(document).on('click', '.delete-animal', function(e) {
                e.preventDefault();

                if (!confirm('Ви впевнені, що хочете видалити цю тварину?')) {
                    return;
                }

                const animalId = $(this).data('id');
                self.deleteAnimal(animalId);
            });

            // Показати фото в модальному вікні
            $(document).on('click', '.animal-photo-thumb', function(e) {
                e.preventDefault();
                const fullUrl = $(this).data('full-url');
                const altText = $(this).attr('alt');
                self.showPhotoModalOverlay(fullUrl, altText);
            });

            // Закрити модальне вікно при кліку на фон
            $(document).on('click', '.lapki-photo-modal', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(200, function() {
                        $(this).remove();
                    });
                }
            });

            // Закрити модальне вікно при кліку на кнопку
            $(document).on('click', '.lapki-photo-modal-close', function(e) {
                e.preventDefault();
                $('.lapki-photo-modal').fadeOut(200, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Завантажити тварин через API
         */
        loadAnimals: function(page = 1) {
            const self = this;
            const searchTerm = $('#animal-search-input').val();
            const perPage = 20;
            const offset = (page - 1) * perPage;

            let endpoint = '/animals?limit=' + perPage + '&offset=' + offset;

            if (searchTerm) {
                endpoint += '&search=' + encodeURIComponent(searchTerm);
            }

            // Показати лоадер
            $('#lapki-animals-table tbody').html('<tr><td colspan="9" style="text-align:center;padding:40px;"><div class="spinner is-active" style="float:none;margin:0 auto;"></div><p>Завантаження...</p></td></tr>');

            this.apiRequest(endpoint)
                .done(function(response) {
                    self.renderAnimalsTable(response.data);
                    self.renderPagination(response.pagination);
                })
                .fail(function(xhr) {
                    console.error('Error loading animals:', xhr);
                    $('#lapki-animals-table tbody').html('<tr><td colspan="9" style="text-align:center;padding:40px;color:#a00;">Помилка завантаження даних</td></tr>');
                });
        },

        /**
         * Відобразити таблицю тварин
         */
        renderAnimalsTable: function(animals) {
            const self = this;
            const tbody = $('#lapki-animals-table tbody');
            tbody.empty();

            if (animals.length === 0) {
                tbody.html('<tr><td colspan="9" style="text-align:center;padding:40px;">Тварин не знайдено</td></tr>');
                return;
            }

            animals.forEach(function(animal) {
                const photoUrl = animal.primary_photo && animal.primary_photo.thumbnail_url
                    ? animal.primary_photo.thumbnail_url
                    : '';

                const fullPhotoUrl = animal.primary_photo && animal.primary_photo.full_url
                    ? animal.primary_photo.full_url
                    : photoUrl;

                const photoHtml = photoUrl
                    ? '<img src="' + photoUrl + '" alt="' + animal.name + '" class="animal-photo-thumb" data-full-url="' + fullPhotoUrl + '" style="width:60px;height:60px;object-fit:cover;border-radius:4px;cursor:pointer;">'
                    : '<div style="width:60px;height:60px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#999;font-size:24px;">📷</div>';

                const editUrl = 'admin.php?page=lapki-add-animal&id=' + animal.id;

                // Отримати переклади
                const typeDisplay = self.getTypeDisplay(animal.type);
                const ageDisplay = self.getAttributeDisplay('age', animal.age);
                const genderDisplay = self.getAttributeDisplay('gender', animal.gender);
                const statusDisplay = self.getAttributeDisplay('status', animal.status);

                const row = $('<tr>');
                row.append('<td class="check-column"><input type="checkbox" name="animals[]" value="' + animal.id + '"></td>');
                row.append('<td>' + photoHtml + '</td>');
                row.append('<td><strong><a href="' + editUrl + '">' + animal.name + '</a></strong><div class="row-actions"><span class="edit"><a href="' + editUrl + '">Редагувати</a> | </span><span class="delete"><a href="#" class="delete-animal" data-id="' + animal.id + '" style="color:#a00;">Видалити</a></span></div></td>');
                row.append('<td>' + typeDisplay + '</td>');

                // Порода - завантажується асинхронно
                const $breedCell = $('<td>-</td>');
                row.append($breedCell);
                if (animal.breed_primary && animal.type) {
                    self.getBreedDisplay(animal.type, animal.breed_primary, function(breedDisplay) {
                        $breedCell.text(breedDisplay);
                    });
                }

                row.append('<td>' + ageDisplay + '<br><small>' + genderDisplay + '</small></td>');
                row.append('<td><span class="status-badge status-' + animal.status + '">' + statusDisplay + '</span></td>');
                row.append('<td>' + (animal.organization_name || '-') + '</td>');

                const createdDisplay = self.formatDate(animal.created_at);
                const updatedDisplay = self.formatDate(animal.updated_at);
                row.append('<td class="animal-dates"><strong>Додано:</strong> ' + createdDisplay + '<br><strong>Оновлено:</strong> ' + updatedDisplay + '</td>');

                tbody.append(row);
            });
        },

        /**
         * Відобразити пагінацію
         */
        renderPagination: function(pagination) {
            const $container = $('#lapki-pagination');
            $container.empty();

            if (!pagination || pagination.pages <= 1) return;

            const self = this;
            const currentPage = pagination.current_page;
            const totalPages = pagination.pages;
            const total = pagination.total;

            const $inner = $('<div class="alignleft actions">');
            $inner.append(
                '<span style="line-height:28px;margin-right:10px;">' +
                'Всього: <strong>' + total + '</strong></span>'
            );

            const $pages = $('<span class="pagination-links" style="margin-left:10px;">');

            if (currentPage > 1) {
                $('<a class="prev-page button button-small">&laquo;</a>')
                    .on('click', function() { self.loadAnimals(currentPage - 1); })
                    .appendTo($pages);
            }

            $pages.append(
                '<span style="margin:0 8px;line-height:28px;">' +
                currentPage + ' / ' + totalPages +
                '</span>'
            );

            if (currentPage < totalPages) {
                $('<a class="next-page button button-small">&raquo;</a>')
                    .on('click', function() { self.loadAnimals(currentPage + 1); })
                    .appendTo($pages);
            }

            $inner.append($pages);
            $container.append($inner);
        },

        /**
         * Видалити тварину
         */
        deleteAnimal: function(animalId) {
            const self = this;

            this.apiRequest('/animals/' + animalId, 'DELETE')
                .done(function(response) {
                    self.showNotice('Тварину успішно видалено!', 'success');
                    self.loadAnimals();
                })
                .fail(function(xhr) {
                    console.error('Error deleting animal:', xhr);
                    self.showNotice('Помилка при видаленні тварини', 'error');
                });
        },

        /**
         * Ініціалізація форми тварини
         */
        initAnimalForm: function() {
            const self = this;
            const $form = $('#lapki-animal-form');

            if ($form.length === 0) return;

            // Блокувати форму під час завантаження
            this.setFormLoading(true);

            // Завантажити довідники спочатку
            const animalId = this.getUrlParameter('id');
            this.loadFormOptions(function() {
                // Після завантаження довідників - завантажити дані тварини якщо редагування
                if (animalId) {
                    self.loadAnimal(animalId);
                } else {
                    // Розблокувати форму якщо це створення нової тварини
                    self.setFormLoading(false);
                }
            });

            // При зміні типу - завантажити породи і кольори для цього типу
            $('#type').on('change', function() {
                const type = $(this).val();
                if (type) {
                    self.loadTypeSpecificOptions(type);
                }
            });

            // Обробка submit
            $form.on('submit', function(e) {
                e.preventDefault();
                self.saveAnimal();
            });
        },

        /**
         * Завантажити дані тварини
         */
        loadAnimal: function(animalId) {
            const self = this;

            this.apiRequest('/animals/' + animalId)
                .done(function(animal) {
                    self.fillForm(animal);
                })
                .fail(function(xhr) {
                    console.error('Error loading animal:', xhr);
                    self.showNotice('Помилка завантаження даних тварини', 'error');
                    self.setFormLoading(false);
                });
        },

        /**
         * Заповнити форму даними
         */
        fillForm: function(animal) {
            const self = this;

            // Спочатку завантажити специфічні для типу опції (породи, кольори)
            if (animal.type) {
                this.loadTypeSpecificOptions(animal.type, function() {
                    self.setFormValues(animal);
                    self.setFormLoading(false);
                });
            } else {
                this.setFormValues(animal);
                this.setFormLoading(false);
            }
        },

        /**
         * Встановити значення полів форми
         */
        setFormValues: function(animal) {
            Object.keys(animal).forEach(function(key) {
                const $field = $('#' + key);

                if ($field.length === 0) return;

                if ($field.attr('type') === 'checkbox') {
                    $field.prop('checked', animal[key] == '1' || animal[key] === true);
                } else {
                    $field.val(animal[key]);
                }
            });
        },

        /**
         * Встановити стан завантаження форми
         */
        setFormLoading: function(loading) {
            const $form = $('#lapki-animal-form');
            const $submitBtn = $form.find('.button-primary');

            if (loading) {
                // Блокувати всі поля
                $form.find('input, select, textarea, button').prop('disabled', true);
                $form.css('opacity', '0.6');
                $submitBtn.text('Завантаження...');
            } else {
                // Розблокувати всі поля
                $form.find('input, select, textarea, button').prop('disabled', false);
                // Координати залишити readonly
                $('#latitude, #longitude').prop('readonly', true);
                $form.css('opacity', '1');
                $submitBtn.text('Зберегти');
            }
        },

        /**
         * Завантажити породи і кольори для конкретного типу
         */
        loadTypeSpecificOptions: function(type, callback) {
            const self = this;

            // Очистити поточні опції
            $('#breed_primary').html('<option value="">Виберіть породу</option>');
            $('#breed_secondary').html('<option value="">Виберіть породу</option>');
            $('#color_primary').html('<option value="">Виберіть колір</option>');

            // Завантажити дані для типу
            this.apiRequest('/types/' + type + '?lang=uk')
                .done(function(response) {
                    const attributes = response.attributes || {};

                    // Породи
                    if (attributes.breed) {
                        attributes.breed.forEach(function(item) {
                            const option = '<option value="' + item.value + '">' + item.display_name + '</option>';
                            $('#breed_primary').append(option);
                            $('#breed_secondary').append(option);
                        });
                    }

                    // Кольори
                    if (attributes.color) {
                        attributes.color.forEach(function(item) {
                            $('#color_primary').append('<option value="' + item.value + '">' + item.display_name + '</option>');
                        });
                    }

                    if (callback) callback();
                })
                .fail(function() {
                    if (callback) callback();
                });
        },

        /**
         * Завантажити опції для select полів
         */
        loadFormOptions: function(callback) {
            const self = this;
            let completed = 0;
            const total = 3;

            function checkComplete() {
                completed++;
                if (completed === total && callback) {
                    callback();
                }
            }

            // Типи тварин
            this.apiRequest('/types?lang=uk')
                .done(function(response) {
                    const $select = $('#type');
                    response.types.forEach(function(type) {
                        $select.append('<option value="' + type.type + '">' + type.display_name + '</option>');
                    });
                })
                .always(checkComplete);

            // Організації
            this.apiRequest('/organizations?limit=100')
                .done(function(response) {
                    const $select = $('#organization_id');
                    response.data.forEach(function(org) {
                        $select.append('<option value="' + org.id + '">' + org.name + '</option>');
                    });
                })
                .always(checkComplete);

            // Загальні атрибути (age, gender, size, coat)
            this.apiRequest('/types/all?lang=uk')
                .done(function(response) {
                    const attributes = response.attributes;

                    // Вік
                    if (attributes.age) {
                        const $age = $('#age');
                        attributes.age.forEach(function(item) {
                            $age.append('<option value="' + item.value + '">' + item.display_name + '</option>');
                        });
                    }

                    // Стать
                    if (attributes.gender) {
                        const $gender = $('#gender');
                        attributes.gender.forEach(function(item) {
                            $gender.append('<option value="' + item.value + '">' + item.display_name + '</option>');
                        });
                    }

                    // Розмір
                    if (attributes.size) {
                        const $size = $('#size');
                        attributes.size.forEach(function(item) {
                            $size.append('<option value="' + item.value + '">' + item.display_name + '</option>');
                        });
                    }

                    // Тип шерсті
                    if (attributes.coat) {
                        const $coat = $('#coat');
                        attributes.coat.forEach(function(item) {
                            $coat.append('<option value="' + item.value + '">' + item.display_name + '</option>');
                        });
                    }
                    // Статус захардкоджено в PHP формі
                })
                .always(checkComplete);
        },

        /**
         * Зберегти тварину
         */
        saveAnimal: function() {
            const self = this;
            const animalId = this.getUrlParameter('id');
            const isEdit = !!animalId;

            // Зібрати дані з форми
            const formData = this.getFormData();

            console.log('Form data:', formData);

            // Валідація
            if (!formData.name || !formData.organization_id || !formData.type) {
                this.showNotice('Заповніть обов\'язкові поля', 'error');
                return;
            }

            // Показати лоадер
            $('#lapki-animal-form .button-primary').prop('disabled', true).text('Збереження...');

            const method = isEdit ? 'PUT' : 'POST';
            const endpoint = isEdit ? '/animals/' + animalId : '/animals';

            console.log('Saving animal:', method, endpoint, formData);

            this.apiRequest(endpoint, method, formData)
                .done(function(response) {
                    self.showNotice(isEdit ? 'Тварину успішно оновлено!' : 'Тварину успішно додано!', 'success');

                    if (!isEdit) {
                        // Перенаправити на редагування
                        setTimeout(function() {
                            window.location.href = 'admin.php?page=lapki-add-animal&id=' + response.id;
                        }, 1000);
                    }
                })
                .fail(function(xhr) {
                    console.error('Error saving animal:', xhr);
                    console.error('Response:', xhr.responseText);
                    console.error('Response JSON:', xhr.responseJSON);
                    const errorMsg = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Помилка при збереженні';
                    self.showNotice(errorMsg, 'error');
                })
                .always(function() {
                    $('#lapki-animal-form .button-primary').prop('disabled', false).text('Зберегти');
                });
        },

        /**
         * Отримати дані з форми
         */
        getFormData: function() {
            const data = {};

            $('#lapki-animal-form').find('input, select, textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');

                if (!name) return;

                if ($field.attr('type') === 'checkbox') {
                    data[name] = $field.is(':checked') ? 1 : 0;
                } else {
                    data[name] = $field.val();
                }
            });

            return data;
        },

        /**
         * Отримати параметр з URL
         */
        getUrlParameter: function(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            const results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        },

        /**
         * Показати фото в модальному вікні (список тварин — власний саморобний оверлей,
         * бо на цій сторінці немає статичного #photo-modal з add_edit_animal_page())
         */
        showPhotoModalOverlay: function(photoUrl, altText) {
            const modal = $('<div class="lapki-photo-modal"></div>');
            const content = $('<div class="lapki-photo-modal-content"></div>');
            const closeBtn = $('<button class="lapki-photo-modal-close" aria-label="Закрити">&times;</button>');
            const img = $('<img src="' + photoUrl + '" alt="' + altText + '">');

            content.append(closeBtn);
            content.append(img);
            modal.append(content);

            $('body').append(modal);

            // Показати модальне вікно з анімацією
            setTimeout(function() {
                modal.addClass('show');
            }, 10);
        },

        /**
         * Ініціалізація карти
         */
        initMap: function() {
            const self = this;
            const $mapContainer = $('#location-map');

            if ($mapContainer.length === 0) return;

            // Дефолтні координати - центр України (Київ)
            const defaultLat = 50.4501;
            const defaultLng = 30.5234;

            // Створити карту
            this.map = L.map('location-map').setView([defaultLat, defaultLng], 13);

            // Додати тайли OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(this.map);

            // Клік на карті - встановити маркер
            this.map.on('click', function(e) {
                self.setMapMarker(e.latlng.lat, e.latlng.lng);
            });

            // Кнопка геокодування
            $('#geocode-address').on('click', function() {
                self.geocodeAddress();
            });

            // Якщо є збережені координати - показати маркер
            const lat = parseFloat($('#latitude').val());
            const lng = parseFloat($('#longitude').val());

            if (lat && lng) {
                this.setMapMarker(lat, lng);
                this.map.setView([lat, lng], 15);
            }
        },

        /**
         * Встановити маркер на карті
         */
        setMapMarker: function(lat, lng) {
            // Видалити попередній маркер
            if (this.marker) {
                this.map.removeLayer(this.marker);
            }

            // Додати новий маркер
            this.marker = L.marker([lat, lng]).addTo(this.map);

            // Оновити поля координат
            $('#latitude').val(lat.toFixed(8));
            $('#longitude').val(lng.toFixed(8));
        },

        /**
         * Геокодування адреси через Nominatim
         */
        geocodeAddress: function() {
            const self = this;

            // Зібрати адресу
            const address = $('#address1').val();
            const city = $('#address_city').val();
            const state = $('#address_state').val();
            const postcode = $('#address_postcode').val();

            if (!city && !address) {
                alert('Введіть хоча б місто або адресу');
                return;
            }

            // Сформувати запит
            const addressParts = [address, city, state, postcode, 'Україна'].filter(Boolean);
            const query = addressParts.join(', ');

            // Показати лоадер
            $('#geocode-address').prop('disabled', true).text('🔍 Шукаю...');

            // Nominatim API
            $.ajax({
                url: 'https://nominatim.openstreetmap.org/search',
                data: {
                    q: query,
                    format: 'json',
                    limit: 1,
                    'accept-language': 'uk'
                },
                success: function(data) {
                    if (data && data.length > 0) {
                        const result = data[0];
                        const lat = parseFloat(result.lat);
                        const lng = parseFloat(result.lon);

                        self.setMapMarker(lat, lng);
                        self.map.setView([lat, lng], 15);
                    } else {
                        alert('Адресу не знайдено. Спробуйте уточнити адресу або виберіть місце на карті вручну.');
                    }
                },
                error: function() {
                    alert('Помилка при пошуку адреси');
                },
                complete: function() {
                    $('#geocode-address').prop('disabled', false).text('📍 Знайти на карті за адресою');
                }
            });
        },

        // =======================================================
        // ГАЛЕРЕЯ ЗОБРАЖЕНЬ
        // =======================================================

        /**
         * Ініціалізація галереї та Dropzone
         */
        initMediaGallery: function() {
            const self = this;

            // Перевірити чи є елемент галереї
            if (!$('#animal-media-gallery').length) {
                return;
            }

            // Отримати ID тварини з URL
            const urlParams = new URLSearchParams(window.location.search);
            const animalId = urlParams.get('id');

            if (!animalId) {
                return;
            }

            this.currentAnimalId = animalId;

            // Завантажити існуючі зображення
            this.loadMediaGallery(animalId);

            // Ініціалізувати Dropzone
            this.initDropzone(animalId);

            // Ініціалізувати модальне вікно
            this.initPhotoModal();
        },

        /**
         * Завантажити галерею зображень
         */
        loadMediaGallery: function(animalId) {
            const self = this;

            this.apiRequest('/animals/' + animalId, 'GET').done(function(response) {
                if (response && response.media && response.media.length > 0) {
                    self.renderMediaGallery(response.media);
                } else {
                    $('#animal-media-gallery').html('<p style="color: #666; font-style: italic;">Зображень ще немає</p>');
                }
            }).fail(function() {
                $('#animal-media-gallery').html('<p style="color: #d63384;">Помилка завантаження зображень</p>');
            });
        },

        /**
         * Відобразити галерею зображень
         */
        renderMediaGallery: function(media) {
            const $gallery = $('#animal-media-gallery');
            $gallery.empty();

            media.forEach(function(item) {
                const $item = $('<div>')
                    .addClass('lapki-media-item')
                    .attr('data-media-id', item.id);

                if (item.is_primary) {
                    $item.addClass('is-primary');
                }

                const $img = $('<img>')
                    .attr('src', item.thumbnail_url)
                    .attr('data-full', item.url)
                    .attr('alt', 'Фото тварини');

                const $actions = $('<div>').addClass('lapki-media-item-actions');

                // Кнопка встановити головним
                if (!item.is_primary) {
                    const $setPrimaryBtn = $('<button>')
                        .attr('title', 'Встановити головним фото')
                        .html('⭐')
                        .on('click', function(e) {
                            e.stopPropagation();
                            LapkiAdmin.setPrimaryMedia(item.id);
                        });
                    $actions.append($setPrimaryBtn);
                }

                // Кнопка видалити
                const $deleteBtn = $('<button>')
                    .attr('title', 'Видалити')
                    .html('🗑️')
                    .on('click', function(e) {
                        e.stopPropagation();
                        if (confirm('Видалити це зображення?')) {
                            LapkiAdmin.deleteMedia(item.id);
                        }
                    });
                $actions.append($deleteBtn);

                $item.append($img);
                $item.append($actions);

                // Бейдж для головного фото
                if (item.is_primary) {
                    const $badge = $('<div>')
                        .addClass('lapki-media-primary-badge')
                        .text('ГОЛОВНЕ');
                    $item.append($badge);
                }

                $gallery.append($item);
            });

            // Клік для збільшення
            $gallery.find('.lapki-media-item img').on('click', function() {
                const fullUrl = $(this).attr('data-full');
                LapkiAdmin.showPhotoModal(fullUrl);
            });
        },

        /**
         * Ініціалізувати Dropzone
         */
        initDropzone: function(animalId) {
            const self = this;

            if (typeof Dropzone === 'undefined') {
                console.error('Dropzone не завантажено');
                return;
            }

            // Налаштування Dropzone
            Dropzone.autoDiscover = false;

            this.dropzone = new Dropzone('#dropzone-upload', {
                url: this.apiBase + '/animals/' + animalId + '/media',
                paramName: 'file',
                maxFilesize: 10, // MB
                acceptedFiles: 'image/jpeg,image/png,image/gif,image/webp',
                addRemoveLinks: true,
                dictDefaultMessage: 'Перетягніть файли сюди або клікніть для вибору',
                dictRemoveFile: 'Видалити',
                dictCancelUpload: 'Скасувати',
                dictFileTooBig: 'Файл занадто великий ({{filesize}}MB). Максимум: {{maxFilesize}}MB.',
                dictInvalidFileType: 'Недопустимий тип файлу. Дозволені: JPG, PNG, GIF, WebP.',
                headers: {
                    'X-WP-Nonce': typeof lapkiAdmin !== 'undefined' ? lapkiAdmin.nonce : ''
                },
                init: function() {
                    this.on('success', function(file, response) {
                        console.log('Файл завантажено:', response);
                        // Перезавантажити галерею
                        setTimeout(function() {
                            self.loadMediaGallery(animalId);
                        }, 500);
                    });

                    this.on('error', function(file, errorMessage) {
                        console.error('Помилка завантаження:', errorMessage);
                        alert('Помилка завантаження: ' + (errorMessage.message || errorMessage));
                    });

                    this.on('complete', function(file) {
                        // Видалити файл з Dropzone після завантаження
                        setTimeout(function() {
                            this.removeFile(file);
                        }.bind(this), 2000);
                    });
                }
            });
        },

        /**
         * Видалити зображення
         */
        deleteMedia: function(mediaId) {
            const self = this;

            this.apiRequest('/media/' + mediaId, 'DELETE').done(function() {
                // Перезавантажити галерею
                self.loadMediaGallery(self.currentAnimalId);
            }).fail(function() {
                alert('Помилка видалення зображення');
            });
        },

        /**
         * Встановити головне фото
         */
        setPrimaryMedia: function(mediaId) {
            const self = this;

            this.apiRequest('/media/' + mediaId + '/primary', 'PUT').done(function(response) {
                // Перезавантажити галерею
                self.loadMediaGallery(self.currentAnimalId);
            }).fail(function(error) {
                alert('Помилка встановлення головного фото');
            });
        },

        /**
         * Ініціалізувати модальне вікно для фото
         */
        initPhotoModal: function() {
            const $modal = $('#photo-modal');
            const $modalImg = $('#modal-image');
            const $close = $('.lapki-modal-close');

            // Закрити по кліку на хрестик
            $close.on('click', function() {
                $modal.hide();
            });

            // Закрити по кліку поза зображенням
            $modal.on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            // Закрити по ESC
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $modal.is(':visible')) {
                    $modal.hide();
                }
            });
        },

        /**
         * Показати фото в модальному вікні
         */
        showPhotoModal: function(imageUrl) {
            $('#modal-image').attr('src', imageUrl);
            $('#photo-modal').fadeIn(200);
        },

        // =======================================================
        // АТРИБУТИ
        // =======================================================

        /**
         * Ініціалізація сторінки атрибутів
         */
        initAttributesList: function() {
            const self = this;

            if ($('#lapki-attributes-table').length === 0) return;

            this.loadAttributes();

            // Фільтри
            $('#attr-filter-apply').on('click', function() { self.loadAttributes(); });
            $('#attr-filter-search').on('keyup', function(e) {
                if (e.key === 'Enter') self.loadAttributes();
            });

            // Додати атрибут
            $('#lapki-attr-add-btn').on('click', function() { self.showAttributeModal(null); });

            // Модальні кнопки
            $('#lapki-attr-save').on('click', function() { self.saveAttribute(); });
            $('#lapki-attr-cancel, .lapki-attr-modal-backdrop').on('click', function() {
                $('#lapki-attr-modal').hide();
            });

            // Делегування: редагувати / видалити
            $(document).on('click', '.lapki-attr-edit', function() {
                const id = $(this).data('id');
                self.loadAttributeForEdit(id);
            });
            $(document).on('click', '.lapki-attr-delete', function() {
                const id = $(this).data('id');
                if (confirm('Видалити цей атрибут?')) {
                    self.deleteAttribute(id);
                }
            });
        },

        /**
         * Завантажити атрибути через API
         */
        loadAttributes: function(page) {
            const self = this;
            page = page || 1;
            const perPage = 50;
            const offset  = (page - 1) * perPage;

            let endpoint = '/attributes?limit=' + perPage + '&offset=' + offset;

            const lang       = $('#attr-filter-lang').val();
            const entity     = $('#attr-filter-entity').val();
            const entityType = $('#attr-filter-entity-type').val().trim();
            const attrName   = $('#attr-filter-attr-name').val();
            const search     = $('#attr-filter-search').val().trim();

            if (lang)       endpoint += '&lang=' + encodeURIComponent(lang);
            if (entity)     endpoint += '&entity=' + encodeURIComponent(entity);
            if (entityType) endpoint += '&entity_type=' + encodeURIComponent(entityType);
            if (attrName)   endpoint += '&attr_name=' + encodeURIComponent(attrName);
            if (search)     endpoint += '&search=' + encodeURIComponent(search);

            $('#lapki-attributes-table tbody').html(
                '<tr><td colspan="8" style="text-align:center;padding:30px;"><div class="spinner is-active" style="float:none;margin:0 auto;"></div></td></tr>'
            );

            this.apiRequest(endpoint)
                .done(function(response) {
                    self.renderAttributesTable(response.data);
                    self.renderAttributesPagination(response.pagination, page);
                })
                .fail(function() {
                    $('#lapki-attributes-table tbody').html(
                        '<tr><td colspan="8" style="text-align:center;color:#a00;padding:20px;">Помилка завантаження</td></tr>'
                    );
                });
        },

        /**
         * Відобразити таблицю атрибутів
         */
        renderAttributesTable: function(data) {
            const tbody = $('#lapki-attributes-table tbody');
            tbody.empty();

            if (!data || data.length === 0) {
                tbody.html('<tr><td colspan="8" style="text-align:center;padding:20px;">Атрибутів не знайдено</td></tr>');
                return;
            }

            data.forEach(function(attr) {
                const row = $('<tr>');
                row.append('<td>' + attr.id + '</td>');
                row.append('<td>' + attr.entity + '</td>');
                row.append('<td><code>' + attr.entity_type + '</code></td>');
                row.append('<td><strong>' + attr.attr_name + '</strong></td>');
                row.append('<td>' + attr.attr_value + '</td>');
                row.append('<td>' + attr.attr_display + '</td>');
                row.append('<td>' + attr.lang + '</td>');
                row.append(
                    '<td>' +
                    '<a href="#" class="button button-small lapki-attr-edit" data-id="' + attr.id + '" style="margin-right:4px;">Ред.</a>' +
                    '<a href="#" class="button button-small lapki-attr-delete" data-id="' + attr.id + '" style="color:#a00;">Вид.</a>' +
                    '</td>'
                );
                tbody.append(row);
            });
        },

        /**
         * Пагінація атрибутів
         */
        renderAttributesPagination: function(pagination, currentPage) {
            const self = this;
            const $container = $('#lapki-attr-pagination');
            $container.empty();

            if (!pagination || pagination.pages <= 1) return;

            const $inner = $('<div class="alignleft actions">');
            $inner.append('<span style="line-height:28px;margin-right:10px;">Всього: <strong>' + pagination.total + '</strong></span>');

            const $pages = $('<span class="pagination-links" style="margin-left:10px;">');

            if (currentPage > 1) {
                $('<a class="button button-small">&laquo;</a>')
                    .on('click', function() { self.loadAttributes(currentPage - 1); })
                    .appendTo($pages);
            }

            $pages.append('<span style="margin:0 8px;line-height:28px;">' + currentPage + ' / ' + pagination.pages + '</span>');

            if (currentPage < pagination.pages) {
                $('<a class="button button-small">&raquo;</a>')
                    .on('click', function() { self.loadAttributes(currentPage + 1); })
                    .appendTo($pages);
            }

            $inner.append($pages);
            $container.append($inner);
        },

        /**
         * Відкрити модальне вікно (null = новий, attr = редагування)
         */
        showAttributeModal: function(attr) {
            if (attr) {
                $('#lapki-attr-modal-title').text('Редагувати атрибут #' + attr.id);
                $('#lapki-attr-id').val(attr.id);
                $('#lapki-attr-entity').val(attr.entity);
                $('#lapki-attr-entity-type').val(attr.entity_type);
                $('#lapki-attr-name').val(attr.attr_name);
                $('#lapki-attr-value').val(attr.attr_value);
                $('#lapki-attr-display').val(attr.attr_display);
                $('#lapki-attr-lang').val(attr.lang);
            } else {
                $('#lapki-attr-modal-title').text('Додати атрибут');
                $('#lapki-attr-id').val('');
                $('#lapki-attr-entity').val('animal');
                $('#lapki-attr-entity-type').val('');
                $('#lapki-attr-name').val('breed');
                $('#lapki-attr-value').val('');
                $('#lapki-attr-display').val('');
                $('#lapki-attr-lang').val('uk');
            }

            $('#lapki-attr-modal').show();
            $('#lapki-attr-entity-type').focus();
        },

        /**
         * Завантажити атрибут для редагування
         */
        loadAttributeForEdit: function(id) {
            const self = this;
            this.apiRequest('/attributes?limit=1&offset=0&search=')
                .done(function() {})
                .fail(function() {});

            // Знайти у відображеній таблиці
            const $row = $('.lapki-attr-edit[data-id="' + id + '"]').closest('tr');
            const cells = $row.find('td');
            if (cells.length >= 7) {
                self.showAttributeModal({
                    id:           id,
                    entity:       $(cells[1]).text(),
                    entity_type:  $(cells[2]).text(),
                    attr_name:    $(cells[3]).text(),
                    attr_value:   $(cells[4]).text(),
                    attr_display: $(cells[5]).text(),
                    lang:         $(cells[6]).text(),
                });
            }
        },

        /**
         * Зберегти атрибут (POST або PUT)
         */
        saveAttribute: function() {
            const self = this;
            const id = $('#lapki-attr-id').val();

            const data = {
                entity:       $('#lapki-attr-entity').val(),
                entity_type:  $('#lapki-attr-entity-type').val().trim(),
                attr_name:    $('#lapki-attr-name').val(),
                attr_value:   $('#lapki-attr-value').val().trim(),
                attr_display: $('#lapki-attr-display').val().trim(),
                lang:         $('#lapki-attr-lang').val(),
            };

            for (const key in data) {
                if (!data[key]) {
                    this.showNotice("Поле '" + key + "' обов'язкове", 'error');
                    return;
                }
            }

            const method   = id ? 'PUT' : 'POST';
            const endpoint = id ? '/attributes/' + id : '/attributes';

            $('#lapki-attr-save').prop('disabled', true).text('Збереження...');

            this.apiRequest(endpoint, method, data)
                .done(function() {
                    $('#lapki-attr-modal').hide();
                    self.showNotice(id ? 'Атрибут оновлено!' : 'Атрибут додано!', 'success');
                    self.loadAttributes();
                })
                .fail(function(xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Помилка збереження';
                    self.showNotice(msg, 'error');
                })
                .always(function() {
                    $('#lapki-attr-save').prop('disabled', false).text('Зберегти');
                });
        },

        /**
         * Видалити атрибут
         */
        deleteAttribute: function(id) {
            const self = this;
            this.apiRequest('/attributes/' + id, 'DELETE')
                .done(function() {
                    self.showNotice('Атрибут видалено!', 'success');
                    self.loadAttributes();
                })
                .fail(function() {
                    self.showNotice('Помилка видалення', 'error');
                });
        },

        // =======================================================
        // ОРГАНІЗАЦІЇ
        // =======================================================

        /**
         * Ініціалізація сторінки організацій
         */
        initOrganizationsList: function() {
            const self = this;

            if ($('#lapki-organizations-table').length === 0) return;

            this.loadOrganizations();

            $('#org-filter-apply').on('click', function() { self.loadOrganizations(); });
            $('#org-filter-search').on('keyup', function(e) {
                if (e.key === 'Enter') self.loadOrganizations();
            });

            $(document).on('click', '.lapki-org-delete', function() {
                const id = $(this).data('id');
                if (confirm('Видалити цю організацію?')) {
                    self.deleteOrganization(id);
                }
            });
        },

        /**
         * Завантажити організації через API
         */
        loadOrganizations: function() {
            const self = this;

            let endpoint = '/organizations?limit=100';
            const name = $('#org-filter-search').val().trim();
            const type = $('#org-filter-type').val();

            if (name) endpoint += '&name=' + encodeURIComponent(name);
            if (type) endpoint += '&type=' + encodeURIComponent(type);

            $('#lapki-organizations-table tbody').html(
                '<tr><td colspan="8" style="text-align:center;padding:30px;"><div class="spinner is-active" style="float:none;margin:0 auto;"></div></td></tr>'
            );

            this.apiRequest(endpoint)
                .done(function(response) {
                    self.renderOrganizationsTable(response.data);
                })
                .fail(function() {
                    $('#lapki-organizations-table tbody').html(
                        '<tr><td colspan="8" style="text-align:center;color:#a00;padding:20px;">Помилка завантаження</td></tr>'
                    );
                });
        },

        /**
         * Відобразити таблицю організацій
         */
        renderOrganizationsTable: function(data) {
            const tbody = $('#lapki-organizations-table tbody');
            tbody.empty();
            this.organizationsCache = {};

            if (!data || data.length === 0) {
                tbody.html('<tr><td colspan="8" style="text-align:center;padding:20px;">Організацій не знайдено</td></tr>');
                return;
            }

            const self = this;
            const editBase = 'admin.php?page=lapki-add-organization&id=';
            data.forEach(function(org) {
                self.organizationsCache[org.id] = org;

                const row = $('<tr>');
                row.append('<td>' + org.id + '</td>');
                row.append('<td><strong><a href="' + editBase + org.id + '">' + (org.name || '') + '</a></strong></td>');
                row.append('<td>' + (org.type || '') + '</td>');
                row.append('<td>' + (org.email || '') + '</td>');
                row.append('<td>' + (org.city || '') + '</td>');
                row.append('<td>' + (org.animals_count || 0) + '</td>');
                row.append('<td>' + (Number(org.is_verified) ? '✅' : '—') + '</td>');
                row.append(
                    '<td>' +
                    '<a href="' + editBase + org.id + '" class="button button-small" style="margin-right:4px;">Ред.</a>' +
                    '<a href="#" class="button button-small lapki-org-delete" data-id="' + org.id + '" style="color:#a00;">Вид.</a>' +
                    '</td>'
                );
                tbody.append(row);
            });
        },

        /**
         * Ініціалізація сторінки додавання/редагування організації
         */
        initOrganizationForm: function() {
            const self = this;
            const $form = $('#lapki-organization-form');

            if ($form.length === 0) return;

            const orgId = this.getUrlParameter('id');
            if (orgId) {
                this.setOrgFormLoading(true);
                this.loadOrganization(orgId);
            }

            $form.on('submit', function(e) {
                e.preventDefault();
                self.saveOrganizationForm();
            });
        },

        /**
         * Завантажити дані організації для редагування
         */
        loadOrganization: function(orgId) {
            const self = this;

            this.apiRequest('/organizations/' + orgId)
                .done(function(org) {
                    self.setOrgFormValues(org);
                    self.setOrgFormLoading(false);
                })
                .fail(function(xhr) {
                    console.error('Error loading organization:', xhr);
                    self.showNotice('Помилка завантаження даних організації', 'error');
                    self.setOrgFormLoading(false);
                });
        },

        /**
         * Заповнити форму організації даними
         */
        setOrgFormValues: function(org) {
            Object.keys(org).forEach(function(key) {
                const $field = $('#lapki-organization-form #' + key);

                if ($field.length === 0) return;

                if ($field.attr('type') === 'checkbox') {
                    $field.prop('checked', org[key] == '1' || org[key] === true);
                } else {
                    $field.val(org[key]);
                }
            });
        },

        /**
         * Заблокувати/розблокувати форму організації під час завантаження
         */
        setOrgFormLoading: function(loading) {
            const $form = $('#lapki-organization-form');
            const $submitBtn = $form.find('.button-primary');

            if (loading) {
                $form.find('input, select, textarea, button').prop('disabled', true);
                $form.css('opacity', '0.6');
            } else {
                $form.find('input, select, textarea, button').prop('disabled', false);
                $form.css('opacity', '1');
                $submitBtn.text('Зберегти');
            }
        },

        /**
         * Зберегти організацію (POST або PUT) зі сторінки форми
         */
        saveOrganizationForm: function() {
            const self = this;
            const $form = $('#lapki-organization-form');
            const id = $('#id').val();
            const name = $('#name').val().trim();

            if (!name) {
                this.showNotice("Поле 'Назва' обов'язкове", 'error');
                return;
            }

            const data = {
                name: name,
                type: $('#type').val(),
                email: $('#email').val().trim(),
                phone: $('#phone').val().trim(),
                website: $('#website').val().trim(),
                city: $('#city').val().trim(),
                state: $('#state').val().trim(),
                mission_statement: $('#mission_statement').val().trim(),
                adoption_policy: $('#adoption_policy').val().trim(),
                is_verified: $('#is_verified').is(':checked') ? 1 : 0
            };

            const method   = id ? 'PUT' : 'POST';
            const endpoint = id ? '/organizations/' + id : '/organizations';

            $form.find('.button-primary').prop('disabled', true).text('Збереження...');

            this.apiRequest(endpoint, method, data)
                .done(function() {
                    self.showNotice(id ? 'Організацію оновлено!' : 'Організацію додано!', 'success');
                    window.location.href = 'admin.php?page=lapki-organizations';
                })
                .fail(function(xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Помилка збереження';
                    self.showNotice(msg, 'error');
                    $form.find('.button-primary').prop('disabled', false).text('Зберегти');
                });
        },

        /**
         * Видалити організацію
         */
        deleteOrganization: function(id) {
            const self = this;
            this.apiRequest('/organizations/' + id, 'DELETE')
                .done(function() {
                    self.showNotice('Організацію видалено!', 'success');
                    self.loadOrganizations();
                })
                .fail(function(xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Помилка видалення';
                    self.showNotice(msg, 'error');
                });
        }
    };

    // Ініціалізація при завантаженні DOM
    $(document).ready(function() {
        LapkiAdmin.init();
    });

})(jQuery);
