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
                'permission_callback' => [__CLASS__, 'check_manage_animals_permission']
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
        // притулку/ГО — окремі кроки; join — приєднатись до вже існуючої
        // організації, leave — вийти зі своєї)
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

        register_rest_route($namespace, '/organizations/leave', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'leave_organization'],
            'permission_callback' => [__CLASS__, 'check_is_logged_in']
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

        register_rest_route($namespace, '/applications/(?P<id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [__CLASS__, 'update_application'],
            'permission_callback' => [__CLASS__, 'check_application_owner_permission'],
        ]);

        // SIGNUP ROUTE (публічна реєстрація нового користувача)
        register_rest_route($namespace, '/signup', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'signup_user'],
            'permission_callback' => '__return_true',
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
     * POST /wp-json/lapki/v1/animals
     */
    public static function create_animal($request) {
        $data = $request->get_json_params();

        // Базова валідація
        $required_fields = ['organization_id', 'name', 'type', 'age', 'gender', 'size'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__("Поле '%s' є обов'язковим", 'lapki'), $field), ['status' => 400]);
            }
        }

        // Автоматично заповнити species з type (це одне і те ж)
        if (empty($data['species'])) {
            $data['species'] = $data['type'];
        }

        // Для тестування - додамо дефолтну організацію якщо не вказана
        if (empty($data['organization_id'])) {
            $data['organization_id'] = 1;
        }

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
        
        return new WP_REST_Response(['message' => 'Тварину успішно видалено'], 200);
    }
    
    // =======================================
    // ANIMAL TYPES ENDPOINTS
    // =======================================
    
    /**
     * GET /wp-json/lapki/v1/types/all
     */
    public static function get_all_type_attributes($request) {
        $lang = $request->get_param('lang') ?: 'uk';
        $attributes = Lapki_Attributes::get_global_attributes($lang);

        return new WP_REST_Response([
            'attributes' => $attributes
        ], 200);
    }

    /**
     * GET /wp-json/lapki/v1/types
     */
    public static function get_animal_types($request) {
        $lang = $request->get_param('lang') ?: 'uk';
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
        $lang = $request->get_param('lang') ?: 'uk';
        
        $attributes = Lapki_Attributes::get_type_attributes($type, $lang);
        
        if (empty($attributes)) {
            return new WP_Error('type_not_found', __('Тип тварини не знайдено', 'lapki'), ['status' => 404]);
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
        $lang = $request->get_param('lang') ?: 'uk';
        
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
     * Самостійна реєстрація організації залогіненим користувачем — робить
     * його власником (роль 'owner' у членстві + WP-роль lapki_shelter_admin).
     * Користувач, який вже прив'язаний до будь-якої організації, спершу має
     * вийти з неї (POST /organizations/leave).
     */
    public static function create_organization($request) {
        $data = $request->get_json_params();
        $wp_user_id = get_current_user_id();

        // Базова валідація
        $required_fields = ['name', 'type'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__("Поле '%s' є обов'язковим", 'lapki'), $field), ['status' => 400]);
            }
        }

        if (!current_user_can('manage_options') && Lapki_Organization_Member::get_by_user($wp_user_id)) {
            return new WP_Error(
                'already_member',
                __('Ви вже прив\'язані до організації. Спершу вийдіть з неї.', 'lapki'),
                ['status' => 409]
            );
        }

        $data['wp_user_id'] = $wp_user_id;

        $org_id = Lapki_Organization::create($data);

        if (!$org_id) {
            return new WP_Error('creation_failed', __('Не вдалося створити організацію', 'lapki'), ['status' => 500]);
        }

        Lapki_Organization_Member::join($org_id, $wp_user_id, Lapki_Organization_Member::ROLE_OWNER);

        $user = get_userdata($wp_user_id);
        if ($user && !in_array('administrator', $user->roles, true)) {
            $user->set_role(Lapki_Roles::ROLE_SHELTER_ADMIN);
        }

        $organization = Lapki_Organization::get($org_id);
        return new WP_REST_Response($organization, 201);
    }

    /**
     * POST /wp-json/lapki/v1/organizations/{id}/join
     * Приєднатись до вже існуючої організації як учасник ('member').
     */
    public static function join_organization($request) {
        $organization_id = $request->get_param('id');
        $wp_user_id = get_current_user_id();

        $organization = Lapki_Organization::get($organization_id);
        if (!$organization) {
            return new WP_Error('organization_not_found', __('Організацію не знайдено', 'lapki'), ['status' => 404]);
        }

        if (Lapki_Organization_Member::get_by_user($wp_user_id)) {
            return new WP_Error(
                'already_member',
                __('Ви вже прив\'язані до організації. Спершу вийдіть з неї.', 'lapki'),
                ['status' => 409]
            );
        }

        $joined = Lapki_Organization_Member::join($organization_id, $wp_user_id, Lapki_Organization_Member::ROLE_MEMBER);
        if (!$joined) {
            return new WP_Error('join_failed', __('Не вдалося приєднатись до організації', 'lapki'), ['status' => 500]);
        }

        $user = get_userdata($wp_user_id);
        if ($user && !in_array('administrator', $user->roles, true)) {
            $user->set_role(Lapki_Roles::ROLE_VOLUNTEER);
        }

        return new WP_REST_Response(Lapki_Organization_Member::get_by_user($wp_user_id), 200);
    }

    /**
     * POST /wp-json/lapki/v1/organizations/leave
     * Вийти зі своєї організації. Власник вийти так не може — організація
     * лишилась би без власника; спершу має передати право власності
     * (POST /organizations/{id}/transfer).
     */
    public static function leave_organization($request) {
        $wp_user_id = get_current_user_id();
        $membership = Lapki_Organization_Member::get_by_user($wp_user_id);

        if (!$membership) {
            return new WP_Error('not_a_member', __('Ви не прив\'язані до жодної організації', 'lapki'), ['status' => 404]);
        }

        if ($membership['role'] === Lapki_Organization_Member::ROLE_OWNER) {
            return new WP_Error(
                'owner_cannot_leave',
                __('Власник не може вийти з організації. Спершу передайте право власності іншому учаснику.', 'lapki'),
                ['status' => 403]
            );
        }

        Lapki_Organization_Member::leave($wp_user_id);

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

        return new WP_REST_Response(['message' => 'Організацію успішно видалено'], 200);
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

        return false;
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
                return new WP_Error('missing_field', sprintf(__("Поле '%s' обов'язкове", 'lapki'), $key), ['status' => 400]);
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
            $orgs = Lapki_Organization::get_by_wp_user_id(get_current_user_id());
            if (empty($orgs)) {
                return new WP_REST_Response(['data' => []], 200);
            }
            $organization_id = (int) $orgs[0]['id'];
        }

        if (empty($organization_id)) {
            return new WP_Error('missing_param', __("Параметр 'organization_id' обов'язковий", 'lapki'), ['status' => 400]);
        }

        $applications = Lapki_Application::get_by_organization($organization_id, $request->get_param('status') ?: '');

        return new WP_REST_Response(['data' => $applications], 200);
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

        $applicant_name = sanitize_text_field($request->get_param('applicant_name'));
        $applicant_email = sanitize_email($request->get_param('applicant_email'));

        if (empty($applicant_name) || empty($applicant_email) || !is_email($applicant_email)) {
            return new WP_Error('invalid_data', "Вкажіть ім'я та коректний email", ['status' => 400]);
        }

        $application_id = Lapki_Application::create([
            'animal_id' => $animal_id,
            'organization_id' => $animal['organization_id'],
            'applicant_name' => $applicant_name,
            'applicant_email' => $applicant_email,
            'applicant_phone' => sanitize_text_field($request->get_param('applicant_phone')),
            'message' => sanitize_textarea_field($request->get_param('message')),
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
        $name = sanitize_text_field((string) $request->get_param('name'));
        $email = sanitize_email((string) $request->get_param('email'));
        $password = (string) $request->get_param('password');
        $phone = sanitize_text_field((string) $request->get_param('phone'));

        if (empty($name) || empty($email) || !is_email($email) || strlen($password) < 6) {
            return new WP_Error('invalid_data', __("Заповніть ім'я, коректний email і пароль (мінімум 6 символів)", 'lapki'), ['status' => 400]);
        }

        if (email_exists($email)) {
            return new WP_Error('email_exists', __('Користувач з таким email вже зареєстрований', 'lapki'), ['status' => 409]);
        }

        $user_id = wp_insert_user([
            'user_login' => self::generate_unique_username($email),
            'user_email' => $email,
            'user_pass' => $password,
            'display_name' => $name,
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