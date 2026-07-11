<?php
/**
 * Tests for Lapki_Attributes::create/get/get_all/count/update/delete
 * and the read-only lookup helpers (get_global_attributes/get_animal_types/
 * get_breeds_by_type/get_type_attributes).
 *
 * The plugin bundles a seed dictionary of ~965 attribute rows that is loaded
 * once outside any test transaction (see CHANGELOG.md, session 3) and never
 * rolled back. Every row created here uses a random suffix so it can't
 * collide with the unique key (entity, entity_type, attr_name, attr_value,
 * lang) or be miscounted alongside seed data.
 *
 * @package Lapki
 */

class Test_Lapki_Attributes extends WP_UnitTestCase {

	private $suffix;

	public function set_up() {
		parent::set_up();
		$this->suffix = wp_generate_password( 8, false );
	}

	private function make_attribute( $overrides = [] ) {
		$data = array_merge( [
			'entity'       => 'animal',
			'entity_type'  => 'dog',
			'attr_name'    => 'breed',
			'attr_value'   => 'husky-' . $this->suffix,
			'attr_display' => 'Хаскі ' . $this->suffix,
			'lang'         => 'uk',
		], $overrides );

		return Lapki_Attributes::create( $data );
	}

	public function test_create_returns_positive_id() {
		$id = $this->make_attribute();

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );
	}

	public function test_get_returns_created_attribute() {
		$id = $this->make_attribute( [ 'attr_display' => 'Хаскі Тест' ] );

		$attribute = Lapki_Attributes::get( $id );

		$this->assertSame( 'Хаскі Тест', $attribute['attr_display'] );
		$this->assertSame( 'dog', $attribute['entity_type'] );
	}

	public function test_get_returns_null_for_nonexistent_id() {
		$this->assertNull( Lapki_Attributes::get( 999999999 ) );
	}

	public function test_get_all_filters_by_attr_name_and_lang() {
		$this->make_attribute( [ 'attr_name' => 'breed', 'lang' => 'uk' ] );
		$this->make_attribute( [ 'attr_name' => 'color', 'attr_value' => 'black-' . $this->suffix, 'lang' => 'uk' ] );
		$this->make_attribute( [ 'attr_name' => 'breed', 'attr_value' => 'husky-en-' . $this->suffix, 'lang' => 'en' ] );

		$results = Lapki_Attributes::get_all( [
			'attr_name' => 'breed',
			'lang'      => 'uk',
			'search'    => $this->suffix,
		] );

		$this->assertCount( 1, $results );
		$this->assertSame( 'breed', $results[0]['attr_name'] );
		$this->assertSame( 'uk', $results[0]['lang'] );
	}

	public function test_get_all_search_matches_value_or_display() {
		$this->make_attribute( [ 'attr_value' => 'zzz-' . $this->suffix, 'attr_display' => 'Унікальний Хаскі' ] );

		$results = Lapki_Attributes::get_all( [ 'search' => 'Унікальний' ] );

		$this->assertCount( 1, $results );
	}

	public function test_get_all_respects_limit_and_offset() {
		$this->make_attribute( [ 'attr_value' => 'a-' . $this->suffix ] );
		$this->make_attribute( [ 'attr_value' => 'b-' . $this->suffix ] );
		$this->make_attribute( [ 'attr_value' => 'c-' . $this->suffix ] );

		$page1 = Lapki_Attributes::get_all( [ 'search' => $this->suffix, 'limit' => 2, 'offset' => 0 ] );
		$page2 = Lapki_Attributes::get_all( [ 'search' => $this->suffix, 'limit' => 2, 'offset' => 2 ] );

		$this->assertCount( 2, $page1 );
		$this->assertCount( 1, $page2 );
	}

	public function test_count_matches_filters() {
		$this->make_attribute( [ 'attr_value' => 'a-' . $this->suffix ] );
		$this->make_attribute( [ 'attr_value' => 'b-' . $this->suffix ] );

		$this->assertSame( 2, Lapki_Attributes::count( [ 'search' => $this->suffix ] ) );
	}

	public function test_update_modifies_fields() {
		$id = $this->make_attribute( [ 'attr_display' => 'Старе значення' ] );

		Lapki_Attributes::update( $id, [ 'attr_display' => 'Нове значення' ] );

		$this->assertSame( 'Нове значення', Lapki_Attributes::get( $id )['attr_display'] );
	}

	public function test_delete_removes_attribute() {
		$id = $this->make_attribute();

		Lapki_Attributes::delete( $id );

		$this->assertNull( Lapki_Attributes::get( $id ) );
	}

	public function test_get_global_attributes_groups_by_attr_name() {
		$this->make_attribute( [ 'entity_type' => 'all', 'attr_name' => 'gender', 'attr_value' => 'male-' . $this->suffix, 'attr_display' => 'Самець ' . $this->suffix ] );
		$this->make_attribute( [ 'entity_type' => 'all', 'attr_name' => 'gender', 'attr_value' => 'female-' . $this->suffix, 'attr_display' => 'Самка ' . $this->suffix ] );

		$attributes = Lapki_Attributes::get_global_attributes( 'uk' );

		$this->assertArrayHasKey( 'gender', $attributes );
		$values = wp_list_pluck( $attributes['gender'], 'value' );
		$this->assertContains( 'male-' . $this->suffix, $values );
		$this->assertContains( 'female-' . $this->suffix, $values );
	}

	public function test_get_animal_types_returns_species_type_rows() {
		$this->make_attribute( [ 'entity_type' => 'type', 'attr_name' => 'species', 'attr_value' => 'ferret-' . $this->suffix, 'attr_display' => 'Тхір ' . $this->suffix ] );

		$types = Lapki_Attributes::get_animal_types( 'uk' );

		$values = wp_list_pluck( $types, 'type' );
		$this->assertContains( 'ferret-' . $this->suffix, $values );
	}

	public function test_get_breeds_by_type_filters_by_entity_type_and_lang() {
		$this->make_attribute( [ 'entity_type' => 'dog', 'attr_name' => 'breed', 'attr_value' => 'husky-' . $this->suffix, 'attr_display' => 'Хаскі ' . $this->suffix, 'lang' => 'uk' ] );
		$this->make_attribute( [ 'entity_type' => 'cat', 'attr_name' => 'breed', 'attr_value' => 'siamese-' . $this->suffix, 'attr_display' => 'Сіамська ' . $this->suffix, 'lang' => 'uk' ] );

		$breeds = Lapki_Attributes::get_breeds_by_type( 'dog', 'uk' );

		$values = wp_list_pluck( $breeds, 'value' );
		$this->assertContains( 'husky-' . $this->suffix, $values );
		$this->assertNotContains( 'siamese-' . $this->suffix, $values );
	}

	public function test_get_type_attributes_merges_type_specific_and_global() {
		$this->make_attribute( [ 'entity_type' => 'dog', 'attr_name' => 'breed', 'attr_value' => 'husky-' . $this->suffix, 'attr_display' => 'Хаскі ' . $this->suffix, 'lang' => 'uk' ] );
		$this->make_attribute( [ 'entity_type' => 'all', 'attr_name' => 'gender', 'attr_value' => 'male-' . $this->suffix, 'attr_display' => 'Самець ' . $this->suffix, 'lang' => 'uk' ] );
		$this->make_attribute( [ 'entity_type' => 'cat', 'attr_name' => 'breed', 'attr_value' => 'siamese-' . $this->suffix, 'attr_display' => 'Сіамська ' . $this->suffix, 'lang' => 'uk' ] );

		$attributes = Lapki_Attributes::get_type_attributes( 'dog', 'uk' );

		$this->assertArrayHasKey( 'breed', $attributes );
		$this->assertArrayHasKey( 'gender', $attributes );

		$breed_values = wp_list_pluck( $attributes['breed'], 'value' );
		$this->assertContains( 'husky-' . $this->suffix, $breed_values );
		$this->assertNotContains( 'siamese-' . $this->suffix, $breed_values );
	}
}
