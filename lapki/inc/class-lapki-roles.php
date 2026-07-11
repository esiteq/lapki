<?php

/**
 * Lapki Roles & Capabilities
 *
 * Визначає кастомні capabilities та ролі для розмежування доступу
 * до REST API та адмін-панелі (притулки, волонтери).
 *
 * @package Lapki
 * @author Oleksii Bugrov
 */

class Lapki_Roles {

    // Capabilities
    const CAP_MANAGE_ANIMALS = 'lapki_manage_animals';
    const CAP_MANAGE_ORGANIZATIONS = 'lapki_manage_organizations';
    const CAP_MANAGE_ATTRIBUTES = 'lapki_manage_attributes';

    // Ролі
    const ROLE_SHELTER_ADMIN = 'lapki_shelter_admin';
    const ROLE_VOLUNTEER = 'lapki_volunteer';

    /**
     * Встановити ролі та роздати capabilities адміністратору.
     * Викликається при активації плагіна і при виявленні зміни версії.
     */
    public static function install() {
        self::add_roles();
        self::grant_admin_capabilities();
    }

    /**
     * Зареєструвати кастомні ролі плагіна
     */
    private static function add_roles() {
        add_role(self::ROLE_SHELTER_ADMIN, 'Адміністратор притулку (Lapki)', [
            'read' => true,
            'upload_files' => true,
            self::CAP_MANAGE_ANIMALS => true,
            self::CAP_MANAGE_ORGANIZATIONS => true,
        ]);

        add_role(self::ROLE_VOLUNTEER, 'Волонтер (Lapki)', [
            'read' => true,
            'upload_files' => true,
            self::CAP_MANAGE_ANIMALS => true,
        ]);
    }

    /**
     * Дати адміністратору сайту всі capabilities плагіна
     */
    private static function grant_admin_capabilities() {
        $admin = get_role('administrator');

        if (!$admin) {
            return;
        }

        $admin->add_cap(self::CAP_MANAGE_ANIMALS);
        $admin->add_cap(self::CAP_MANAGE_ORGANIZATIONS);
        $admin->add_cap(self::CAP_MANAGE_ATTRIBUTES);
    }

    /**
     * Чи є користувач власником організації (або адміністратором сайту)
     *
     * @param int $organization_id
     * @param int $wp_user_id
     * @return bool
     */
    public static function user_owns_organization($organization_id, $wp_user_id) {
        if (user_can($wp_user_id, 'manage_options')) {
            return true;
        }

        return Lapki_Organization::belongs_to_user($organization_id, $wp_user_id);
    }
}
