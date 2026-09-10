<?php

/**
 * Lapki REST API Endpoints
 * 
 * @package Lapki
 * @author Oleksii Bugrov
 */

class Lapki_REST_API {
    
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_filter('rest_pre_serve_request', [__CLASS__, 'allow_embed_cors'], 20, 3);
    }

    /**
     * Дозволяє крос-доменний доступ (CORS) лише для публічних GET-ендпоінтів
     * тварин/організацій — потрібно для віджета вбудовування (js/animals.js),
     * який виконує fetch() з довільного стороннього сайту. Дефолтна поведінка
     * WP (`rest_send_cors_headers`) дозволяє лише той самий origin, тож для
     * зовнішніх сайтів запит інакше блокується браузером.
     */
    public static function allow_embed_cors($served, $result, $request) {
        if ($request->get_method() !== 'GET') {
            return $served;
        }

        $route = $request->get_route();
        if (strpos($route, '/lapki/v1/animals') === 0 || strpos($route, '/lapki/v1/organizations') === 0) {
            header('Access-Control-Allow-Origin: *');
        }

        return $served;
    }
    
    public static function register_routes() {
        $namespace = 'lapki/v1';
        
        // ANIMALS ROUTES
        register_rest_route($namespace, '/animals', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_animals'],
                'permission_callback' => '__return_true',
                'args' => self::get_animals_search_args()
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'create_animal'],
                'permission_callback' => [__CLASS__, 'check_create_animal_permission']
            ]
        ]);

        register_rest_route($namespace, '/animals/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_animal'],
                'permission_callback' => '__return_true',
                'args' => [
                    'id' => [
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [__CLASS__, 'update_animal'],
                'permission_callback' => [__CLASS__, 'check_animal_owner_permission']
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [__CLASS__, 'delete_animal'],
                'permission_callback' => [__CLASS__, 'check_animal_owner_permission']
            ]
        ]);
        
        // ANIMAL TYPES ROUTES
        register_rest_route($namespace, '/types', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_animal_types'],
            'permission_callback' => '__return_true',
            'args' => [
                'lang' => [
                    'type' => 'string',
                    'default' => 'uk',
                    'enum' => ['uk', 'en']
                ]
            ]
        ]);

        // Явний маршрут /types/all — глобальні атрибути (age, gender, size, coat, status)
        // Реєструємо ДО /types/{type}, щоб 'all' не потрапляло під загальний маршрут
        register_rest_route($namespace, '/types/all', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_all_type_attributes'],
            'permission_callback' => '__return_true',
            'args' => [
                'lang' => [
                    'type' => 'string',
                    'default' => 'uk',
                    'enum' => ['uk', 'en']
                ]
            ]
        ]);

        register_rest_route($namespace, '/types/(?P<type>[a-zA-Z0-9_-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_type_details'],
            'permission_callback' => '__return_true',
            'args' => [
                'type' => [
                    'required' => true,
                    'type' => 'string'
                ],
                'lang' => [
                    'type' => 'string',
                    'default' => 'uk',
                    'enum' => ['uk', 'en']
                ]
            ]
        ]);
        
        register_rest_route($namespace, '/types/(?P<type>[a-zA-Z0-9_-]+)/breeds', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_type_breeds'],
            'permission_callback' => '__return_true',
            'args' => [
                'type' => [
                    'required' => true,
                    'type' => 'string'
                ],
                'lang' => [
                    'type' => 'string',
                    'default' => 'uk',
                    'enum' => ['uk', 'en']
                ]
            ]
        ]);
        
        // LOCATIONS ROUTE — підказки міст для автодоповнення пошуку
        register_rest_route($namespace, '/locations', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_locations'],
            'permission_callback' => '__return_true',
            'args' => [
                'query' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'limit' => [
                    'type' => 'integer',
                    'default' => 10
                ]
            ]
        ]);

        // GEO ROUTE — довідник населених пунктів (КАТОТТГ) для полів "Місто" з
        // обов'язковим вибором конкретного населеного пункту (не довільний текст)
        register_rest_route($namespace, '/geo', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_geo'],
            'permission_callback' => '__return_true',
            'args' => [
                'query' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'limit' => [
                    'type' => 'integer',
                    'default' => 20
                ]
            ]
        ]);

        // AI ROUTE — "Покращити за допомогою ШІ" (Опис тварини), делегує
        // конкретному провайдеру через Lapki_AI_Manager (inc/class-lapki-ai.php)
        register_rest_route($namespace, '/ai/improve', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'ai_improve_text'],
            'permission_callback' => [__CLASS__, 'check_is_logged_in'],
            'args' => [
                'text' => [
                    'type' => 'string',
                    'required' => true
                ],
                'context' => [
                    'type' => 'object',
                    'required' => false
                ]
            ]
        ]);

        // ORGANIZATIONS ROUTES
        register_rest_route($namespace, '/organizations', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_organizations'],
                'permission_callback' => '__return_true',
                'args' => self::get_organizations_search_args()
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'create_organization'],
                // Самостійна реєстрація організації — будь-який залогінений користувач
                // без наявного членства (перевірка всередині обробника)
                'permission_callback' => [__CLASS__, 'check_is_logged_in']
            ]
        ]);

        register_rest_route($namespace, '/organizations/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_organization'],
                'permission_callback' => '__return_true',
                'args' => [
                    'id' => [
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [__CLASS__, 'update_organization'],
                'permission_callback' => [__CLASS__, 'check_organization_owner_permission']
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [__CLASS__, 'delete_organization'],
                'permission_callback' => [__CLASS__, 'check_organization_owner_permission']
            ]
        ]);

        // ЧЛЕНСТВО В ОРГАНІЗАЦІЇ (реєстрація користувача і привʼязка до
        // притулку/ГО — окремі кроки; join — надіслати заявку на приєднання
        // до вже існуючої організації (очікує підтвердження власником),
        // leave — вийти з конкретної організації/скасувати свою заявку)
        register_rest_route($namespace, '/organizations/(?P<id>\d+)/join', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'join_organization'],
            'permission_callback' => [__CLASS__, 'check_is_logged_in'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);

        register_rest_route($namespace, '/organizations/(?P<id>\d+)/leave', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'leave_organization'],
            'permission_callback' => [__CLASS__, 'check_is_logged_in'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);

        // Підтвердити/відхилити заявку на приєднання — лише власник організації
        register_rest_route($namespace, '/organizations/(?P<id>\d+)/join-requests/(?P<user_id>\d+)/approve', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'approve_join_request'],
            'permission_callback' => [__CLASS__, 'check_organization_owner_permission'],
            'args' => [
                'id' => ['required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'],
                'user_id' => ['required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'],
            ]
        ]);

        register_rest_route($namespace, '/organizations/(?P<id>\d+)/join-requests/(?P<user_id>\d+)/reject', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'reject_join_request'],
            'permission_callback' => [__CLASS__, 'check_organization_owner_permission'],
            'args' => [
                'id' => ['required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'],
                'user_id' => ['required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'],
            ]
        ]);

        // Власник не може просто вийти (організація лишиться без власника) —
        // спершу має передати право власності комусь із учасників
        register_rest_route($namespace, '/organizations/(?P<id>\d+)/transfer', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'transfer_organization_owner'],
            'permission_callback' => [__CLASS__, 'check_organization_owner_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);

        // STATISTICS ROUTE
        register_rest_route($namespace, '/stats', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_stats'],
            'permission_callback' => '__return_true'
        ]);

        // MEDIA ROUTES
        register_rest_route($namespace, '/animals/(?P<animal_id>\d+)/media', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'upload_animal_media'],
            'permission_callback' => [__CLASS__, 'check_animal_media_permission']
        ]);

        // Відео тварини — зовнішнє посилання (YouTube/Vimeo/...), без завантаження файлу
        register_rest_route($namespace, '/animals/(?P<animal_id>\d+)/video', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'add_animal_video'],
            'permission_callback' => [__CLASS__, 'check_animal_media_permission']
        ]);

        // Фото притулку/організації — окремо від фото тварин (uploads/lapki/org/)
        register_rest_route($namespace, '/organizations/(?P<id>\d+)/media', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'upload_organization_media'],
            'permission_callback' => [__CLASS__, 'check_organization_media_permission']
        ]);

        // Відео притулку — зовнішнє посилання (YouTube/Vimeo/пряме), без завантаження файлу
        register_rest_route($namespace, '/organizations/(?P<id>\d+)/video', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'add_organization_video'],
            'permission_callback' => [__CLASS__, 'check_organization_media_permission']
        ]);

        register_rest_route($namespace, '/media/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [__CLASS__, 'delete_media'],
            'permission_callback' => [__CLASS__, 'check_media_owner_permission']
        ]);

        register_rest_route($namespace, '/media/(?P<id>\d+)/primary', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [__CLASS__, 'set_primary_media'],
            'permission_callback' => [__CLASS__, 'check_media_owner_permission']
        ]);

        // ATTRIBUTES ROUTES (глобальний довідник — тільки адмін)
        register_rest_route($namespace, '/attributes', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_attributes'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'create_attribute'],
                'permission_callback' => [__CLASS__, 'check_manage_attributes_permission'],
            ]
        ]);

        register_rest_route($namespace, '/attributes/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [__CLASS__, 'update_attribute'],
                'permission_callback' => [__CLASS__, 'check_manage_attributes_permission'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [__CLASS__, 'delete_attribute'],
                'permission_callback' => [__CLASS__, 'check_manage_attributes_permission'],
            ]
        ]);

        // EMAIL TEMPLATES ROUTES (Lapki → Email-шаблони, тільки адмін; без
        // create/delete — редагується лише subject/body засіяних шаблонів)
        register_rest_route($namespace, '/email-templates', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_email_templates'],
            'permission_callback' => [__CLASS__, 'check_manage_email_templates_permission'],
        ]);

        register_rest_route($namespace, '/email-templates/(?P<id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [__CLASS__, 'update_email_template'],
            'permission_callback' => [__CLASS__, 'check_manage_email_templates_permission'],
        ]);

        // APPLICATIONS ROUTES (заявки на усиновлення)
        register_rest_route($namespace, '/applications', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_applications'],
                'permission_callback' => [__CLASS__, 'check_manage_animals_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'create_application'],
                // Публічна форма подачі заявки — як контактна форма, без авторизації
                'permission_callback' => '__return_true',
            ]
        ]);

        // Заявки, подані ПОТОЧНИМ користувачем (як заявником) — вкладка
        // "Заявки на прилаштування" в /profile/. 'mine' не збігається з
        // regex \d+ маршруту нижче, тож порядок реєстрації не важливий.
        register_rest_route($namespace, '/applications/mine', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_my_applications'],
            'permission_callback' => [__CLASS__, 'check_is_logged_in'],
        ]);

        register_rest_route($namespace, '/applications/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [__CLASS__, 'update_application'],
                'permission_callback' => [__CLASS__, 'check_application_owner_permission'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [__CLASS__, 'delete_application'],
                // Власник організації/адмін (як і PUT) АБО сам заявник, що
                // видаляє свою заявку зі вкладки "Заявки на прилаштування" в /profile/
                'permission_callback' => [__CLASS__, 'check_application_delete_permission'],
            ],
        ]);

        // SIGNUP ROUTE (публічна реєстрація нового користувача)
        register_rest_route($namespace, '/signup', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'signup_user'],
            'permission_callback' => '__return_true',
        ]);

        // LOGIN ROUTE (публічний вхід через email/пароль)
        register_rest_route($namespace, '/login', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'login_user'],
            'permission_callback' => '__return_true',
        ]);

        // PROFILE ROUTES (редагування власного профілю — лише залогінений користувач, лише себе)
        register_rest_route($namespace, '/profile', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [__CLASS__, 'update_profile'],
            'permission_callback' => [__CLASS__, 'check_is_logged_in'],
        ]);

        register_rest_route($namespace, '/profile/avatar', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'upload_profile_avatar'],
            'permission_callback' => [__CLASS__, 'check_is_logged_in'],
        ]);

        register_rest_route($namespace, '/profile/avatar', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [__CLASS__, 'delete_profile_avatar'],
            'permission_callback' => [__CLASS__, 'check_is_logged_in'],
        ]);
    }
    
    // =======================================
    // ANIMALS ENDPOINTS
    // =======================================
    
    /**
     * GET /wp-json/lapki/v1/animals
     */
    public static function get_animals($request) {
        $params = [
            'type' => $request->get_param('type'),
            'species' => $request->get_param('species'),
            'breed' => $request->get_param('breed'),
            'age' => $request->get_param('age'),
            'gender' => $request->get_param('gender'),
            'size' => $request->get_param('size'),
            'status' => $request->get_param('status') ?: '',
            'location' => $request->get_param('location'),
            'distance' => $request->get_param('distance') ?: (int) get_option('lapki_default_distance', 50),
            'latitude' => $request->get_param('latitude'),
            'longitude' => $request->get_param('longitude'),
            'good_with_children' => self::parse_boolean($request->get_param('good_with_children')),
            'good_with_dogs' => self::parse_boolean($request->get_param('good_with_dogs')),
            'good_with_cats' => self::parse_boolean($request->get_param('good_with_cats')),
            'spayed_neutered' => self::parse_boolean($request->get_param('spayed_neutered')),
            'special_needs' => self::parse_boolean($request->get_param('special_needs')),
            'organization_id' => $request->get_param('organization_id'),
            'limit' => min($request->get_param('limit') ?: (int) get_option('lapki_default_page_size', 20), 100), // Максимум 100
            'offset' => $request->get_param('offset') ?: 0,
            'order_by' => $request->get_param('order_by') ?: 'published_at',
            'order' => $request->get_param('order') ?: 'DESC',
            'search' => $request->get_param('search') ?: ''
        ];

        $animals = Lapki_Animal::search($params);
        $total_count = Lapki_Animal::count($params);

        if (empty($animals)) {
            return new WP_REST_Response([
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => 1,
                    'per_page' => $params['limit']
                ]
            ], 200);
        }
        
        $current_page = floor($params['offset'] / $params['limit']) + 1;
        $total_pages = ceil($total_count / $params['limit']);
        
        return new WP_REST_Response([
            'data' => $animals,
            'pagination' => [
                'total' => $total_count,
                'pages' => $total_pages,
                'current_page' => $current_page,
                'per_page' => $params['limit']
            ]
        ], 200);
    }
    
    /**
     * GET /wp-json/lapki/v1/animals/{id}
     */
    public static function get_animal($request) {
        $id = $request->get_param('id');
        $animal = Lapki_Animal::get($id);

        if (!$animal) {
            return new WP_Error('animal_not_found', __('Тварину не знайдено', 'lapki'), ['status' => 404]);
        }

        // Перевірити чи є головне фото, якщо ні - встановити перше фото головним
        if (!empty($animal['media'])) {
            if (Lapki_Media::ensure_primary('animal', $id)) {
                // Перезавантажити дані тварини щоб відобразити оновлення
                $animal = Lapki_Animal::get($id);
            }
        }

        return new WP_REST_Response($animal, 200);
    }
    
    /**
     * "Додаткова інформація" (additional_attributes) — довільні пари attr_name=>value
     * з довідника атрибутів, яких немає серед стандартних колонок тварини. Зберігаються
     * одним JSON-стовпцем, тож перед записом у БД масив/значення з тіла запиту треба
     * привести до JSON-рядка (і санітизувати кожен ключ/значення як звичайний текст).
     */
    private static function normalize_additional_attributes($value) {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return null;
        }

        $clean = [];
        foreach ($value as $attr_name => $attr_value) {
            $attr_name = sanitize_key($attr_name);
            $attr_value = is_scalar($attr_value) ? sanitize_text_field((string) $attr_value) : '';
            if ($attr_name !== '' && $attr_value !== '') {
                $clean[$attr_name] = $attr_value;
            }
        }

        return wp_json_encode($clean);
    }

    /**
     * POST /wp-json/lapki/v1/animals
     */
    public static function create_animal($request) {
        $data = $request->get_json_params();

        if (array_key_exists('additional_attributes', $data)) {
            $data['additional_attributes'] = self::normalize_additional_attributes($data['additional_attributes']);
        }

        // Базова валідація
        $required_fields = ['organization_id', 'name', 'type', 'age', 'gender', 'size'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(
                    /* translators: %s: field name */
                    __("Поле '%s' є обов'язковим", 'lapki'),
                    $field
                ), ['status' => 400]);
            }
        }

        $city_check = self::validate_geo_city($data, 'address_city', 'address_city_katottg');
        if (is_wp_error($city_check)) {
            return $city_check;
        }

        // Автоматично заповнити species з type (це одне і те ж)
        if (empty($data['species'])) {
            $data['species'] = $data['type'];
        }

        // Хто додав тварину — завжди з сесії, а не з тіла запиту (щоб не можна
        // було підмінити чужим user_id); анонімний create неможливий, бо
        // check_create_animal_permission() вимагає capability + власність організації
        $data['created_by_user_id'] = get_current_user_id();

        $animal_id = Lapki_Animal::create($data);
        
        if (!$animal_id) {
            return new WP_Error('creation_failed', __('Не вдалося створити тварину', 'lapki'), ['status' => 500]);
        }
        
        $animal = Lapki_Animal::get($animal_id);
        return new WP_REST_Response($animal, 201);
    }
    
    /**
     * PUT /wp-json/lapki/v1/animals/{id}
     */
    public static function update_animal($request) {
        $id = $request->get_param('id');
        $data = $request->get_json_params();

        $existing = Lapki_Animal::get($id);
        if (!$existing) {
            return new WP_Error('animal_not_found', __('Тварину не знайдено', 'lapki'), ['status' => 404]);
        }

        // Автоматично заповнити species з type (це одне і те ж)
        if (!empty($data['type']) && empty($data['species'])) {
            $data['species'] = $data['type'];
        }

        // Хто додав тварину — незмінне після створення, ігноруємо будь-яке значення з тіла запиту
        unset($data['created_by_user_id']);

        if (array_key_exists('additional_attributes', $data)) {
            $data['additional_attributes'] = self::normalize_additional_attributes($data['additional_attributes']);
        }

        $updated = Lapki_Animal::update($id, $data);
        
        if ($updated === false) {
            return new WP_Error('update_failed', __('Не вдалося оновити тварину', 'lapki'), ['status' => 500]);
        }
        
        $animal = Lapki_Animal::get($id);
        return new WP_REST_Response($animal, 200);
    }
    
    /**
     * DELETE /wp-json/lapki/v1/animals/{id}
     */
    public static function delete_animal($request) {
        $id = $request->get_param('id');
        
        $existing = Lapki_Animal::get($id);
        if (!$existing) {
            return new WP_Error('animal_not_found', __('Тварину не знайдено', 'lapki'), ['status' => 404]);
        }
        
        $deleted = Lapki_Animal::delete($id);
        
        if (!$deleted) {
            return new WP_Error('delete_failed', __('Не вдалося видалити тварину', 'lapki'), ['status' => 500]);
        }
        
        return new WP_REST_Response(['message' => __('Тварину успішно видалено', 'lapki')], 200);
    }
    
    // =======================================
    // ANIMAL TYPES ENDPOINTS
    // =======================================
    
    /**
     * GET /wp-json/lapki/v1/types/all
     */
    public static function get_all_type_attributes($request) {
        $lang = $request->get_param('lang') ?: Lapki_I18n::get_lang();
        $attributes = Lapki_Attributes::get_global_attributes($lang);

        return new WP_REST_Response([
            'attributes' => $attributes
        ], 200);
    }

    /**
     * GET /wp-json/lapki/v1/types
     */
    public static function get_animal_types($request) {
        $lang = $request->get_param('lang') ?: Lapki_I18n::get_lang();
        $types = Lapki_Attributes::get_animal_types($lang);
        
        return new WP_REST_Response([
            'types' => $types
        ], 200);
    }
    
    /**
     * GET /wp-json/lapki/v1/types/{type}
     */
    public static function get_type_details($request) {
        $type = $request->get_param('type');
        $lang = $request->get_param('lang') ?: Lapki_I18n::get_lang();
        
        $attributes = Lapki_Attributes::get_type_attributes($type, $lang);
        
        if (empty($attributes)) {
            return new WP_Error('type_not_found', __('Вид тварини не знайдено', 'lapki'), ['status' => 404]);
        }
        
        return new WP_REST_Response([
            'type' => $type,
            'attributes' => $attributes
        ], 200);
    }
    
    /**
     * GET /wp-json/lapki/v1/types/{type}/breeds
     */
    public static function get_type_breeds($request) {
        $type = $request->get_param('type');
        $lang = $request->get_param('lang') ?: Lapki_I18n::get_lang();
        
        $breeds = Lapki_Attributes::get_breeds_by_type($type, $lang);
        
        return new WP_REST_Response([
            'type' => $type,
            'breeds' => $breeds
        ], 200);
    }
    
    /**
     * GET /wp-json/lapki/v1/locations — підказки міст для автодоповнення
     */
    public static function get_locations($request) {
        $query = trim((string) $request->get_param('query'));
        $limit = min((int) ($request->get_param('limit') ?: 10), 25);

        if (strlen($query) < 2) {
            return new WP_REST_Response(['data' => []], 200);
        }

        $locations = Lapki_Animal::search_locations($query, $limit);

        return new WP_REST_Response(['data' => $locations], 200);
    }

    /**
     * Перевірити, що поле "Місто" заповнене вибором конкретного населеного
     * пункту з довідника (katottg_code), а не довільним текстом — обов'язкове
     * при СТВОРЕННІ тварини/організації (не при частковому оновленні через PUT).
     */
    private static function validate_geo_city($data, $name_field, $katottg_field) {
        if (empty($data[$name_field]) || empty($data[$katottg_field])) {
            return new WP_Error(
                'missing_field',
                __("Поле 'Місто' є обов'язковим — оберіть населений пункт зі списку підказок", 'lapki'),
                ['status' => 400]
            );
        }

        if (!Lapki_Geo::get_by_katottg_code($data[$katottg_field])) {
            return new WP_Error(
                'invalid_city',
                __('Оберіть населений пункт зі списку підказок, а не довільний текст', 'lapki'),
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * GET /wp-json/lapki/v1/geo — автодоповнення для полів "Місто" з довідника
     * населених пунктів (КАТОТТГ), а не з уже введених значень (на відміну від
     * /locations вище, який підказує з фактично наявних тварин для фільтра пошуку)
     */
    public static function get_geo($request) {
        $query = trim((string) $request->get_param('query'));
        $limit = min((int) ($request->get_param('limit') ?: 20), 50);

        if (mb_strlen($query, 'UTF-8') < 2) {
            return new WP_REST_Response(['data' => []], 200);
        }

        $results = Lapki_Geo::search($query, $limit);

        return new WP_REST_Response(['data' => $results], 200);
    }

    /**
     * POST /wp-json/lapki/v1/ai/improve — "Покращити за допомогою ШІ" для
     * текстових полів (наразі — Опис тварини). Делегує дефолтному
     * зареєстрованому провайдеру (Lapki_AI_Manager) — сам ендпоінт нічого не
     * знає про конкретного провайдера (Gemini тощо).
     */
    public static function ai_improve_text($request) {
        $text = trim((string) $request->get_param('text'));

        if ($text === '') {
            return new WP_Error('ai_empty_text', __('Немає тексту для покращення.', 'lapki'), ['status' => 400]);
        }

        // Контекст — уже введені поля форми (кличка, вид, вік…), надіслані
        // фронтендом як { "Кличка": "Вася", "Вид": "Кіт", ... } — довільні
        // рядкові ключі/значення, тому санітизуємо й обмежуємо кількість тут,
        // а не покладаємось на схему аргументів REST (type=object її не описує).
        $context = $request->get_param('context');
        $clean_context = [];
        if (is_array($context)) {
            foreach ($context as $key => $value) {
                if (!is_string($key) || !is_scalar($value) || count($clean_context) >= 20) {
                    continue;
                }
                $clean_context[sanitize_text_field($key)] = sanitize_text_field((string) $value);
            }
        }

        $provider = Lapki_AI_Manager::get_default_provider();

        if (!$provider || !$provider->is_configured()) {
            // Навмисно 500, не 503 — Cloudflare (і CDN/проксі загалом) типово
            // перехоплює 502/503/504 і підміняє тіло відповіді власною HTML-
            // сторінкою помилки, навіть якщо origin коректно віддав валідний
            // JSON. 500 такому перехопленню за замовчуванням не підлягає.
            return new WP_Error('ai_not_configured', __('ШІ-провайдер не налаштований. Зверніться до адміністратора сайту.', 'lapki'), ['status' => 500]);
        }

        $result = $provider->improve_text($text, $clean_context);

        $usage = is_wp_error($result) ? [] : ($result['usage'] ?? []);
        $error_code = is_wp_error($result) ? $result->get_error_code() : null;
        $error_message = is_wp_error($result) ? $result->get_error_message() : null;
        Lapki_AI_Usage_Log::log($provider->get_id(), !is_wp_error($result), get_current_user_id(), $usage, $error_code, $error_message);

        if (is_wp_error($result)) {
            return new WP_Error($error_code, $error_message, ['status' => 500]);
        }

        return new WP_REST_Response(['text' => $result['text']], 200);
    }

    // =======================================
    // ORGANIZATIONS ENDPOINTS
    // =======================================
    
    /**
     * GET /wp-json/lapki/v1/organizations
     */
    public static function get_organizations($request) {
        $params = [
            'name' => $request->get_param('name'),
            'type' => $request->get_param('type'),
            'location' => $request->get_param('location'),
            'state' => $request->get_param('state'),
            'city' => $request->get_param('city'),
            'verified_only' => $request->get_param('verified_only') ?: false,
            'limit' => min($request->get_param('limit') ?: 20, 100),
            'offset' => $request->get_param('offset') ?: 0
        ];
        
        $organizations = Lapki_Organization::search($params);
        
        return new WP_REST_Response([
            'data' => $organizations,
            'pagination' => [
                'per_page' => $params['limit'],
                'offset' => $params['offset']
            ]
        ], 200);
    }
    
    /**
     * GET /wp-json/lapki/v1/organizations/{id}
     */
    public static function get_organization($request) {
        $id = $request->get_param('id');
        $organization = Lapki_Organization::get($id);
        
        if (!$organization) {
            return new WP_Error('organization_not_found', __('Організацію не знайдено', 'lapki'), ['status' => 404]);
        }
        
        return new WP_REST_Response($organization, 200);
    }
    
    /**
     * POST /wp-json/lapki/v1/organizations
     * Самостійна реєстрація НОВОЇ організації залогіненим користувачем —
     * робить його власником одразу (роль 'owner', status 'approved' +
     * WP-роль lapki_shelter_admin). Користувач може мати й інші організації
     * одночасно (заявки/членство) — це не блокується.
     */
    public static function create_organization($request) {
        $data = $request->get_json_params();
        $wp_user_id = get_current_user_id();

        // Базова валідація
        $required_fields = ['name', 'type'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(
                    /* translators: %s: field name */
                    __("Поле '%s' є обов'язковим", 'lapki'),
                    $field
                ), ['status' => 400]);
            }
        }

        $city_check = self::validate_geo_city($data, 'city', 'city_katottg');
        if (is_wp_error($city_check)) {
            return $city_check;
        }

        $data['wp_user_id'] = $wp_user_id;

        $org_id = Lapki_Organization::create($data);

        if (!$org_id) {
            return new WP_Error('creation_failed', __('Не вдалося створити організацію', 'lapki'), ['status' => 500]);
        }

        Lapki_Organization_Member::join($org_id, $wp_user_id, Lapki_Organization_Member::ROLE_OWNER, Lapki_Organization_Member::STATUS_APPROVED);

        $user = get_userdata($wp_user_id);
        if ($user && !in_array('administrator', $user->roles, true)) {
            $user->set_role(Lapki_Roles::ROLE_SHELTER_ADMIN);
        }

        $organization = Lapki_Organization::get($org_id);
        return new WP_REST_Response($organization, 201);
    }

    /**
     * POST /wp-json/lapki/v1/organizations/{id}/join
     * Надіслати заявку на приєднання до вже існуючої організації —
     * status='pending', жодних прав не дає, доки власник не підтвердить
     * (POST /organizations/{id}/join-requests/{user_id}/approve). Власник
     * отримує сповіщення email + бачить заявку в кабінеті.
     */
    public static function join_organization($request) {
        $organization_id = $request->get_param('id');
        $wp_user_id = get_current_user_id();

        $organization = Lapki_Organization::get($organization_id);
        if (!$organization) {
            return new WP_Error('organization_not_found', __('Організацію не знайдено', 'lapki'), ['status' => 404]);
        }

        if ($organization['type'] === 'individual') {
            return new WP_Error(
                'not_joinable',
                __('Це не організація, а профіль приватної особи — приєднатись до нього не можна.', 'lapki'),
                ['status' => 400]
            );
        }

        if (Lapki_Organization_Member::get_membership($organization_id, $wp_user_id)) {
            return new WP_Error(
                'already_member',
                __('Ви вже подавали заявку або є учасником цієї організації.', 'lapki'),
                ['status' => 409]
            );
        }

        $joined = Lapki_Organization_Member::join($organization_id, $wp_user_id, Lapki_Organization_Member::ROLE_MEMBER, Lapki_Organization_Member::STATUS_PENDING);
        if (!$joined) {
            return new WP_Error('join_failed', __('Не вдалося надіслати заявку на приєднання', 'lapki'), ['status' => 500]);
        }

        self::send_join_request_email($organization, get_userdata($wp_user_id));

        return new WP_REST_Response([
            'success' => true,
            'status' => Lapki_Organization_Member::STATUS_PENDING,
            'message' => __('Заявку надіслано. Очікуйте підтвердження власником організації.', 'lapki'),
        ], 202);
    }

    /**
     * Email-нотифікація власнику (власникам) організації про нову заявку на
     * приєднання — окрема заявка може загубитись, якщо помітна лише в кабінеті.
     */
    private static function send_join_request_email($organization, $applicant) {
        if (!$applicant) {
            return;
        }

        $owners = array_filter(
            Lapki_Organization_Member::get_members($organization['id'], Lapki_Organization_Member::STATUS_APPROVED),
            function ($m) { return $m['role'] === Lapki_Organization_Member::ROLE_OWNER; }
        );

        $recipients = array_unique(array_filter(array_map(function ($m) { return $m['user_email']; }, $owners)));
        if (empty($recipients) && !empty($organization['email'])) {
            $recipients = [$organization['email']];
        }
        if (empty($recipients)) {
            return;
        }

        $subject = sprintf(
            /* translators: %s: organization name */
            __('Нова заявка на приєднання до "%s"', 'lapki'),
            $organization['name']
        );
        $body = sprintf(
            "Користувач %s (%s) подав заявку на приєднання до організації \"%s\".\n\nПідтвердити або відхилити можна в кабінеті: %s",
            $applicant->display_name,
            $applicant->user_email,
            $organization['name'],
            home_url('/profile/?tab=organizations')
        );

        wp_mail($recipients, $subject, $body);
    }

    /**
     * POST /wp-json/lapki/v1/organizations/{id}/join-requests/{user_id}/approve
     * Підтвердити заявку на приєднання — лише власник організації.
     */
    public static function approve_join_request($request) {
        $organization_id = $request->get_param('id');
        $applicant_id = $request->get_param('user_id');

        $membership = Lapki_Organization_Member::get_membership($organization_id, $applicant_id);
        if (!$membership || $membership['status'] !== Lapki_Organization_Member::STATUS_PENDING) {
            return new WP_Error('request_not_found', __('Заявку не знайдено', 'lapki'), ['status' => 404]);
        }

        $approved = Lapki_Organization_Member::approve_request($organization_id, $applicant_id);
        if (!$approved) {
            return new WP_Error('approve_failed', __('Не вдалося підтвердити заявку', 'lapki'), ['status' => 500]);
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * POST /wp-json/lapki/v1/organizations/{id}/join-requests/{user_id}/reject
     * Відхилити заявку на приєднання — лише власник організації.
     */
    public static function reject_join_request($request) {
        $organization_id = $request->get_param('id');
        $applicant_id = $request->get_param('user_id');

        $membership = Lapki_Organization_Member::get_membership($organization_id, $applicant_id);
        if (!$membership || $membership['status'] !== Lapki_Organization_Member::STATUS_PENDING) {
            return new WP_Error('request_not_found', __('Заявку не знайдено', 'lapki'), ['status' => 404]);
        }

        Lapki_Organization_Member::reject_request($organization_id, $applicant_id);

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * POST /wp-json/lapki/v1/organizations/{id}/leave
     * Вийти з КОНКРЕТНОЇ організації (скасовує й власну заявку, що очікує
     * підтвердження). Власник вийти так не може — організація лишилась би
     * без власника; спершу має передати право власності
     * (POST /organizations/{id}/transfer).
     */
    public static function leave_organization($request) {
        $organization_id = $request->get_param('id');
        $wp_user_id = get_current_user_id();
        $membership = Lapki_Organization_Member::get_membership($organization_id, $wp_user_id);

        if (!$membership) {
            return new WP_Error('not_a_member', __('Ви не прив\'язані до цієї організації', 'lapki'), ['status' => 404]);
        }

        if ($membership['role'] === Lapki_Organization_Member::ROLE_OWNER && $membership['status'] === Lapki_Organization_Member::STATUS_APPROVED) {
            return new WP_Error(
                'owner_cannot_leave',
                __('Власник не може вийти з організації. Спершу передайте право власності іншому учаснику.', 'lapki'),
                ['status' => 403]
            );
        }

        Lapki_Organization_Member::leave($organization_id, $wp_user_id);

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * POST /wp-json/lapki/v1/organizations/{id}/transfer
     * Власник передає право власності іншому учаснику ТІЄЇ Ж організації.
     * Сам стає звичайним учасником (не виходить з організації).
     */
    public static function transfer_organization_owner($request) {
        $organization_id = $request->get_param('id');
        $wp_user_id = get_current_user_id();
        $new_owner_id = absint($request->get_param('new_owner_id'));

        if (!$new_owner_id || $new_owner_id === $wp_user_id) {
            return new WP_Error('invalid_new_owner', __('Оберіть іншого учасника організації.', 'lapki'), ['status' => 400]);
        }

        $transferred = Lapki_Organization_Member::transfer_owner($organization_id, $wp_user_id, $new_owner_id);
        if (!$transferred) {
            return new WP_Error(
                'not_a_member',
                __('Обраний користувач не є учасником цієї організації.', 'lapki'),
                ['status' => 400]
            );
        }

        $old_owner = get_userdata($wp_user_id);
        if ($old_owner && !in_array('administrator', $old_owner->roles, true)) {
            $old_owner->set_role(Lapki_Roles::ROLE_VOLUNTEER);
        }

        $new_owner = get_userdata($new_owner_id);
        if ($new_owner && !in_array('administrator', $new_owner->roles, true)) {
            $new_owner->set_role(Lapki_Roles::ROLE_SHELTER_ADMIN);
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Дозволити дію будь-якому залогіненому користувачу (без вимог до capability) —
     * для самостійної реєстрації/приєднання/виходу з організації.
     */
    public static function check_is_logged_in($request) {
        return is_user_logged_in();
    }

    /**
     * PUT /wp-json/lapki/v1/organizations/{id}
     */
    public static function update_organization($request) {
        $id = $request->get_param('id');
        $data = $request->get_json_params();

        $existing = Lapki_Organization::get($id);
        if (!$existing) {
            return new WP_Error('organization_not_found', __('Організацію не знайдено', 'lapki'), ['status' => 404]);
        }

        $updated = Lapki_Organization::update($id, $data);
        if (!$updated) {
            return new WP_Error('update_failed', __('Не вдалося оновити організацію', 'lapki'), ['status' => 500]);
        }

        return new WP_REST_Response(Lapki_Organization::get($id), 200);
    }

    /**
     * DELETE /wp-json/lapki/v1/organizations/{id}
     */
    public static function delete_organization($request) {
        $id = $request->get_param('id');

        $existing = Lapki_Organization::get($id);
        if (!$existing) {
            return new WP_Error('organization_not_found', __('Організацію не знайдено', 'lapki'), ['status' => 404]);
        }

        $deleted = Lapki_Organization::delete($id);
        if (!$deleted) {
            return new WP_Error('delete_failed', __('Не вдалося видалити організацію', 'lapki'), ['status' => 500]);
        }

        return new WP_REST_Response(['message' => __('Організацію успішно видалено', 'lapki')], 200);
    }

    /**
     * Редагування/видалення організації: саме власник ('owner'), не будь-який
     * учасник (або адмін сайту)
     */
    public static function check_organization_owner_permission($request) {
        return Lapki_Roles::user_is_organization_owner($request->get_param('id'), get_current_user_id());
    }

    // =======================================
    // STATISTICS ENDPOINT
    // =======================================
    
    /**
     * GET /wp-json/lapki/v1/stats
     */
    public static function get_stats($request) {
        $stats = Lapki_Animal::get_stats();
        
        return new WP_REST_Response([
            'animals' => $stats,
            'generated_at' => current_time('mysql')
        ], 200);
    }
    
    // =======================================
    // HELPER METHODS
    // =======================================
    
    private static function get_animals_search_args() {
        return [
            'type' => [
                'type' => 'string',
                'description' => 'Тип тварини'
            ],
            'species' => [
                'type' => 'string',
                'description' => 'Вид тварини'
            ],
            'breed' => [
                'type' => 'string',
                'description' => 'Порода тварини'
            ],
            'age' => [
                'type' => 'string',
                'enum' => ['baby', 'young', 'adult', 'senior'],
                'description' => 'Вік тварини'
            ],
            'gender' => [
                'type' => 'string',
                'enum' => ['male', 'female', 'unknown'],
                'description' => 'Стать тварини'
            ],
            'size' => [
                'type' => 'string',
                'enum' => ['small', 'medium', 'large', 'xlarge'],
                'description' => 'Розмір тварини'
            ],
            'status' => [
                'type' => 'string',
                'default' => 'adoptable',
                'description' => 'Статус тварини'
            ],
            'location' => [
                'type' => 'string',
                'description' => 'Місце розташування'
            ],
            'distance' => [
                'type' => 'integer',
                'default' => 50,
                'description' => 'Відстань у км'
            ],
            'latitude' => [
                'type' => 'number',
                'description' => 'Широта для пошуку'
            ],
            'longitude' => [
                'type' => 'number',
                'description' => 'Довгота для пошуку'
            ],
            'good_with_children' => [
                'type' => 'boolean',
                'description' => 'Підходить для дітей'
            ],
            'good_with_dogs' => [
                'type' => 'boolean',
                'description' => 'Підходить для собак'
            ],
            'good_with_cats' => [
                'type' => 'boolean',
                'description' => 'Підходить для котів'
            ],
            'spayed_neutered' => [
                'type' => 'boolean',
                'description' => 'Стерилізована/кастрована'
            ],
            'special_needs' => [
                'type' => 'boolean',
                'description' => 'Особливі потреби'
            ],
            'organization_id' => [
                'type' => 'integer',
                'description' => 'ID організації'
            ],
            'limit' => [
                'type' => 'integer',
                'default' => 20,
                'maximum' => 100,
                'description' => 'Кількість результатів'
            ],
            'offset' => [
                'type' => 'integer',
                'default' => 0,
                'description' => 'Зсув для пагінації'
            ],
            'order_by' => [
                'type' => 'string',
                'enum' => ['published_at', 'name', 'age', 'distance', 'updated_at'],
                'default' => 'published_at',
                'description' => 'Поле для сортування'
            ],
            'order' => [
                'type' => 'string',
                'enum' => ['ASC', 'DESC'],
                'default' => 'DESC',
                'description' => 'Порядок сортування'
            ],
            'search' => [
                'type' => 'string',
                'description' => 'Пошук за кличкою тварини'
            ]
        ];
    }
    
    private static function get_organizations_search_args() {
        return [
            'name' => [
                'type' => 'string',
                'description' => 'Назва організації'
            ],
            'type' => [
                'type' => 'string',
                'enum' => ['individual', 'shelter', 'rescue', 'vet_clinic', 'vet', 'volunteer'],
                'description' => 'Тип організації'
            ],
            'location' => [
                'type' => 'string',
                'description' => 'Місце розташування'
            ],
            'state' => [
                'type' => 'string',
                'description' => 'Область/регіон'
            ],
            'city' => [
                'type' => 'string',
                'description' => 'Місто'
            ],
            'verified_only' => [
                'type' => 'boolean',
                'default' => false,
                'description' => 'Тільки верифіковані організації'
            ],
            'limit' => [
                'type' => 'integer',
                'default' => 20,
                'maximum' => 100,
                'description' => 'Кількість результатів'
            ],
            'offset' => [
                'type' => 'integer',
                'default' => 0,
                'description' => 'Зсув для пагінації'
            ]
        ];
    }
    
    private static function parse_boolean($value) {
        if ($value === null || $value === '') {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
    
    // =======================================
    // PERMISSION CALLBACKS
    //
    // Читання (GET) лишається публічним — це основна функція платформи
    // (публічний пошук тварин, як на petfinder.com). Авторизація потрібна
    // тільки для операцій, що змінюють дані.
    // =======================================

    /**
     * Створення тварини: потрібна capability lapki_manage_animals
     */
    public static function check_manage_animals_permission($request) {
        return current_user_can(Lapki_Roles::CAP_MANAGE_ANIMALS);
    }

    /**
     * Створення тварини: capability + organization_id з тіла запиту має належати
     * поточному користувачу (або адмін). Без цього будь-хто з lapki_manage_animals
     * (напр. звичайний volunteer через публічну форму /add-animal/) міг би
     * підставити чужий organization_id і прив'язати тварину не до своєї організації.
     */
    public static function check_create_animal_permission($request) {
        if (!current_user_can(Lapki_Roles::CAP_MANAGE_ANIMALS)) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        $data = $request->get_json_params();
        $organization_id = !empty($data['organization_id']) ? absint($data['organization_id']) : 0;

        if (!$organization_id) {
            return false;
        }

        return Lapki_Roles::user_owns_organization($organization_id, get_current_user_id());
    }

    /**
     * Редагування/видалення тварини: capability + власник організації (або адмін)
     */
    public static function check_animal_owner_permission($request) {
        if (!current_user_can(Lapki_Roles::CAP_MANAGE_ANIMALS)) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        $animal = Lapki_Animal::get($request->get_param('id'));
        if (!$animal) {
            // Дати колбеку самому повернути 404
            return true;
        }

        return Lapki_Roles::user_owns_organization($animal['organization_id'], get_current_user_id());
    }

    /**
     * Завантаження фото тварини: capability + власник організації (або адмін)
     */
    public static function check_animal_media_permission($request) {
        if (!current_user_can(Lapki_Roles::CAP_MANAGE_ANIMALS)) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        $animal = Lapki_Animal::get($request->get_param('animal_id'));
        if (!$animal) {
            return true;
        }

        return Lapki_Roles::user_owns_organization($animal['organization_id'], get_current_user_id());
    }

    /**
     * Видалення/зміна головного медіа: capability + власник організації тварини (або адмін)
     */
    public static function check_media_owner_permission($request) {
        if (!current_user_can(Lapki_Roles::CAP_MANAGE_ANIMALS)) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        $media = Lapki_Media::get($request->get_param('id'));
        if (!$media) {
            return true;
        }

        if ($media['entity_type'] === 'animal') {
            $animal = Lapki_Animal::get($media['entity_id']);
            if ($animal) {
                return Lapki_Roles::user_owns_organization($animal['organization_id'], get_current_user_id());
            }
        }

        if ($media['entity_type'] === 'organization') {
            return Lapki_Roles::user_owns_organization($media['entity_id'], get_current_user_id());
        }

        return false;
    }

    /**
     * Завантаження фото/відео притулку: capability + власник/учасник
     * організації (або адмін) — той самий рівень доступу, що й для медіа тварин
     */
    public static function check_organization_media_permission($request) {
        if (!current_user_can(Lapki_Roles::CAP_MANAGE_ANIMALS)) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        return Lapki_Roles::user_owns_organization($request->get_param('id'), get_current_user_id());
    }

    /**
     * Створення організації: потрібна capability lapki_manage_organizations
     */
    public static function check_manage_organizations_permission($request) {
        return current_user_can(Lapki_Roles::CAP_MANAGE_ORGANIZATIONS);
    }

    /**
     * Керування глобальним довідником атрибутів: тільки адміністратор
     */
    public static function check_manage_attributes_permission($request) {
        return current_user_can(Lapki_Roles::CAP_MANAGE_ATTRIBUTES);
    }

    /**
     * Email-шаблони — окреме, суто адмінське налаштування сайту (не
     * пов'язане з конкретною організацією/тваринами), тому manage_options
     * напряму, а не одна з capability lapki_manage_*.
     */
    public static function check_manage_email_templates_permission($request) {
        return current_user_can('manage_options');
    }

    // =======================================
    // MEDIA ENDPOINTS
    // =======================================

    /**
     * POST /wp-json/lapki/v1/animals/{animal_id}/media
     */
    public static function upload_animal_media($request) {
        $animal_id = $request->get_param('animal_id');

        // Перевірити чи існує тварина
        $animal = Lapki_Animal::get($animal_id);
        if (!$animal) {
            return new WP_Error('animal_not_found', __('Тварину не знайдено', 'lapki'), ['status' => 404]);
        }

        // Перевірити чи є файл
        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new WP_Error('no_file', __('Файл не надіслано', 'lapki'), ['status' => 400]);
        }

        $file = $files['file'];

        // Валідація розміру (макс 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            return new WP_Error('file_too_large', __('Файл занадто великий. Максимум 10MB', 'lapki'), ['status' => 400]);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $is_primary = !Lapki_Media::has_primary('animal', $animal_id) ? 1 : 0;
        $sort_order = Lapki_Media::get_next_sort_order('animal', $animal_id);

        $result = Lapki_Media::upload_image($file, 'animal', $animal_id, $animal['name'] ?? '', $is_primary, $sort_order);

        if (is_wp_error($result)) {
            $server_side_errors = ['move_error', 'db_error'];
            $status = in_array($result->get_error_code(), $server_side_errors, true) ? 500 : 400;
            return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => $status]);
        }

        // Повернути дані медіа
        $media = Lapki_Media::get($result['media_id']);

        return new WP_REST_Response($media, 201);
    }

    /**
     * POST /wp-json/lapki/v1/organizations/{id}/media
     *
     * Фото притулку — зберігається окремо від фото тварин (uploads/lapki/org/),
     * та відображається на публічній сторінці організації окремо від грід-списку тварин.
     */
    public static function upload_organization_media($request) {
        $organization_id = $request->get_param('id');

        $organization = Lapki_Organization::get($organization_id);
        if (!$organization) {
            return new WP_Error('organization_not_found', __('Організацію не знайдено', 'lapki'), ['status' => 404]);
        }

        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new WP_Error('no_file', __('Файл не надіслано', 'lapki'), ['status' => 400]);
        }

        $file = $files['file'];

        if ($file['size'] > 10 * 1024 * 1024) {
            return new WP_Error('file_too_large', __('Файл занадто великий. Максимум 10MB', 'lapki'), ['status' => 400]);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $is_primary = !Lapki_Media::has_primary('organization', $organization_id) ? 1 : 0;
        $sort_order = Lapki_Media::get_next_sort_order('organization', $organization_id);

        $result = Lapki_Media::upload_image($file, 'organization', $organization_id, $organization['name'] ?? '', $is_primary, $sort_order);

        if (is_wp_error($result)) {
            $server_side_errors = ['move_error', 'db_error'];
            $status = in_array($result->get_error_code(), $server_side_errors, true) ? 500 : 400;
            return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => $status]);
        }

        $media = Lapki_Media::get($result['media_id']);

        return new WP_REST_Response($media, 201);
    }

    /**
     * POST /wp-json/lapki/v1/organizations/{id}/video
     *
     * Відео притулку — зовнішнє посилання (YouTube/Vimeo/...), без
     * завантаження й обробки файлу (для цього немає інфраструктури — той самий
     * підхід зарезервований у схемі БД полем video_url для будь-якої сутності).
     * Приймає довільний текст із одним чи кількома посиланнями — розділювач
     * не має значення (пробіл, кома, новий рядок), розбирається за URL-маскою.
     */
    public static function add_organization_video($request) {
        $organization_id = $request->get_param('id');

        $organization = Lapki_Organization::get($organization_id);
        if (!$organization) {
            return new WP_Error('organization_not_found', __('Організацію не знайдено', 'lapki'), ['status' => 404]);
        }

        return self::create_video_media_bulk('organization', $organization_id, $request->get_param('video_urls'));
    }

    /**
     * POST /wp-json/lapki/v1/animals/{animal_id}/video
     *
     * Відео тварини — той самий підхід, що й для організацій (див. вище):
     * лише зовнішні посилання, без завантаження файлів.
     */
    public static function add_animal_video($request) {
        $animal_id = $request->get_param('animal_id');

        $animal = Lapki_Animal::get($animal_id);
        if (!$animal) {
            return new WP_Error('animal_not_found', __('Тварину не знайдено', 'lapki'), ['status' => 404]);
        }

        return self::create_video_media_bulk('animal', $animal_id, $request->get_param('video_urls'));
    }

    /**
     * Розібрати текстовий блок посилань (Lapki_Media::parse_video_urls) і
     * створити по одному media-запису на кожне валідне посилання.
     */
    private static function create_video_media_bulk($entity_type, $entity_id, $raw_text) {
        $urls = Lapki_Media::parse_video_urls((string) $raw_text);

        if (empty($urls)) {
            return new WP_Error(
                'no_valid_video_urls',
                __('Не знайдено жодного дійсного посилання на відео (YouTube, Vimeo, TikTok, Instagram, Facebook, Dailymotion)', 'lapki'),
                ['status' => 400]
            );
        }

        $created = [];
        $sort_order = Lapki_Media::get_next_sort_order($entity_type, $entity_id);
        foreach ($urls as $url) {
            $media_id = Lapki_Media::create([
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'media_type' => 'video',
                'video_url' => $url,
                'sort_order' => $sort_order++,
            ]);
            if ($media_id) {
                $created[] = Lapki_Media::get($media_id);
            }
        }

        if (empty($created)) {
            return new WP_Error('create_failed', __('Не вдалося додати відео', 'lapki'), ['status' => 500]);
        }

        return new WP_REST_Response(['data' => $created, 'count' => count($created)], 201);
    }

    /**
     * DELETE /wp-json/lapki/v1/media/{id}
     */
    public static function delete_media($request) {
        $id = $request->get_param('id');

        $media = Lapki_Media::get($id);
        if (!$media) {
            return new WP_Error('media_not_found', __('Медіа не знайдено', 'lapki'), ['status' => 404]);
        }

        $was_primary = $media['is_primary'];
        $entity_type = $media['entity_type'];
        $entity_id = $media['entity_id'];

        // Видалити з бази і файли (Lapki_Media::delete видаляє і файли і запис БД)
        $deleted = Lapki_Media::delete($id);

        if (!$deleted) {
            return new WP_Error('delete_failed', __('Не вдалося видалити медіа', 'lapki'), ['status' => 500]);
        }

        // Якщо видалене фото було головним - встановити наступне головним
        if ($was_primary) {
            Lapki_Media::ensure_primary($entity_type, $entity_id);
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    // =======================================
    // ATTRIBUTES ENDPOINTS
    // =======================================

    /**
     * GET /wp-json/lapki/v1/attributes
     */
    public static function get_attributes($request) {
        $filters = [
            'lang'        => $request->get_param('lang') ?: '',
            'entity'      => $request->get_param('entity') ?: '',
            'entity_type' => $request->get_param('entity_type') ?: '',
            'attr_name'   => $request->get_param('attr_name') ?: '',
            'search'      => $request->get_param('search') ?: '',
            'limit'       => min($request->get_param('limit') ?: 50, 200),
            'offset'      => $request->get_param('offset') ?: 0,
        ];

        $data  = Lapki_Attributes::get_all($filters);
        $total = Lapki_Attributes::count($filters);

        return new WP_REST_Response([
            'data' => $data,
            'pagination' => [
                'total'        => $total,
                'pages'        => (int) ceil($total / $filters['limit']),
                'current_page' => (int) floor($filters['offset'] / $filters['limit']) + 1,
                'per_page'     => $filters['limit'],
            ]
        ], 200);
    }

    /**
     * POST /wp-json/lapki/v1/attributes
     */
    public static function create_attribute($request) {
        $data = [
            'entity'       => sanitize_text_field($request->get_param('entity')),
            'entity_type'  => sanitize_text_field($request->get_param('entity_type')),
            'attr_name'    => sanitize_text_field($request->get_param('attr_name')),
            'attr_value'   => sanitize_text_field($request->get_param('attr_value')),
            'attr_display' => sanitize_text_field($request->get_param('attr_display')),
            'lang'         => sanitize_text_field($request->get_param('lang')),
        ];

        foreach ($data as $key => $val) {
            if (empty($val)) {
                return new WP_Error('missing_field', sprintf(
                    /* translators: %s: field name */
                    __("Поле '%s' обов'язкове", 'lapki'),
                    $key
                ), ['status' => 400]);
            }
        }

        $id = Lapki_Attributes::create($data);
        if (!$id) {
            return new WP_Error('create_failed', __('Помилка створення атрибуту', 'lapki'), ['status' => 500]);
        }

        return new WP_REST_Response(Lapki_Attributes::get($id), 201);
    }

    /**
     * PUT /wp-json/lapki/v1/attributes/{id}
     */
    public static function update_attribute($request) {
        $id   = $request->get_param('id');
        $attr = Lapki_Attributes::get($id);

        if (!$attr) {
            return new WP_Error('not_found', __('Атрибут не знайдено', 'lapki'), ['status' => 404]);
        }

        $data = [];
        foreach (['entity', 'entity_type', 'attr_name', 'attr_value', 'attr_display', 'lang'] as $field) {
            $val = $request->get_param($field);
            if ($val !== null) {
                $data[$field] = sanitize_text_field($val);
            }
        }

        Lapki_Attributes::update($id, $data);
        return new WP_REST_Response(Lapki_Attributes::get($id), 200);
    }

    /**
     * DELETE /wp-json/lapki/v1/attributes/{id}
     */
    public static function delete_attribute($request) {
        $id   = $request->get_param('id');
        $attr = Lapki_Attributes::get($id);

        if (!$attr) {
            return new WP_Error('not_found', __('Атрибут не знайдено', 'lapki'), ['status' => 404]);
        }

        Lapki_Attributes::delete($id);
        return new WP_REST_Response(['success' => true], 200);
    }

    // =======================================
    // EMAIL TEMPLATES ENDPOINTS
    // =======================================

    /**
     * GET /wp-json/lapki/v1/email-templates
     */
    public static function get_email_templates($request) {
        return new WP_REST_Response(['data' => Lapki_Email_Template::get_all()], 200);
    }

    /**
     * PUT /wp-json/lapki/v1/email-templates/{id} — редагування лише subject/body
     */
    public static function update_email_template($request) {
        $id       = $request->get_param('id');
        $template = Lapki_Email_Template::get($id);

        if (!$template) {
            return new WP_Error('not_found', __('Шаблон не знайдено', 'lapki'), ['status' => 404]);
        }

        $subject = sanitize_text_field((string) $request->get_param('subject'));
        $body    = sanitize_textarea_field((string) $request->get_param('body'));

        if (empty($subject) || empty($body)) {
            return new WP_Error('invalid_data', __("Заповніть тему і текст листа", 'lapki'), ['status' => 400]);
        }

        Lapki_Email_Template::update($id, ['subject' => $subject, 'body' => $body]);
        return new WP_REST_Response(Lapki_Email_Template::get($id), 200);
    }

    /**
     * PUT /wp-json/lapki/v1/media/{id}/primary
     */
    public static function set_primary_media($request) {
        $id = $request->get_param('id');

        $media = Lapki_Media::get($id);
        if (!$media) {
            return new WP_Error('media_not_found', __('Медіа не знайдено', 'lapki'), ['status' => 404]);
        }

        // Встановити медіа головним через API (автоматично знімає is_primary з інших)
        $result = Lapki_Media::set_primary($id);

        if (!$result) {
            return new WP_Error('update_failed', __('Не вдалося встановити головне фото', 'lapki'), ['status' => 500]);
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    // =======================================
    // APPLICATIONS ENDPOINTS (заявки на усиновлення)
    // =======================================

    /**
     * GET /wp-json/lapki/v1/applications
     * Адмін бачить усі (за organization_id), власник організації — тільки свої
     */
    public static function get_applications($request) {
        $organization_id = absint($request->get_param('organization_id'));

        if (!current_user_can('manage_options')) {
            // Учасник може належати до кількох організацій — якщо він явно
            // вказав organization_id, довіряємо йому лише за умови членства
            // саме в цій організації; інакше падаємо на першу зі своїх.
            if ($organization_id && Lapki_Roles::user_owns_organization($organization_id, get_current_user_id())) {
                // organization_id вже коректний, нічого не змінюємо
            } else {
                $orgs = Lapki_Organization::get_by_wp_user_id(get_current_user_id());
                if (empty($orgs)) {
                    return new WP_REST_Response(['data' => []], 200);
                }
                $organization_id = (int) $orgs[0]['id'];
            }
        }

        if (empty($organization_id)) {
            return new WP_Error('missing_param', __("Параметр 'organization_id' обов'язковий", 'lapki'), ['status' => 400]);
        }

        $applications = Lapki_Application::get_by_organization($organization_id, $request->get_param('status') ?: '');

        return new WP_REST_Response(['data' => $applications], 200);
    }

    /**
     * GET /wp-json/lapki/v1/applications/mine
     * Заявки, подані поточним залогіненим користувачем (як заявником) —
     * для вкладки "Заявки на прилаштування" в /profile/.
     */
    public static function get_my_applications($request) {
        return new WP_REST_Response(['data' => Lapki_Application::get_by_user(get_current_user_id())], 200);
    }

    /**
     * POST /wp-json/lapki/v1/applications
     * Публічна форма подачі заявки на усиновлення (без авторизації)
     */
    public static function create_application($request) {
        $animal_id = absint($request->get_param('animal_id'));
        $animal = Lapki_Animal::get($animal_id);

        if (!$animal) {
            return new WP_Error('animal_not_found', __('Тварину не знайдено', 'lapki'), ['status' => 404]);
        }

        // Дзеркалить UI-обмеження на /animals/{id}/ (неактивна кнопка "Хочу
        // прилаштувати" для статусу 'found') — захист і на боці бекенду, щоб
        // заявку не можна було надіслати напряму через REST API в обхід UI.
        if ($animal['status'] === 'found') {
            return new WP_Error('animal_not_adoptable', __('Ця тварина ще не готова до прилаштування', 'lapki'), ['status' => 400]);
        }

        $applicant_name = sanitize_text_field($request->get_param('applicant_name'));
        $applicant_email = sanitize_email($request->get_param('applicant_email'));
        $applicant_phone = sanitize_text_field($request->get_param('applicant_phone'));
        $message = sanitize_textarea_field($request->get_param('message'));

        // Дзеркалить required-атрибути на формі (templates/single-animal.php) —
        // захист і на боці бекенду, щоб неповну заявку не можна було надіслати в обхід UI
        if (empty($applicant_name) || empty($applicant_email) || !is_email($applicant_email) || empty($applicant_phone) || empty($message)) {
            return new WP_Error('invalid_data', __("Заповніть усі поля: ім'я, коректний email, телефон і повідомлення", 'lapki'), ['status' => 400]);
        }

        $application_id = Lapki_Application::create([
            'animal_id' => $animal_id,
            'organization_id' => $animal['organization_id'],
            // Ігноруємо будь-яке значення wp_user_id від клієнта — лише
            // поточна залогінена сесія (щоб заявку не можна було підв'язати
            // до чужого акаунту). Гість лишає NULL — форма публічна.
            'wp_user_id' => is_user_logged_in() ? get_current_user_id() : null,
            'applicant_name' => $applicant_name,
            'applicant_email' => $applicant_email,
            'applicant_phone' => $applicant_phone,
            'message' => $message,
        ]);

        if (!$application_id) {
            return new WP_Error('creation_failed', __('Не вдалося надіслати заявку', 'lapki'), ['status' => 500]);
        }

        self::send_application_emails($application_id, $animal);

        return new WP_REST_Response(['success' => true, 'id' => $application_id], 201);
    }

    /**
     * PUT /wp-json/lapki/v1/applications/{id}
     * Зміна статусу заявки (new/contacted/approved/rejected)
     */
    public static function update_application($request) {
        $id = $request->get_param('id');
        $application = Lapki_Application::get($id);

        if (!$application) {
            return new WP_Error('not_found', __('Заявку не знайдено', 'lapki'), ['status' => 404]);
        }

        $status = sanitize_text_field($request->get_param('status'));
        $allowed_statuses = [
            Lapki_Application::STATUS_NEW,
            Lapki_Application::STATUS_CONTACTED,
            Lapki_Application::STATUS_APPROVED,
            Lapki_Application::STATUS_REJECTED,
        ];

        if (!in_array($status, $allowed_statuses, true)) {
            return new WP_Error('invalid_status', __('Некоректний статус', 'lapki'), ['status' => 400]);
        }

        Lapki_Application::update_status($id, $status);

        return new WP_REST_Response(Lapki_Application::get($id), 200);
    }

    /**
     * DELETE /wp-json/lapki/v1/applications/{id}
     * Власник організації/адмін АБО сам заявник (кнопка "Х" на вкладці
     * "Заявки на прилаштування" в /profile/) — дозвіл уже перевірено
     * check_application_delete_permission()
     */
    public static function delete_application($request) {
        $id = $request->get_param('id');
        $application = Lapki_Application::get($id);

        if (!$application) {
            return new WP_Error('not_found', __('Заявку не знайдено', 'lapki'), ['status' => 404]);
        }

        Lapki_Application::delete($id);

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Перегляд/зміна заявки: власник організації тварини (або адмін)
     */
    public static function check_application_owner_permission($request) {
        if (!current_user_can(Lapki_Roles::CAP_MANAGE_ANIMALS)) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        $application = Lapki_Application::get($request->get_param('id'));
        if (!$application) {
            return true;
        }

        return Lapki_Roles::user_owns_organization($application['organization_id'], get_current_user_id());
    }

    /**
     * Видалення заявки: усе, що дозволяє check_application_owner_permission()
     * (власник організації/адмін), АБО сам заявник — видаляє власну заявку
     * зі вкладки "Заявки на прилаштування" в /profile/.
     */
    public static function check_application_delete_permission($request) {
        $application = Lapki_Application::get($request->get_param('id'));
        if ($application && is_user_logged_in() && (int) $application['wp_user_id'] === get_current_user_id()) {
            return true;
        }

        return self::check_application_owner_permission($request);
    }

    // =======================================
    // SIGNUP ENDPOINT
    // =======================================

    /**
     * POST /wp-json/lapki/v1/signup
     * Публічна реєстрація — лише акаунт користувача, без організації.
     * Прив'язка до притулку/ГО (створення нової або приєднання до вже
     * існуючої) — окремий крок у кабінеті (POST /organizations або
     * POST /organizations/{id}/join).
     */
    public static function signup_user($request) {
        $first_name = sanitize_text_field((string) $request->get_param('first_name'));
        $last_name = sanitize_text_field((string) $request->get_param('last_name'));
        $email = sanitize_email((string) $request->get_param('email'));
        $password = (string) $request->get_param('password');
        $phone = sanitize_text_field((string) $request->get_param('phone'));

        if (empty($first_name) || empty($last_name) || empty($email) || !is_email($email) || strlen($password) < 6
            || !preg_match('/^\+38 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $phone)) {
            return new WP_Error('invalid_data', __("Заповніть прізвище, ім'я, коректний email, пароль (мінімум 6 символів) і телефон у форматі +38 (XXX) XXX-XX-XX", 'lapki'), ['status' => 400]);
        }

        if (email_exists($email)) {
            return new WP_Error('email_exists', __('Користувач з таким email вже зареєстрований', 'lapki'), ['status' => 409]);
        }

        // Логін завжди формується з email (email — основний спосіб входу, класичний логін не використовується)
        $user_id = wp_insert_user([
            'user_login' => self::generate_unique_username($email),
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
            'role' => Lapki_Roles::ROLE_VOLUNTEER,
        ]);

        if (is_wp_error($user_id)) {
            return new WP_Error('user_creation_failed', $user_id->get_error_message(), ['status' => 500]);
        }

        if (!empty($phone)) {
            update_user_meta($user_id, 'lapki_phone', $phone);
        }

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        return new WP_REST_Response([
            'success' => true,
            'user_id' => $user_id,
            'redirect' => home_url('/profile/'),
        ], 201);
    }

    /**
     * POST /wp-json/lapki/v1/login
     * Публічний вхід через email + пароль (замінює стандартну wp-login.php-форму на фронтенді).
     */
    public static function login_user($request) {
        $email = sanitize_email((string) $request->get_param('email'));
        $password = (string) $request->get_param('password');

        if (empty($email) || !is_email($email) || empty($password)) {
            return new WP_Error('invalid_data', __('Вкажіть коректний email і пароль', 'lapki'), ['status' => 400]);
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            return new WP_Error('invalid_credentials', __('Неправильний email або пароль', 'lapki'), ['status' => 403]);
        }

        $signon = wp_signon([
            'user_login' => $user->user_login,
            'user_password' => $password,
            'remember' => true,
        ], is_ssl());

        if (is_wp_error($signon)) {
            return new WP_Error('invalid_credentials', __('Неправильний email або пароль', 'lapki'), ['status' => 403]);
        }

        wp_set_current_user($signon->ID);

        return new WP_REST_Response([
            'success' => true,
            'user_id' => $signon->ID,
            'redirect' => home_url('/profile/'),
        ], 200);
    }

    // =======================================
    // PROFILE ENDPOINT (редагування власного акаунта на /edit-profile/)
    // =======================================

    /**
     * PUT /wp-json/lapki/v1/profile
     * Ім'я, прізвище, телефон, email поточного користувача. Пароль тут не
     * змінюється (окрема, більш обережна дія — не потрібна для цієї задачі).
     */
    public static function update_profile($request) {
        $user_id = get_current_user_id();

        $first_name = sanitize_text_field((string) $request->get_param('first_name'));
        $last_name = sanitize_text_field((string) $request->get_param('last_name'));
        $email = sanitize_email((string) $request->get_param('email'));
        $phone = sanitize_text_field((string) $request->get_param('phone'));

        if (empty($first_name) || empty($last_name) || empty($email) || !is_email($email)) {
            return new WP_Error('invalid_data', __("Заповніть прізвище, ім'я і коректний email", 'lapki'), ['status' => 400]);
        }

        if (!empty($phone) && !preg_match('/^\+38 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $phone)) {
            return new WP_Error('invalid_data', __('Телефон має бути у форматі +38 (XXX) XXX-XX-XX', 'lapki'), ['status' => 400]);
        }

        $existing = email_exists($email);
        if ($existing && (int) $existing !== $user_id) {
            return new WP_Error('email_exists', __('Цей email вже використовується іншим акаунтом', 'lapki'), ['status' => 409]);
        }

        $updated = wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
            'user_email' => $email,
        ]);

        if (is_wp_error($updated)) {
            return new WP_Error('update_failed', $updated->get_error_message(), ['status' => 500]);
        }

        update_user_meta($user_id, 'lapki_phone', $phone);

        $user = get_userdata($user_id);

        return new WP_REST_Response([
            'success' => true,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => $phone,
        ], 200);
    }

    /**
     * POST /wp-json/lapki/v1/profile/avatar
     * На відміну від фото тварин/організацій — не галерея, а рівно одне
     * фото: нове завантаження замінює попереднє (старе видаляється разом
     * із файлами через delete_by_entity() перед завантаженням нового).
     */
    public static function upload_profile_avatar($request) {
        $user_id = get_current_user_id();

        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new WP_Error('no_file', __('Файл не надіслано', 'lapki'), ['status' => 400]);
        }

        $file = $files['file'];

        if ($file['size'] > 10 * 1024 * 1024) {
            return new WP_Error('file_too_large', __('Файл занадто великий. Максимум 10MB', 'lapki'), ['status' => 400]);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $user = get_userdata($user_id);
        $result = Lapki_Media::upload_image($file, 'user', $user_id, $user->display_name ?? '', true, 0);

        if (is_wp_error($result)) {
            $server_side_errors = ['move_error', 'db_error'];
            $status = in_array($result->get_error_code(), $server_side_errors, true) ? 500 : 400;
            return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => $status]);
        }

        // Старий аватар (якщо був) видаляємо ПІСЛЯ успішного завантаження нового,
        // щоб користувач не лишився без фото, якщо завантаження раптом не вдасться
        Lapki_Media::delete_by_entity_except('user', $user_id, $result['media_id']);

        $media = Lapki_Media::get($result['media_id']);

        return new WP_REST_Response($media, 201);
    }

    /**
     * DELETE /wp-json/lapki/v1/profile/avatar
     */
    public static function delete_profile_avatar($request) {
        $user_id = get_current_user_id();
        Lapki_Media::delete_by_entity('user', $user_id);

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Унікальний user_login на основі локальної частини email (email@example.com → email, email1, email2…)
     */
    private static function generate_unique_username($email) {
        $base = sanitize_user(current(explode('@', $email)), true);
        if (empty($base)) {
            $base = 'user';
        }

        $username = $base;
        $i = 1;
        while (username_exists($username)) {
            $username = $base . $i;
            $i++;
        }

        return $username;
    }

    /**
     * Надіслати email-нотифікацію організації та підтвердження заявнику
     */
    private static function send_application_emails($application_id, $animal) {
        $application = Lapki_Application::get($application_id);
        if (!$application) {
            return;
        }

        $organization = Lapki_Organization::get($animal['organization_id']);
        $org_email = !empty($organization['email'])
            ? $organization['email']
            : get_option('lapki_notification_email', get_option('admin_email'));

        // Шаблон редагується в Lapki → Email-шаблони (wp_lapki_email);
        // надсилається власнику притулка або приватній особі — обидва
        // випадки вже покриті organization.email (див. Lapki_Organization_Member::ensure_membership())
        $rendered_org = Lapki_Email_Template::render(Lapki_Email_Template::SLUG_APPLICATION_OWNER_NOTIFICATION, [
            'animal_name'        => $animal['name'],
            'applicant_name'     => $application['applicant_name'],
            'applicant_email'    => $application['applicant_email'],
            'applicant_phone'    => $application['applicant_phone'] ?: '—',
            'applicant_message'  => $application['message'] ?: '—',
            'applications_url'   => admin_url('admin.php?page=lapki-organizations'),
        ]);

        if ($rendered_org) {
            wp_mail($org_email, $rendered_org['subject'], $rendered_org['body']);
        } else {
            // Фолбек, якщо шаблон з якоїсь причини відсутній у БД (видалення
            // шаблонів через UI не передбачено, це лише страховка)
            $subject_org = sprintf('Нова заявка на усиновлення: %s', $animal['name']);
            $body_org = sprintf(
                "Отримано нову заявку на усиновлення тварини \"%s\".\n\nІм'я: %s\nEmail: %s\nТелефон: %s\nПовідомлення: %s\n\nПереглянути заявки: %s",
                $animal['name'],
                $application['applicant_name'],
                $application['applicant_email'],
                $application['applicant_phone'] ?: '—',
                $application['message'] ?: '—',
                admin_url('admin.php?page=lapki-organizations')
            );
            wp_mail($org_email, $subject_org, $body_org);
        }

        $subject_applicant = sprintf('Ваша заявка на усиновлення "%s" отримана', $animal['name']);
        $body_applicant = sprintf(
            "Вітаємо, %s!\n\nВашу заявку на усиновлення тварини \"%s\" отримано. Організація зв'яжеться з вами найближчим часом.\n\nДякуємо, що вирішили подарувати дім!",
            $application['applicant_name'],
            $animal['name']
        );
        wp_mail($application['applicant_email'], $subject_applicant, $body_applicant);
    }
}

// Ініціалізація REST API
Lapki_REST_API::init();
?>