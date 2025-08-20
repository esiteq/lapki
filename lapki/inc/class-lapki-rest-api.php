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
                'permission_callback' => '__return_true' // Відключено для тестування
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
                'permission_callback' => '__return_true' // Відключено для тестування
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [__CLASS__, 'delete_animal'],
                'permission_callback' => '__return_true' // Відключено для тестування
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
                'permission_callback' => '__return_true' // Відключено для тестування
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
            ]
        ]);
        
        // STATISTICS ROUTE
        register_rest_route($namespace, '/stats', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_stats'],
            'permission_callback' => '__return_true'
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
            'status' => $request->get_param('status') ?: 'adoptable',
            'location' => $request->get_param('location'),
            'distance' => $request->get_param('distance') ?: 50,
            'latitude' => $request->get_param('latitude'),
            'longitude' => $request->get_param('longitude'),
            'good_with_children' => self::parse_boolean($request->get_param('good_with_children')),
            'good_with_dogs' => self::parse_boolean($request->get_param('good_with_dogs')),
            'good_with_cats' => self::parse_boolean($request->get_param('good_with_cats')),
            'spayed_neutered' => self::parse_boolean($request->get_param('spayed_neutered')),
            'special_needs' => self::parse_boolean($request->get_param('special_needs')),
            'organization_id' => $request->get_param('organization_id'),
            'limit' => min($request->get_param('limit') ?: 20, 100), // Максимум 100
            'offset' => $request->get_param('offset') ?: 0,
            'order_by' => $request->get_param('order_by') ?: 'published_at',
            'order' => $request->get_param('order') ?: 'DESC'
        ];
        
        $animals = Lapki_Animal::search($params);
        
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
        
        // Підрахунок загальної кількості для пагінації
        $total_params = $params;
        $total_params['limit'] = 999999;
        $total_params['offset'] = 0;
        $total_count = count(Lapki_Animal::search($total_params));
        
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
            return new WP_Error('animal_not_found', 'Тварину не знайдено', ['status' => 404]);
        }
        
        return new WP_REST_Response($animal, 200);
    }
    
    /**
     * POST /wp-json/lapki/v1/animals
     */
    public static function create_animal($request) {
        $data = $request->get_json_params();
        
        // Базова валідація
        $required_fields = ['organization_id', 'name', 'type', 'species', 'age', 'gender', 'size'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', "Поле '{$field}' є обов'язковим", ['status' => 400]);
            }
        }
        
        // Для тестування - додамо дефолтну організацію якщо не вказана
        if (empty($data['organization_id'])) {
            $data['organization_id'] = 1;
        }
        
        $animal_id = Lapki_Animal::create($data);
        
        if (!$animal_id) {
            return new WP_Error('creation_failed', 'Не вдалося створити тварину', ['status' => 500]);
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
            return new WP_Error('animal_not_found', 'Тварину не знайдено', ['status' => 404]);
        }
        
        $updated = Lapki_Animal::update($id, $data);
        
        if ($updated === false) {
            return new WP_Error('update_failed', 'Не вдалося оновити тварину', ['status' => 500]);
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
            return new WP_Error('animal_not_found', 'Тварину не знайдено', ['status' => 404]);
        }
        
        $deleted = Lapki_Animal::delete($id);
        
        if (!$deleted) {
            return new WP_Error('delete_failed', 'Не вдалося видалити тварину', ['status' => 500]);
        }
        
        return new WP_REST_Response(['message' => 'Тварину успішно видалено'], 200);
    }
    
    // =======================================
    // ANIMAL TYPES ENDPOINTS
    // =======================================
    
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
            return new WP_Error('type_not_found', 'Тип тварини не знайдено', ['status' => 404]);
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
            return new WP_Error('organization_not_found', 'Організацію не знайдено', ['status' => 404]);
        }
        
        return new WP_REST_Response($organization, 200);
    }
    
    /**
     * POST /wp-json/lapki/v1/organizations
     */
    public static function create_organization($request) {
        $data = $request->get_json_params();
        
        // Базова валідація
        $required_fields = ['name', 'type'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', "Поле '{$field}' є обов'язковим", ['status' => 400]);
            }
        }
        
        // Для тестування - додамо дефолтного користувача
        if (empty($data['wp_user_id'])) {
            $data['wp_user_id'] = 1;
        }
        
        $org_id = Lapki_Organization::create($data);
        
        if (!$org_id) {
            return new WP_Error('creation_failed', 'Не вдалося створити організацію', ['status' => 500]);
        }
        
        $organization = Lapki_Organization::get($org_id);
        return new WP_REST_Response($organization, 201);
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
                'enum' => ['individual', 'shelter', 'rescue', 'vet_clinic'],
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
    // =======================================
    
    public static function check_create_permission($request) {
        return current_user_can('edit_posts');
    }
    
    public static function check_edit_permission($request) {
        $id = $request->get_param('id');
        
        // Перевіряємо чи користувач може редагувати цю тварину/організацію
        if (current_user_can('edit_others_posts')) {
            return true;
        }
        
        // Тут можна додати логіку перевірки власності
        return current_user_can('edit_posts');
    }
    
    public static function check_delete_permission($request) {
        return current_user_can('delete_posts');
    }
}

// Ініціалізація REST API
Lapki_REST_API::init();
?>