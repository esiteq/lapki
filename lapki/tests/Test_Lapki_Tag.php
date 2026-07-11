<?php
/**
 * Tests for Lapki_Tag::get_by_entity/delete_by_entity
 *
 * Lapki_Tag has no create() of its own (tags feature has no writer yet in the
 * plugin code) — tests insert rows directly via $wpdb, mirroring how a future
 * writer would populate wp_lapki_tags.
 *
 * @package Lapki
 */

class Test_Lapki_Tag extends WP_UnitTestCase {

	private $animal_id;

	public function set_up() {
		parent::set_up();

		$organization_id = Lapki_Organization::create( [
			'name'       => 'Test Shelter',
			'wp_user_id' => self::factory()->user->create(),
		] );

		$this->animal_id = Lapki_Animal::create( [
			'organization_id' => $organization_id,
			'name'            => 'Барсик',
			'type'            => 'cat',
			'species'         => 'cat',
			'age'             => 'young',
			'gender'          => 'male',
			'size'            => 'medium',
		] );
	}

	private function insert_tag( $entity_type, $entity_id, $tag ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'lapki_tags', [
			'entity_type' => $entity_type,
			'entity_id'   => $entity_id,
			'tag'         => $tag,
		] );

		return $wpdb->insert_id;
	}

	public function test_get_by_entity_returns_tags_for_entity() {
		$this->insert_tag( 'animal', $this->animal_id, 'friendly' );
		$this->insert_tag( 'animal', $this->animal_id, 'playful' );

		$tags = Lapki_Tag::get_by_entity( 'animal', $this->animal_id );

		$this->assertCount( 2, $tags );
		$this->assertContains( 'friendly', $tags );
		$this->assertContains( 'playful', $tags );
	}

	public function test_get_by_entity_returns_empty_array_when_no_tags() {
		$tags = Lapki_Tag::get_by_entity( 'animal', $this->animal_id );

		$this->assertSame( [], $tags );
	}

	public function test_get_by_entity_does_not_mix_different_entities() {
		$other_animal_id = Lapki_Animal::create( [
			'organization_id' => Lapki_Animal::get( $this->animal_id )['organization_id'],
			'name'            => 'Рекс',
			'type'            => 'dog',
			'species'         => 'dog',
			'age'             => 'adult',
			'gender'          => 'male',
			'size'            => 'large',
		] );

		$this->insert_tag( 'animal', $this->animal_id, 'friendly' );
		$this->insert_tag( 'animal', $other_animal_id, 'guard-dog' );

		$this->assertSame( [ 'friendly' ], Lapki_Tag::get_by_entity( 'animal', $this->animal_id ) );
		$this->assertSame( [ 'guard-dog' ], Lapki_Tag::get_by_entity( 'animal', $other_animal_id ) );
	}

	public function test_delete_by_entity_removes_all_tags_for_entity() {
		$this->insert_tag( 'animal', $this->animal_id, 'friendly' );
		$this->insert_tag( 'animal', $this->animal_id, 'playful' );

		Lapki_Tag::delete_by_entity( 'animal', $this->animal_id );

		$this->assertSame( [], Lapki_Tag::get_by_entity( 'animal', $this->animal_id ) );
	}

	public function test_delete_by_entity_does_not_touch_other_entities() {
		$other_animal_id = Lapki_Animal::create( [
			'organization_id' => Lapki_Animal::get( $this->animal_id )['organization_id'],
			'name'            => 'Рекс',
			'type'            => 'dog',
			'species'         => 'dog',
			'age'             => 'adult',
			'gender'          => 'male',
			'size'            => 'large',
		] );

		$this->insert_tag( 'animal', $this->animal_id, 'friendly' );
		$this->insert_tag( 'animal', $other_animal_id, 'guard-dog' );

		Lapki_Tag::delete_by_entity( 'animal', $this->animal_id );

		$this->assertSame( [], Lapki_Tag::get_by_entity( 'animal', $this->animal_id ) );
		$this->assertSame( [ 'guard-dog' ], Lapki_Tag::get_by_entity( 'animal', $other_animal_id ) );
	}
}
