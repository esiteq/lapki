<?php
/**
 * Tests for REST API authorization (animals & organizations write endpoints)
 *
 * @package Lapki
 */

class Test_Lapki_Rest_Api extends WP_Test_REST_TestCase {

	/** @var WP_REST_Server */
	protected $server;

	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init', $this->server );
	}

	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tear_down();
	}

	private function dispatch_json( $method, $route, $body = null, $user_id = 0 ) {
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( $method, $route );
		if ( null !== $body ) {
			$request->add_header( 'content-type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );
		}

		return $this->server->dispatch( $request );
	}

	private function make_organization( $wp_user_id ) {
		return Lapki_Organization::create( [
			'name'       => 'Test Shelter',
			'wp_user_id' => $wp_user_id,
		] );
	}

	private function make_animal( $organization_id, $overrides = [] ) {
		$data = array_merge( [
			'organization_id' => $organization_id,
			'name'            => 'Барсик',
			'type'            => 'cat',
			'species'         => 'cat',
			'age'             => 'young',
			'gender'          => 'male',
			'size'            => 'medium',
		], $overrides );

		return Lapki_Animal::create( $data );
	}

	// -----------------------------------------------------------------
	// Animals
	// -----------------------------------------------------------------

	public function test_get_animals_is_public_for_anonymous() {
		$response = $this->dispatch_json( 'GET', '/lapki/v1/animals' );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_create_animal_requires_login() {
		$owner_id        = self::factory()->user->create();
		$organization_id = $this->make_organization( $owner_id );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/animals', [
			'organization_id' => $organization_id,
			'name'            => 'Барсик',
			'type'            => 'cat',
			'age'             => 'young',
			'gender'          => 'male',
			'size'            => 'medium',
		], 0 );

		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );
	}

	public function test_create_animal_forbidden_without_capability() {
		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/animals', [
			'organization_id' => 1,
			'name'            => 'Барсик',
			'type'            => 'cat',
			'age'             => 'young',
			'gender'          => 'male',
			'size'            => 'medium',
		], $subscriber_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_create_animal_allowed_for_shelter_admin() {
		$shelter_admin_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id  = $this->make_organization( $shelter_admin_id );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/animals', [
			'organization_id' => $organization_id,
			'name'            => 'Барсик',
			'type'            => 'cat',
			'age'             => 'young',
			'gender'          => 'male',
			'size'            => 'medium',
		], $shelter_admin_id );

		$this->assertSame( 201, $response->get_status() );
	}

	public function test_update_animal_forbidden_for_non_owner() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );

		$other_shelter_admin_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );

		$response = $this->dispatch_json( 'PUT', "/lapki/v1/animals/{$animal_id}", [
			'name' => 'Змінено',
		], $other_shelter_admin_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_update_animal_allowed_for_owner() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );

		$response = $this->dispatch_json( 'PUT', "/lapki/v1/animals/{$animal_id}", [
			'name' => 'Змінено',
		], $owner_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Змінено', Lapki_Animal::get( $animal_id )['name'] );
	}

	public function test_update_animal_allowed_for_site_admin_regardless_of_ownership() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );

		$site_admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		$response = $this->dispatch_json( 'PUT', "/lapki/v1/animals/{$animal_id}", [
			'name' => 'Змінено адміном',
		], $site_admin_id );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_delete_animal_requires_login() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );

		$response = $this->dispatch_json( 'DELETE', "/lapki/v1/animals/{$animal_id}", null, 0 );

		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );
		$this->assertNotNull( Lapki_Animal::get( $animal_id ) );
	}

	// -----------------------------------------------------------------
	// Organizations
	// -----------------------------------------------------------------

	public function test_get_organizations_is_public_for_anonymous() {
		$response = $this->dispatch_json( 'GET', '/lapki/v1/organizations' );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_create_organization_forbidden_without_capability() {
		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/organizations', [
			'name' => 'Test Shelter',
			'type' => 'shelter',
		], $subscriber_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_create_organization_allowed_for_shelter_admin() {
		$shelter_admin_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/organizations', [
			'name'       => 'Test Shelter',
			'type'       => 'shelter',
			'wp_user_id' => $shelter_admin_id,
		], $shelter_admin_id );

		$this->assertSame( 201, $response->get_status() );
	}

	public function test_create_organization_defaults_wp_user_id_to_current_user() {
		$shelter_admin_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/organizations', [
			'name' => 'Test Shelter Without Explicit Owner',
			'type' => 'shelter',
		], $shelter_admin_id );

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( $shelter_admin_id, (int) $response->get_data()['wp_user_id'] );
	}

	public function test_update_organization_forbidden_for_non_owner() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );

		$other_shelter_admin_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );

		$response = $this->dispatch_json( 'PUT', "/lapki/v1/organizations/{$organization_id}", [
			'name' => 'Змінено',
		], $other_shelter_admin_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_update_organization_allowed_for_owner() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );

		$response = $this->dispatch_json( 'PUT', "/lapki/v1/organizations/{$organization_id}", [
			'name' => 'Змінено',
		], $owner_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Змінено', Lapki_Organization::get( $organization_id )['name'] );
	}

	public function test_volunteer_cannot_manage_organizations() {
		$volunteer_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_VOLUNTEER ] );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/organizations', [
			'name' => 'Test Shelter',
			'type' => 'shelter',
		], $volunteer_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	// -----------------------------------------------------------------
	// Media
	// -----------------------------------------------------------------

	private function make_media( $animal_id, $overrides = [] ) {
		$data = array_merge( [
			'entity_type' => 'animal',
			'entity_id'   => $animal_id,
			'media_type'  => 'photo',
			'filename'    => 'cat.jpg',
			'file_path'   => 'cat-' . wp_generate_password( 8, false ) . '.jpg',
		], $overrides );

		return Lapki_Media::create( $data );
	}

	public function test_upload_media_forbidden_without_capability() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );

		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$response = $this->dispatch_json( 'POST', "/lapki/v1/animals/{$animal_id}/media", null, $subscriber_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_delete_media_forbidden_for_non_owner() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );
		$media_id        = $this->make_media( $animal_id );

		$other_shelter_admin_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );

		$response = $this->dispatch_json( 'DELETE', "/lapki/v1/media/{$media_id}", null, $other_shelter_admin_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
		$this->assertNotNull( Lapki_Media::get( $media_id ) );
	}

	public function test_delete_media_allowed_for_owner() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );
		$media_id        = $this->make_media( $animal_id );

		$response = $this->dispatch_json( 'DELETE', "/lapki/v1/media/{$media_id}", null, $owner_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNull( Lapki_Media::get( $media_id ) );
	}

	public function test_set_primary_media_allowed_for_owner() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );
		$this->make_media( $animal_id, [ 'is_primary' => 1 ] );
		$second_id = $this->make_media( $animal_id, [ 'is_primary' => 0 ] );

		$response = $this->dispatch_json( 'PUT', "/lapki/v1/media/{$second_id}/primary", null, $owner_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( Lapki_Media::get( $second_id )['is_primary'] );
	}

	public function test_set_primary_media_forbidden_for_non_owner() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );
		$media_id        = $this->make_media( $animal_id );

		$other_shelter_admin_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );

		$response = $this->dispatch_json( 'PUT', "/lapki/v1/media/{$media_id}/primary", null, $other_shelter_admin_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	// -----------------------------------------------------------------
	// Attributes (global dictionary — admin only)
	// -----------------------------------------------------------------

	public function test_get_attributes_is_public_for_anonymous() {
		$response = $this->dispatch_json( 'GET', '/lapki/v1/attributes' );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_create_attribute_forbidden_for_shelter_admin() {
		$shelter_admin_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/attributes', [
			'entity'       => 'animal',
			'entity_type'  => 'dog',
			'attr_name'    => 'breed',
			'attr_value'   => 'husky-' . wp_generate_password( 8, false ),
			'attr_display' => 'Хаскі',
			'lang'         => 'uk',
		], $shelter_admin_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_create_attribute_allowed_for_site_admin() {
		$site_admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/attributes', [
			'entity'       => 'animal',
			'entity_type'  => 'dog',
			'attr_name'    => 'breed',
			'attr_value'   => 'husky-' . wp_generate_password( 8, false ),
			'attr_display' => 'Хаскі',
			'lang'         => 'uk',
		], $site_admin_id );

		$this->assertSame( 201, $response->get_status() );
	}

	public function test_update_attribute_forbidden_for_shelter_admin() {
		$site_admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$id            = Lapki_Attributes::create( [
			'entity'       => 'animal',
			'entity_type'  => 'dog',
			'attr_name'    => 'breed',
			'attr_value'   => 'husky-' . wp_generate_password( 8, false ),
			'attr_display' => 'Хаскі',
			'lang'         => 'uk',
		] );

		$shelter_admin_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );

		$response = $this->dispatch_json( 'PUT', "/lapki/v1/attributes/{$id}", [
			'attr_display' => 'Змінено',
		], $shelter_admin_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_delete_attribute_allowed_for_site_admin() {
		$site_admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$id            = Lapki_Attributes::create( [
			'entity'       => 'animal',
			'entity_type'  => 'dog',
			'attr_name'    => 'breed',
			'attr_value'   => 'husky-' . wp_generate_password( 8, false ),
			'attr_display' => 'Хаскі',
			'lang'         => 'uk',
		] );

		$response = $this->dispatch_json( 'DELETE', "/lapki/v1/attributes/{$id}", null, $site_admin_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNull( Lapki_Attributes::get( $id ) );
	}

	// -----------------------------------------------------------------
	// Applications (adoption requests)
	// -----------------------------------------------------------------

	private function make_application( $animal_id, $organization_id, $overrides = [] ) {
		$data = array_merge( [
			'animal_id'        => $animal_id,
			'organization_id'  => $organization_id,
			'applicant_name'   => 'Іван Іванов',
			'applicant_email'  => 'ivan@example.com',
		], $overrides );

		return Lapki_Application::create( $data );
	}

	public function test_create_application_is_public_for_anonymous() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/applications', [
			'animal_id'       => $animal_id,
			'applicant_name'  => 'Іван Іванов',
			'applicant_email' => 'ivan@example.com',
		], 0 );

		$this->assertSame( 201, $response->get_status() );
	}

	public function test_create_application_rejects_invalid_email() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );

		$response = $this->dispatch_json( 'POST', '/lapki/v1/applications', [
			'animal_id'       => $animal_id,
			'applicant_name'  => 'Іван Іванов',
			'applicant_email' => 'not-an-email',
		], 0 );

		$this->assertErrorResponse( 'invalid_data', $response, 400 );
	}

	public function test_get_applications_requires_login() {
		$response = $this->dispatch_json( 'GET', '/lapki/v1/applications', null, 0 );

		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );
	}

	public function test_get_applications_scoped_to_owner_organization() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );
		$this->make_application( $animal_id, $organization_id );

		$other_owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$other_organization_id = $this->make_organization( $other_owner_id );
		$other_animal_id       = $this->make_animal( $other_organization_id );
		$this->make_application( $other_animal_id, $other_organization_id );

		$response = $this->dispatch_json( 'GET', '/lapki/v1/applications', null, $owner_id );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		foreach ( $data['data'] as $application ) {
			$this->assertSame( $organization_id, (int) $application['organization_id'] );
		}
	}

	public function test_update_application_forbidden_for_non_owner() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );
		$application_id  = $this->make_application( $animal_id, $organization_id );

		$other_shelter_admin_id = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );

		$response = $this->dispatch_json( 'PUT', "/lapki/v1/applications/{$application_id}", [
			'status' => Lapki_Application::STATUS_CONTACTED,
		], $other_shelter_admin_id );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_update_application_allowed_for_owner() {
		$owner_id        = self::factory()->user->create( [ 'role' => Lapki_Roles::ROLE_SHELTER_ADMIN ] );
		$organization_id = $this->make_organization( $owner_id );
		$animal_id       = $this->make_animal( $organization_id );
		$application_id  = $this->make_application( $animal_id, $organization_id );

		$response = $this->dispatch_json( 'PUT', "/lapki/v1/applications/{$application_id}", [
			'status' => Lapki_Application::STATUS_CONTACTED,
		], $owner_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( Lapki_Application::STATUS_CONTACTED, Lapki_Application::get( $application_id )['status'] );
	}
}
