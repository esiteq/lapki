<?php
/**
 * Tests for Lapki_Animal::create/get/search
 *
 * @package Lapki
 */

class Test_Lapki_Animal extends WP_UnitTestCase {

	private $organization_id;

	public function set_up() {
		parent::set_up();
		$this->organization_id = Lapki_Organization::create( [
			'name'       => 'Test Shelter',
			'wp_user_id' => self::factory()->user->create(),
		] );
	}

	private function make_animal( $overrides = [] ) {
		$data = array_merge( [
			'organization_id' => $this->organization_id,
			'name'            => 'Барсик',
			'type'            => 'cat',
			'species'         => 'cat',
			'age'             => 'young',
			'gender'          => 'male',
			'size'            => 'medium',
		], $overrides );

		return Lapki_Animal::create( $data );
	}

	public function test_create_returns_positive_id() {
		$id = $this->make_animal();

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );
	}

	public function test_create_applies_defaults() {
		$id = $this->make_animal();

		$animal = Lapki_Animal::get( $id );

		$this->assertSame( 'adoptable', $animal['status'] );
		$this->assertSame( 'UA', $animal['address_country'] );
		$this->assertNotEmpty( $animal['published_at'] );
	}

	public function test_create_respects_explicit_overrides() {
		$id = $this->make_animal( [ 'status' => 'hold', 'name' => 'Рекс' ] );

		$animal = Lapki_Animal::get( $id );

		$this->assertSame( 'hold', $animal['status'] );
		$this->assertSame( 'Рекс', $animal['name'] );
	}

	public function test_get_returns_animal_with_organization_name() {
		$id = $this->make_animal();

		$animal = Lapki_Animal::get( $id );

		$this->assertSame( (string) $id, (string) $animal['id'] );
		$this->assertSame( 'Test Shelter', $animal['organization_name'] );
		$this->assertIsArray( $animal['media'] );
		$this->assertCount( 0, $animal['media'] );
		$this->assertIsArray( $animal['tags'] );
		$this->assertCount( 0, $animal['tags'] );
	}

	public function test_get_returns_null_for_nonexistent_id() {
		$animal = Lapki_Animal::get( 999999999 );

		$this->assertNull( $animal );
	}

	public function test_search_filters_by_type() {
		$this->make_animal( [ 'type' => 'dog', 'species' => 'dog', 'name' => 'Рекс' ] );
		$this->make_animal( [ 'type' => 'cat', 'species' => 'cat', 'name' => 'Мурка' ] );

		$results = Lapki_Animal::search( [ 'type' => 'dog' ] );

		$this->assertCount( 1, $results );
		$this->assertSame( 'Рекс', $results[0]['name'] );
	}

	public function test_search_without_status_returns_all_statuses() {
		$this->make_animal( [ 'status' => 'adoptable', 'name' => 'Барсик' ] );
		$this->make_animal( [ 'status' => 'adopted', 'name' => 'Мурзик' ] );

		$results = Lapki_Animal::search( [ 'organization_id' => $this->organization_id ] );

		$this->assertCount( 2, $results );
	}

	public function test_search_filters_by_status() {
		$this->make_animal( [ 'status' => 'adoptable', 'name' => 'Барсик' ] );
		$this->make_animal( [ 'status' => 'adopted', 'name' => 'Мурзик' ] );

		$results = Lapki_Animal::search( [ 'status' => 'adopted' ] );

		$this->assertCount( 1, $results );
		$this->assertSame( 'Мурзик', $results[0]['name'] );
	}

	public function test_search_by_name_matches_partial() {
		$this->make_animal( [ 'name' => 'Барсик' ] );
		$this->make_animal( [ 'name' => 'Мурзик' ] );

		$results = Lapki_Animal::search( [ 'search' => 'Бар' ] );

		$this->assertCount( 1, $results );
		$this->assertSame( 'Барсик', $results[0]['name'] );
	}

	public function test_search_filters_by_boolean_attribute() {
		$this->make_animal( [ 'name' => 'Барсик', 'good_with_children' => 1 ] );
		$this->make_animal( [ 'name' => 'Мурзик', 'good_with_children' => 0 ] );

		$results = Lapki_Animal::search( [ 'good_with_children' => true ] );

		$this->assertCount( 1, $results );
		$this->assertSame( 'Барсик', $results[0]['name'] );
	}

	public function test_search_filters_by_organization_id() {
		$other_org_id = Lapki_Organization::create( [
			'name'       => 'Other Shelter',
			'wp_user_id' => self::factory()->user->create(),
		] );

		$this->make_animal( [ 'name' => 'Барсик' ] );
		$this->make_animal( [ 'name' => 'Мурзик', 'organization_id' => $other_org_id ] );

		$results = Lapki_Animal::search( [ 'organization_id' => $this->organization_id ] );

		$this->assertCount( 1, $results );
		$this->assertSame( 'Барсик', $results[0]['name'] );
	}

	public function test_search_respects_limit_and_offset() {
		$this->make_animal( [ 'name' => 'Тварина 1', 'published_at' => '2026-01-01 10:00:00' ] );
		$this->make_animal( [ 'name' => 'Тварина 2', 'published_at' => '2026-01-02 10:00:00' ] );
		$this->make_animal( [ 'name' => 'Тварина 3', 'published_at' => '2026-01-03 10:00:00' ] );

		$page = Lapki_Animal::search( [
			'organization_id' => $this->organization_id,
			'limit'           => 2,
			'offset'          => 1,
			'order_by'        => 'published_at',
			'order'           => 'DESC',
		] );

		$this->assertCount( 2, $page );
		$this->assertSame( 'Тварина 2', $page[0]['name'] );
		$this->assertSame( 'Тварина 1', $page[1]['name'] );
	}

	public function test_search_includes_primary_photo_key() {
		$id = $this->make_animal();

		$results = Lapki_Animal::search( [ 'organization_id' => $this->organization_id ] );

		$this->assertArrayHasKey( 'primary_photo', $results[0] );
		$this->assertNull( $results[0]['primary_photo'] );
	}
}
