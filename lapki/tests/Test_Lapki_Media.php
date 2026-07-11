<?php
/**
 * Tests for Lapki_Media::create/get/get_by_entity/set_primary/delete
 *
 * @package Lapki
 */

class Test_Lapki_Media extends WP_UnitTestCase {

	private $organization_id;
	private $animal_id;

	public function set_up() {
		parent::set_up();

		$this->organization_id = Lapki_Organization::create( [
			'name'       => 'Test Shelter',
			'wp_user_id' => self::factory()->user->create(),
		] );

		$this->animal_id = Lapki_Animal::create( [
			'organization_id' => $this->organization_id,
			'name'            => 'Барсик',
			'type'            => 'cat',
			'species'         => 'cat',
			'age'             => 'young',
			'gender'          => 'male',
			'size'            => 'medium',
		] );
	}

	private function make_photo( $overrides = [] ) {
		$data = array_merge( [
			'entity_type' => 'animal',
			'entity_id'   => $this->animal_id,
			'media_type'  => 'photo',
			'filename'    => 'cat.jpg',
			'file_path'   => 'cat-' . wp_generate_password( 8, false ) . '.jpg',
		], $overrides );

		return Lapki_Media::create( $data );
	}

	public function test_create_returns_positive_id() {
		$id = $this->make_photo();

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );
	}

	public function test_create_requires_entity_type_entity_id_and_media_type() {
		$this->assertFalse( Lapki_Media::create( [ 'entity_id' => $this->animal_id, 'media_type' => 'photo' ] ) );
		$this->assertFalse( Lapki_Media::create( [ 'entity_type' => 'animal', 'media_type' => 'photo' ] ) );
		$this->assertFalse( Lapki_Media::create( [ 'entity_type' => 'animal', 'entity_id' => $this->animal_id ] ) );
	}

	public function test_create_applies_defaults() {
		$id = $this->make_photo();

		$media = Lapki_Media::get( $id );

		$this->assertFalse( $media['is_primary'] );
		$this->assertSame( '1', (string) $media['is_active'] );
		$this->assertSame( '0', (string) $media['sort_order'] );
	}

	public function test_get_adds_url_and_thumbnail_url() {
		$id = $this->make_photo( [ 'file_path' => 'barsik.jpg' ] );

		$media = Lapki_Media::get( $id );

		$this->assertStringEndsWith( 'barsik.jpg', $media['url'] );
		$this->assertStringContainsString( 'thumbnails', $media['thumbnail_url'] );
		$this->assertIsBool( $media['is_primary'] );
	}

	public function test_get_returns_null_for_nonexistent_id() {
		$this->assertNull( Lapki_Media::get( 999999999 ) );
	}

	public function test_get_by_entity_excludes_inactive_media() {
		$this->make_photo( [ 'file_path' => 'active.jpg', 'is_active' => 1 ] );
		$this->make_photo( [ 'file_path' => 'inactive.jpg', 'is_active' => 0 ] );

		$results = Lapki_Media::get_by_entity( 'animal', $this->animal_id );

		$this->assertCount( 1, $results );
		$this->assertStringEndsWith( 'active.jpg', $results[0]['url'] );
	}

	public function test_get_by_entity_orders_primary_first() {
		$this->make_photo( [ 'file_path' => 'second.jpg', 'is_primary' => 0, 'sort_order' => 0 ] );
		$this->make_photo( [ 'file_path' => 'first.jpg', 'is_primary' => 1, 'sort_order' => 1 ] );

		$results = Lapki_Media::get_by_entity( 'animal', $this->animal_id );

		$this->assertCount( 2, $results );
		$this->assertStringEndsWith( 'first.jpg', $results[0]['url'] );
	}

	public function test_create_primary_photo_unsets_previous_primary() {
		$first_id = $this->make_photo( [ 'file_path' => 'first.jpg', 'is_primary' => 1 ] );

		$second_id = $this->make_photo( [ 'file_path' => 'second.jpg', 'is_primary' => 1 ] );

		$first  = Lapki_Media::get( $first_id );
		$second = Lapki_Media::get( $second_id );

		$this->assertFalse( $first['is_primary'] );
		$this->assertTrue( $second['is_primary'] );
	}

	public function test_get_primary_photo_returns_the_primary_one() {
		$this->make_photo( [ 'file_path' => 'not-primary.jpg', 'is_primary' => 0 ] );
		$this->make_photo( [ 'file_path' => 'primary.jpg', 'is_primary' => 1 ] );

		$primary = Lapki_Media::get_primary_photo( 'animal', $this->animal_id );

		$this->assertNotNull( $primary );
		$this->assertStringEndsWith( 'primary.jpg', $primary['url'] );
	}

	public function test_get_primary_photo_returns_null_when_none_set() {
		$this->make_photo( [ 'file_path' => 'not-primary.jpg', 'is_primary' => 0 ] );

		$this->assertNull( Lapki_Media::get_primary_photo( 'animal', $this->animal_id ) );
	}

	public function test_set_primary_switches_primary_flag() {
		$first_id  = $this->make_photo( [ 'file_path' => 'first.jpg', 'is_primary' => 1 ] );
		$second_id = $this->make_photo( [ 'file_path' => 'second.jpg', 'is_primary' => 0 ] );

		$result = Lapki_Media::set_primary( $second_id );

		$this->assertNotFalse( $result );
		$this->assertFalse( Lapki_Media::get( $first_id )['is_primary'] );
		$this->assertTrue( Lapki_Media::get( $second_id )['is_primary'] );
	}

	public function test_delete_removes_media_record() {
		$id = $this->make_photo();

		$this->assertNotFalse( Lapki_Media::delete( $id ) );
		$this->assertNull( Lapki_Media::get( $id ) );
	}

	public function test_delete_by_entity_removes_all_media() {
		$this->make_photo( [ 'file_path' => 'one.jpg' ] );
		$this->make_photo( [ 'file_path' => 'two.jpg' ] );

		Lapki_Media::delete_by_entity( 'animal', $this->animal_id );

		$this->assertCount( 0, Lapki_Media::get_by_entity( 'animal', $this->animal_id ) );
	}
}
