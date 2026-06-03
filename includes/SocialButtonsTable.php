<?php
namespace SCFS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SocialButtonsTable extends \WP_List_Table {

    private $is_trash = false;
    private $social_buttons;

    public function __construct( $is_trash = false ) {
        $this->is_trash = (bool) $is_trash;
        $this->social_buttons = SocialButtons::get_instance();

        parent::__construct( array(
            'singular' => 'button',
            'plural'   => 'buttons',
            'ajax'     => false,
        ) );

        $this->handle_single_actions();
    }

    private function handle_single_actions() {
        $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
        // NU folosi absint() - ID-urile sunt string-uri!
        $id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';

        if ( empty( $id ) ) {
            return;
        }

        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        switch ( $action ) {
            case 'trash_single':
                if ( wp_verify_nonce( $nonce, 'trash_button_' . $id ) ) {
                    $this->social_buttons->trash( $id );
                    $this->redirect();
                }
                break;
            case 'restore_single':
                if ( wp_verify_nonce( $nonce, 'restore_button_' . $id ) ) {
                    $this->social_buttons->restore( $id );
                    $this->redirect( 'trash' );
                }
                break;
            case 'delete_single':
                if ( wp_verify_nonce( $nonce, 'delete_button_' . $id ) ) {
                    $this->social_buttons->delete( $id );
                    $this->redirect( 'trash' );
                }
                break;
        }
    }

    private function redirect( $tab = '' ) {
        $url = admin_url( 'admin.php?page=scfs-social-buttons' );
        if ( 'trash' === $tab ) {
            $url = add_query_arg( 'action', 'trash', $url );
        }
        wp_safe_redirect( $url );
        exit;
    }

    public function get_columns() {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'order'     => esc_html__( 'Order', 'social-custom-fields-shortcodes' ),
            'name'      => esc_html__( 'Name', 'social-custom-fields-shortcodes' ),
            'label'     => esc_html__( 'Label', 'social-custom-fields-shortcodes' ),
            'icon'      => esc_html__( 'Icon', 'social-custom-fields-shortcodes' ),
            'type'      => esc_html__( 'Type', 'social-custom-fields-shortcodes' ),
            'shortcode' => esc_html__( 'Shortcode', 'social-custom-fields-shortcodes' ),
        );

        if ( $this->is_trash ) {
            $columns['trashed'] = esc_html__( 'Trashed Date', 'social-custom-fields-shortcodes' );
        } else {
            $columns['floating'] = esc_html__( 'Floating', 'social-custom-fields-shortcodes' );
            $columns['actions'] = esc_html__( 'Actions', 'social-custom-fields-shortcodes' );
        }

        if ( ! $this->is_trash ) {
            $columns['actions'] = esc_html__( 'Actions', 'social-custom-fields-shortcodes' );
        } else {
            $columns['actions'] = esc_html__( 'Actions', 'social-custom-fields-shortcodes' );
        }

        return $columns;
    }

    public function get_sortable_columns() {
        return array(
            'order' => array( 'order', true ),
            'name'  => array( 'name', false ),
            'label' => array( 'label', false ),
        );
    }

    public function prepare_items() {
        $all_buttons = $this->social_buttons->get_all( true );
        $buttons = array();

        foreach ( $all_buttons as $button ) {
            $is_trashed = ! empty( $button['trashed'] );
            if ( $this->is_trash && $is_trashed ) {
                $buttons[] = $button;
            } elseif ( ! $this->is_trash && ! $is_trashed ) {
                $buttons[] = $button;
            }
        }

        $buttons = array_values( $buttons );

        $orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $orderby = $orderby ?: 'order';
        $order = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $order = $order ?: 'asc';

        usort( $buttons, function ( $a, $b ) use ( $orderby, $order ) {
            $a_val = isset( $a[ $orderby ] ) ? $a[ $orderby ] : '';
            $b_val = isset( $b[ $orderby ] ) ? $b[ $orderby ] : '';

            if ( is_numeric( $a_val ) && is_numeric( $b_val ) ) {
                $result = $a_val <=> $b_val;
            } else {
                $result = strcasecmp( (string) $a_val, (string) $b_val );
            }

            return ( 'desc' === $order ) ? -$result : $result;
        } );

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = count( $buttons );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );

        $this->items = array_slice( $buttons, ( $current_page - 1 ) * $per_page, $per_page );

        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
        $this->process_bulk_action();
    }

    public function get_bulk_actions() {
        if ( $this->is_trash ) {
            return array(
                'restore'           => esc_html__( 'Restore', 'social-custom-fields-shortcodes' ),
                'delete_permanently' => esc_html__( 'Delete Permanently', 'social-custom-fields-shortcodes' ),
            );
        } else {
            return array(
                'trash' => esc_html__( 'Move to Trash', 'social-custom-fields-shortcodes' ),
            );
        }
    }

    public function process_bulk_action() {
        $action = $this->current_action();
        if ( ! $action ) {
            return;
        }

        $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via absint()
        $raw_ids = isset( $_POST['id'] ) ? wp_unslash( $_POST['id'] ) : array();
        $ids = array_map( 'sanitize_text_field', (array) $raw_ids );

        foreach ( $ids as $id ) {
            switch ( $action ) {
                case 'trash':
                    $this->social_buttons->trash( $id );
                    break;
                case 'restore':
                    $this->social_buttons->restore( $id );
                    break;
                case 'delete_permanently':
                    $this->social_buttons->delete( $id );
                    break;
            }
        }

        $redirect_url = admin_url( 'admin.php?page=scfs-social-buttons' );
        if ( $this->is_trash ) {
            $redirect_url = add_query_arg( 'action', 'trash', $redirect_url );
        }
        
        wp_safe_redirect( $redirect_url );
        exit;
    }

    public function column_cb( $item ) {
        $id = isset( $item['id'] ) ? $item['id'] : '';
        if ( empty( $id ) ) {
            return '';
        }
        return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_attr( $id ) );
    }

    public function column_order( $item ) {
        return isset( $item['order'] ) ? (int) $item['order'] : 0;
    }

    public function column_name( $item ) {
        if ( ! isset( $item['id'] ) ) {
            return '<strong>' . esc_html__( 'Error: No ID', 'social-custom-fields-shortcodes' ) . '</strong>';
        }

        $name = '<strong>' . esc_html( $item['name'] ?? __( 'Unnamed', 'social-custom-fields-shortcodes' ) ) . '</strong>';

        if ( ! $this->is_trash ) {
            $actions = array(
                'edit' => sprintf(
                    '<a href="%s">' . esc_html__( 'Edit', 'social-custom-fields-shortcodes' ) . '</a>',
                    esc_url( admin_url( 'admin.php?page=scfs-social-buttons&action=edit&id=' . urlencode( $item['id'] ) ) )
                ),
                'trash' => sprintf(
                    '<a href="%s" onclick="return confirm(\'' . esc_js( __( 'Move to trash?', 'social-custom-fields-shortcodes' ) ) . '\')">' . esc_html__( 'Trash', 'social-custom-fields-shortcodes' ) . '</a>',
                    esc_url( wp_nonce_url( admin_url( 'admin.php?page=scfs-social-buttons&action=trash_single&id=' . urlencode( $item['id'] ) ), 'trash_button_' . $item['id'] ) )
                )
            );

            $name .= $this->row_actions( $actions );
        }

        return $name;
    }

    public function column_label( $item ) {
        return isset( $item['label'] ) ? esc_html( $item['label'] ) : '';
    }

    public function column_icon( $item ) {
        $icon = isset( $item['icon'] ) ? $item['icon'] : 'fas fa-link';
        return '<i class="' . esc_attr( $icon ) . '"></i>';
    }

    public function column_type( $item ) {
        $types = array(
            'url'       => 'URL',
            'tel'       => 'Phone',
            'mailto'    => 'Email',
            'whatsapp'  => 'WhatsApp',
            'facebook'  => 'Facebook',
            'instagram' => 'Instagram',
            'twitter'   => 'Twitter',
            'linkedin'  => 'LinkedIn',
            'youtube'   => 'YouTube'
        );
        
        $type = $item['type'] ?? 'url';
        return isset( $types[$type] ) ? $types[$type] : esc_html( ucfirst( $type ) );
    }

    public function column_shortcode( $item ) {
        if ( empty( $item['name'] ) ) {
            return '';
        }
        return '<code>[scfs_social name="' . esc_attr( $item['name'] ) . '"]</code>';
    }

    public function column_floating( $item ) {
        return isset( $item['floating'] ) && $item['floating'] 
            ? '<span class="dashicons dashicons-yes" style="color:#46b450;"></span> ' . esc_html__( 'Yes', 'social-custom-fields-shortcodes' )
            : '<span class="dashicons dashicons-no" style="color:#dc3232;"></span> ' . esc_html__( 'No', 'social-custom-fields-shortcodes' );
    }

    public function column_trashed( $item ) {
        return isset( $item['trashed'] ) && ! empty( $item['trashed'] ) 
            ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['trashed'] ) )
            : '';
    }

    public function column_actions( $item ) {
        $id = isset( $item['id'] ) ? $item['id'] : '';
        if ( empty( $id ) ) {
            return '';
        }

        $actions = array();

        if ( $this->is_trash ) {
            $restore_url = wp_nonce_url( 
                admin_url( 'admin.php?page=scfs-social-buttons&action=restore_single&id=' . urlencode( $id ) ), 
                'restore_button_' . $id 
            );
            $delete_url = wp_nonce_url( 
                admin_url( 'admin.php?page=scfs-social-buttons&action=delete_single&id=' . urlencode( $id ) ), 
                'delete_button_' . $id 
            );
            
            $actions['restore'] = '<a href="' . esc_url( $restore_url ) . '" class="button button-small">' . esc_html__( 'Restore', 'social-custom-fields-shortcodes' ) . '</a>';
            $actions['delete'] = '<a href="' . esc_url( $delete_url ) . '" class="button button-small button-danger" onclick="return confirm(\'' . esc_js( __( 'Delete permanently?', 'social-custom-fields-shortcodes' ) ) . '\')">' . esc_html__( 'Delete', 'social-custom-fields-shortcodes' ) . '</a>';
        } else {
            $edit_url = admin_url( 'admin.php?page=scfs-social-buttons&action=edit&id=' . urlencode( $id ) );
            $trash_url = wp_nonce_url( 
                admin_url( 'admin.php?page=scfs-social-buttons&action=trash_single&id=' . urlencode( $id ) ), 
                'trash_button_' . $id 
            );
            
            $actions['edit'] = '<a href="' . esc_url( $edit_url ) . '" class="button button-small">' . esc_html__( 'Edit', 'social-custom-fields-shortcodes' ) . '</a>';
            $actions['trash'] = '<a href="' . esc_url( $trash_url ) . '" class="button button-small button-link-delete" onclick="return confirm(\'' . esc_js( __( 'Move to trash?', 'social-custom-fields-shortcodes' ) ) . '\')">' . esc_html__( 'Trash', 'social-custom-fields-shortcodes' ) . '</a>';
        }

        return implode( ' ', $actions );
    }

    public function no_items() {
        if ( $this->is_trash ) {
            esc_html_e( 'No buttons found in trash.', 'social-custom-fields-shortcodes' );
        } else {
            esc_html_e( 'No buttons found.', 'social-custom-fields-shortcodes' );
        }
    }
}
