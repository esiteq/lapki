<?php
/**
 * Tests for Lapki_Organization::create/get/search
 *
 * @package Lapki
 */

class Test_Lapki_Organization extends WP_UnitTestCase {

	private function make_organization( $overrides = [] ) {
		$data = array_merge( [
			'name'       => 'Test Shelter',
			'wp_user_id' => self::factory()->user->create(),
		], $overrides );

		return Lapki_Organization::create( $data );
	}

	public function test_create_returns_positive_id() {
		$id = $this->make_organization();

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );
	}

	public function test_create_applies_defaults() {
		$id = $this->make_organization();

		$organization = Lapki_Organization::get( $id );

		$this->assertSame( 'individual', $organization['type'] );
		$this->assertSame( 'UA', $organization['country'] );
		$this->assertSame( '0', (string) $organization['is_verified'] );
	}

	public function test_create_rejects_duplicate_wp_user_id() {
		global $wpdb;

		$owner_id = self::factory()->user->create();
		$this->make_organization( [ 'wp_user_id' => $owner_id ] );

		// Дублікат wp_user_id очікувано провалить INSERT через UNIQUE KEY —
		// приховуємо друк помилки БД, вона тут навмисна, а не збій тесту.
		$wpdb->suppress_errors( true );
		$second_id = $this->make_organization( [ 'name' => 'Other Shelter', 'wp_user_id' => $owner_id ] );
		$wpdb->suppress_errors( false );

		$this->assertFalse( $second_id );
	}

	public function test_get_returns_organization_with_animals_count() {
		$id = $this->make_organization();

		Lapki_Animal::create( [
			'organization_id' => $id,
			'name'            => 'Барсик',
			'type'            => 'cat',
			'species'         => 'cat',
			'age'             => 'young',
			'gender'          => 'male',
			'size'            => 'medium',
			'status'          => 'adoptable',
		] );
		Lapki_Animal::create( [
			'organization_id' => $id,
			'name'            => 'Рекс',
			'type'            => 'dog',
			'species'         => 'dog',
			'age'             => 'adult',
			'gender'          => 'male',
			'size'            => 'large',
			'status'          => 'adopted',
		] );

		$organization = Lapki_Organization::get( $id );

		$this->assertSame( 'Test Shelter', $organization['name'] );
		$this->assertSame( '1', (string) $organization['animals_count'] );
		$this->assertIsArray( $organization['media'] );
	}

	public function test_get_returns_null_for_nonexistent_id() {
		$this->assertNull( Lapki_Organization::get( 999999999 ) );
	}

	public function test_update_persists_changes() {
		$id = $this->make_organization();

		$updated = Lapki_Organization::update( $id, [ 'name' => 'Renamed Shelter', 'is_verified' => 1 ] );

		$this->assertTrue( $updated );

		$organization = Lapki_Organization::get( $id );
		$this->assertSame( 'Renamed Shelter', $organization['name'] );
		$this->assertSame( '1', (string) $organization['is_verified'] );
	}

	public function test_delete_removes_organization() {
		$id = $this->make_organization();

		$this->assertTrue( Lapki_Organization::delete( $id ) );
		$this->assertNull( Lapki_Organization::get( $id ) );
	}

	public function test_search_filters_by_name() {
		$this->make_organization( [ 'name' => 'Kyiv Shelter' ] );
		$this->make_organization( [ 'name' => 'Lviv Shelter' ] );

		$results = Lapki_Organization::search( [ 'name' => 'Kyiv' ] );

		$this->assertCount( 1, $results );
		$this->assertSame( 'Kyiv Shelter', $results[0]['name'] );
	}

	public function test_search_filters_by_type() {
		$this->make_organization( [ 'name' => 'Shelter A', 'type' => 'shelter' ] );
		$this->make_organization( [ 'name' => 'Vet A', 'type' => 'vet_clinic' ] );

		$results = Lapki_Organization::search( [ 'type' => 'vet_clinic' ] );

		$this->assertCount( 1, $results );
		$this->assertSame( 'Vet A', $results[0]['name'] );
	}

	public function test_search_verified_only_excludes_unverified() {
		$this->make_organization( [ 'name' => 'Verified Test Shelter', 'is_verified' => 1 ] );
		$this->make_organization( [ 'name' => 'Unverified Test Shelter', 'is_verified' => 0 ] );

		// Обмежуємось власними записами: seed-дані плагіна (id=1, "Демо притулок Lapki")
		// теж мають is_verified=1 і псують точний підрахунок без фільтра за іменем.
		$results = Lapki_Organization::search( [ 'verified_only' => true, 'name' => 'Test Shelter' ] );

		$this->assertCount( 1, $results );
		$this->assertSame( 'Verified Test Shelter', $results[0]['name'] );
	}

	public function test_get_by_wp_user_id_returns_owned_organizations() {
		$owner_id = self::factory()->user->create();
		$id       = $this->make_organization( [ 'wp_user_id' => $owner_id ] );

		$results = Lapki_Organization::get_by_wp_user_id( $owner_id );

		$this->assertCount( 1, $results );
		$this->assertSame( (string) $id, (string) $results[0]['id'] );
	}

	public function test_belongs_to_user_true_for_owner() {
		$owner_id = self::factory()->user->create();
		$id       = $this->make_organization( [ 'wp_user_id' => $owner_id ] );

		$this->assertTrue( Lapki_Organization::belongs_to_user( $id, $owner_id ) );
	}

	public function test_belongs_to_user_false_for_non_owner() {
		$owner_id     = self::factory()->user->create();
		$non_owner_id = self::factory()->user->create();
		$id           = $this->make_organization( [ 'wp_user_id' => $owner_id ] );

		$this->assertFalse( Lapki_Organization::belongs_to_user( $id, $non_owner_id ) );
	}
}
