<?php
// User taxonomy and custom meta portions sourced from
// http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress and
// http://justintadlock.com/archives/2009/09/10/adding-and-using-custom-user-profile-fields
class WSUWP_VALS_Custom_Roles {
	/**
	 * @var WSUWP_VALS_Custom_Roles
	 */
	private static $instance;

	/**
	 * @since 0.0.1
	 *
	 * @var array Names for custom VALS roles.
	 */
	public $roles = array(
		'trainee' => 'vals_trainee',
		'certified' => 'vals_certified',
		'admin' => 'vals_center_admin',
	);

	/**
	 * @since 0.0.1
	 *
	 * @var string Slug for tracking the 'Center' taxonomy.
	 */
	public $taxonomy_slug = 'vals_center';

	/**
	 * Maintain and return the one instance.
	 * Initiate hooks when called the first time.
	 *
	 * @since 0.0.1
	 *
	 * @return \WSUWP_VALS_Custom_Roles
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSUWP_VALS_Custom_Roles();
			self::$instance->setup_hooks();
		}
		return self::$instance;
	}

	/**
	 * Setup hooks to include.
	 *
	 * @since 0.0.1
	 */
	public function setup_hooks() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 12 );
		add_action( 'personal_options', array( $this, 'extend_user_profile' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_center_data' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_center_data' ) );
		add_action( 'admin_menu', array( $this, 'center_admin_page' ) );
		add_filter( 'parent_file', array( $this, 'user_center_page' ) );
		add_filter( 'manage_edit-center_columns', array( $this, 'center_user_column' ) );
		add_action( 'manage_center_custom_column', array( $this, 'manage_center_column' ), 10, 3 );
		add_filter( 'manage_users_columns', array( $this, 'users_custom_columns' ) );
		add_action( 'manage_users_custom_column', array( $this, 'manage_users_vals_columns' ), 10, 3 );
		add_filter( 'manage_users_sortable_columns', array( $this, 'manage_users_vals_sortable_columns' ) );
		add_filter( 'login_redirect', array( $this, 'vals_roles_login_redirect' ), 10, 3 );
		add_action( 'current_screen', array( $this, 'vals_roles_redirect' ) );
		add_action( 'admin_init', array( $this, 'vals_roles_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'vals_roles_enqueue_scripts' ) );
		add_action( 'pre_get_users', array( $this, 'vals_pre_user_query' ) );
		add_filter( 'views_users', array( $this, 'vals_center_admin_views_users' ) );
		add_filter( 'editable_roles', array( $this, 'vals_center_admin_editable_roles' ) );
		add_action( 'user_register', array( $this, 'save_new_user_center' ) );
		add_action( 'set_user_role', array( $this, 'add_certification_date' ), 10, 2 );
		add_filter( 'map_meta_cap', array( $this, 'admin_edit_vals_roles' ), 10, 4 );
		add_filter( 'enable_edit_any_user_configuration', '__return_true' );
	}

	/**
	 * Add 'VALS Registered Trainee' and 'VALS Center Admin' custom roles.
	 *
	 * @since 0.0.1
	 */
	public static function add_roles() {
		add_role(
			WSUWP_VALS_Custom_Roles()->roles['trainee'],
			'VALS Registered Trainee',
			array(
				'read' => true,
			)
		);

		add_role(
			WSUWP_VALS_Custom_Roles()->roles['certified'],
			'VALS Certified',
			array(
				'read' => true,
			)
		);

		add_role(
			WSUWP_VALS_Custom_Roles()->roles['admin'],
			'VALS Center Admin',
			array(
				'read' => true,
				'list_users' => true,
				'create_users' => true,
			)
		);
	}

	/**
	 * Remove custom roles on deactivation.
	 */
	public static function remove_roles() {
		remove_role( WSUWP_VALS_Custom_Roles()->roles['trainee'] );
		remove_role( WSUWP_VALS_Custom_Roles()->roles['certified'] );
		remove_role( WSUWP_VALS_Custom_Roles()->roles['admin'] );
	}

	/**
	 * Register the 'Center' taxonomy for the users object type.
	 *
	 * @since 0.0.1
	 */
	public function register_taxonomy() {
		$labels = array(
			'name' => 'VALS Centers',
			'singular_name' => 'Center',
			'search_items' => 'Search Centers',
			'popular_items' => 'Popular Centers',
			'all_items' => 'All Centers',
			'parent_item' => 'Parent Center',
			'parent_item_colon' => 'Parent Center:',
			'edit_item' => 'Edit Center',
			'view_item' => 'View Center',
			'update_item' => 'Update Center',
			'add_new_item' => 'Add New Center',
			'new_item_name' => 'New Center Name',
			'not_found' => 'No centers found',
			'no_terms' => 'No centers',
		);

		$capabilities = array(
			'manage_terms' => 'manage_options',
			'edit_terms' => 'manage_options',
			'delete_terms' => 'manage_options',
			'assign_terms' => 'manage_options',
		);

		$args = array(
			'labels' => $labels,
			'description' => 'Scholarship Center.',
			'public' => false,
			'hierarchical' => true,
			'show_ui' => true,
			'show_tagcloud' => false,
			'show_admin_column' => true,
			'capabilities' => $capabilities,
			'update_count_callback' => '_update_generic_term_count',
		);

		register_taxonomy( $this->taxonomy_slug, 'user', $args );
	}

	/**
	 * Return the certification with a wrapper indicating its status.
	 *
	 * @since 0.0.1
	 *
	 * @param string $certification The user's certification date.
	 *
	 * @return string $certification The user's certification date.
	 */
	public function certification_status( $certification ) {
		$certification_date = new DateTime( $certification );
		$today = new DateTime( 'now' );
		$difference = $certification_date->diff( $today );

		if ( 5 < (int) $difference->y ) {
			// User's certification has expired.
			return '<span style="color:#c60c30;">' . esc_html( $certification ) . '</span>';
		} elseif ( 54 < (int) 12 * $difference->y + $difference->m ) {
			// User's certification expires in 6 (or fewer) months.
			return '<span style="color:#f6861f;">' . esc_html( $certification ) . '</span>';
		} else {
			return esc_html( $certification );
		}
	}

	/**
	 * Add a 'VALS Data' section to the edit user/profile page.
	 *
	 * @since 0.0.1
	 *
	 * @param object $user The user object currently being edited.
	 */
	public function extend_user_profile( $user ) {
		// Bail if not viewing a profile for a user with a custom VALS role.
		if ( ! array_intersect( $this->roles, (array) $user->roles ) ) {
			return;
		}

		wp_nonce_field( 'save-vals-user-data', '_vals_user_nonce' );

		?>
		<tr class="vals-data">
			<td colspan="2">
				<h2>VALS Data</h2>
			</td>
		</tr>
		<?php
		$taxonomy = get_taxonomy( $this->taxonomy_slug );
		$terms = get_terms( $this->taxonomy_slug, array( 'hide_empty' => false ) );
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			?>
			<tr class="vals-data">
				<th><label for="<?php echo esc_attr( $this->taxonomy_slug ); ?>">Center</label></th>
				<td id="<?php echo esc_attr( $this->taxonomy_slug ); ?>"><?php

				if ( current_user_can( $taxonomy->cap->assign_terms ) ) {
					foreach ( $terms as $term ) {
						?>
						<input type="radio"
							   name="<?php echo esc_attr( $this->taxonomy_slug ); ?>"
							   id="<?php echo esc_attr( $this->taxonomy_slug . '-' . $term->slug ); ?>"
							   value="<?php echo esc_attr( $term->slug ); ?>"
								<?php checked( true, is_object_in_term( $user->ID, $this->taxonomy_slug, $term ) ); ?> />
						<label for="<?php echo esc_attr( $this->taxonomy_slug . '-' . $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></label><br />
						<?php
					}
				} else {
					$center = wp_get_object_terms( $user->ID, $this->taxonomy_slug );
					?><p><?php echo esc_html( $center[0]->name ); ?></p><?php
				}
				?></td>
			</tr>
			<?php
		}

		if ( in_array( $this->roles['certified'], (array) $user->roles, true ) ) {
			?>
			<tr class="vals-data">
				<th>
					<label for="certification">Certification Date</label>
				</th>
				<td>
					<?php
					$certification = get_the_author_meta( 'certification', $user->ID );

					if ( current_user_can( $taxonomy->cap->assign_terms ) ) { ?>
						<input type="date"
							   name="certification"
							   id="certification"
							   value="<?php echo esc_attr( $certification ); ?>"
							   class="regular-text" />
					<?php } else { ?>
						<p><?php echo wp_kses_post( $this->certification_status( $certification ) ); ?></p>
					<?php } ?>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Save additional user data.
	 *
	 * @since 0.0.1
	 *
	 * @param int $user_id The ID of the user to save the additional data for.
	 */
	public function save_user_center_data( $user_id ) {
		if ( ! isset( $_POST['_vals_user_nonce'] ) || ! wp_verify_nonce( $_POST['_vals_user_nonce'], 'save-vals-user-data' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( isset( $_POST[ $this->taxonomy_slug ] ) ) {
			$term = sanitize_text_field( $_POST[ $this->taxonomy_slug ] );

			wp_set_object_terms( $user_id, array( $term ), $this->taxonomy_slug, false );
			clean_object_term_cache( $user_id, $this->taxonomy_slug );
		}

		if ( isset( $_POST['certification'] ) ) {
			update_user_meta( $user_id, 'certification', sanitize_text_field( $_POST['certification'] ) );
		}
	}

	/**
	 * Create an admin page for the 'Center' taxonomy under the 'Users' menu.
	 *
	 * @since 0.0.1
	 */
	function center_admin_page() {
		$taxonomy = get_taxonomy( $this->taxonomy_slug );

		add_users_page(
			esc_attr( $taxonomy->labels->name ),
			esc_attr( $taxonomy->labels->name ),
			$taxonomy->cap->manage_terms,
			'edit-tags.php?taxonomy=' . $taxonomy->name
		);
	}

	/**
	 * Keep the 'Users' sub-menu open when viewing the 'Center' taxonomy or terms pages.
	 *
	 * @since 0.0.1
	 *
	 * @param string $parent_file The parent file.
	 *
	 * @return string $parent_file The parent file.
	 */
	public function user_center_page( $parent_file = '' ) {
		$screen = get_current_screen();

		if ( $screen->taxonomy === $this->taxonomy_slug ) {
			$parent_file = 'users.php';
		}

		return $parent_file;
	}

	/**
	 * Unset the 'posts' column and add a 'users' column on the 'Center' taxonomy page.
	 *
	 * @since 0.0.1
	 *
	 * @param array $columns Default columns shown in the manage terms table.
	 *
	 * @return array $columns Columns to be shown in the manage terms table.
	 */
	public function center_user_column( $columns ) {
		unset( $columns['posts'] );

		$columns['users'] = 'Users';

		return $columns;
	}

	/**
	 * Displays content for custom columns on the 'Center' taxonomy page.
	 *
	 * @since 0.0.1
	 *
	 * @param string $display WP just passes an empty string here.
	 * @param string $column  The name of the custom column.
	 * @param int    $term_id The ID of the term being displayed in the table.
	 */
	function manage_center_column( $display, $column, $term_id ) {
		if ( 'users' === $column ) {
			$term = get_term( $term_id, $this->taxonomy_slug );
			echo esc_html( $term->count );
		}
	}

	/**
	 * Modify the columns on the 'All Users' page.
	 *
	 * @since 0.0.1
	 *
	 * @param array $columns Default columns shown in the All Users table.
	 *
	 * @return array $columns Columns to be shown in the All Users table.
	 */
	public function users_custom_columns( $columns ) {
		$columns['vals_date_certified'] = 'Date Certified';
		$columns['vals_center'] = 'Center';
		$user = wp_get_current_user();

		if ( $this->vals_admin_role( $user ) ) {
			unset( $columns['posts'] );
			unset( $columns['vals_center'] );
		}

		return $columns;
	}

	/**
	 * Displays content for custom columns on the 'All Users' page.
	 *
	 * @since 0.0.1
	 *
	 * @param string $display Empty string.
	 * @param string $column  The name of the custom column.
	 * @param int    $user_id ID of the currently-listed user.
	 *
	 * @return string $display The custom value to display.
	 */
	function manage_users_vals_columns( $display, $column, $user_id ) {
		if ( 'vals_date_certified' === $column ) {
			$certification = get_user_meta( $user_id, 'certification', true );
			$display = $this->certification_status( $certification );
		}

		if ( 'vals_center' === $column ) {
			$centers = wp_get_object_terms( $user_id, $this->taxonomy_slug );
			if ( ! empty( $centers ) ) {
				$display = esc_html( $centers[0]->name );
			}
		}

		return $display;
	}

	/**
	 * Allow for sorting users by the 'Date Certified' column.
	 *
	 * @since 0.0.1
	 *
	 * @param array $sortable_columns The default array of sortable columns.
	 *
	 * @return array $sortable_columns Modified array of sortable columns.
	 */
	public function manage_users_vals_sortable_columns( $sortable_columns ) {
		$sortable_columns['vals_date_certified'] = 'certification_date';

		return $sortable_columns;
	}

	/**
	 * Determine whether the user has a VALS role other than 'VALS Center Admin'.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_User $user.
	 *
	 * @return boolean
	 */
	public function non_admin_role( $user ) {
		$non_admin_roles = $this->roles;

		unset( $non_admin_roles['admin'] );

		if ( isset( $user->roles ) && is_array( $user->roles ) && array_intersect( $non_admin_roles, (array) $user->roles ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Determine if the user has the 'VALS Center Admin' role.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_User $user.
	 *
	 * @return boolean
	 */
	public function vals_admin_role( $user ) {
		if ( isset( $user->roles ) && is_array( $user->roles ) && in_array( $this->roles['admin'], $user->roles, true ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Redirect users with a custom VALS role after successful login.
	 *
	 * @since 0.0.1
	 * @since 0.0.2 Added a redirect for users with the 'VALS Center Admin' role.
	 *
	 * @param string $redirect_to URL to redirect to.
	 * @param string $request     URL the user is coming from.
	 * @param object $user        WP_User object if login was successful, WP_Error object otherwise.
	 *
	 * @return string $redirect_to URL to redirect to.
	 */
	function vals_roles_login_redirect( $redirect_to, $request, $user ) {
		// Bail if the login was not successful.
		if ( is_wp_error( $user ) ) {
			return $redirect_to;
		}

		// Redirect users with the 'VALS Registered Trainee' or 'VALS Certified' roles to their profile page.
		if ( $this->non_admin_role( $user ) ) {
			$redirect_to = get_edit_profile_url( $user->ID );
		}

		// Redirect users with the 'VALS Center Admin' role to the 'All Users' page.
		if ( $this->vals_admin_role( $user ) ) {
			$redirect_to = get_dashboard_url( $user->ID, 'users.php' );
		}

		return $redirect_to;
	}

	/**
	 * Redirect users with a custom VALS role away from certain Dashboard pages.
	 *
	 * @since 0.0.1
	 * @since 0.0.2 Added a redirect for users with the 'VALS Center Admin' role.
	 *
	 * @param WP_Screen object.
	 */
	public function vals_roles_redirect( $current_screen ) {
		$user = wp_get_current_user();

		// Bail if the user does not have a custom VALS role.
		if ( ! array_intersect( $this->roles, (array) $user->roles ) ) {
			return;
		}

		// 'VALS Registered Trainee' or 'VALS Certified' roles can only access their profile page.
		if ( $this->non_admin_role( $user ) && 'profile' !== $current_screen->base ) {
			wp_redirect( get_edit_profile_url( $user->ID ) );
		}

		// Users with the 'Vals Center Admin' role can access the following pages:
		// 'All Users', 'Users' > 'Add New', and 'Your Profile'.
		if ( $this->vals_admin_role( $user ) && ! in_array( $current_screen->base, array( 'users', 'user', 'profile' ), true ) ) {
			wp_redirect( get_dashboard_url( $user->ID, 'users.php' ) );
		}
	}

	/**
	 * Remove the 'Dashboard' page for users with a custom VALS role.
	 *
	 * @since 0.0.1
	 * @since 0.0.2 Updated to remove the page for users with any VALS role.
	 */
	public function vals_roles_menu_pages() {
		$user = wp_get_current_user();

		if ( array_intersect( $this->roles, (array) $user->roles ) ) {
			remove_menu_page( 'index.php' );
		}
	}

	/**
	 * Enqueue stylesheets on certain dashboard pages for users with a custom VALS role.
	 *
	 * @since 0.0.1
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function vals_roles_enqueue_scripts( $hook_suffix ) {
		// Hide a majority of the default profile fields of/for users with a custom VALS role.
		if ( in_array( $hook_suffix, array( 'profile.php', 'user-edit.php' ), true ) ) {
			global $user_id;

			$user = get_userdata( $user_id );

			if ( array_intersect( $this->roles, (array) $user->roles ) ) {
				wp_enqueue_style( 'vals-role-profile', plugins_url( 'css/vals-role-profile.css', dirname( __FILE__ ) ) );
			}
		}

		// Hide the "Add Existing User" form from VALS Center Admins.
		if ( 'user-new.php' === $hook_suffix ) {
			$current_user = wp_get_current_user();

			if ( $this->vals_admin_role( $current_user ) ) {
				wp_enqueue_style( 'vals-role-profile', plugins_url( 'css/add-user.css', dirname( __FILE__ ) ) );
			}
		}
	}

	/**
	 * Modify the user listing in certain cases.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_User_Query $query.
	 */
	public function vals_pre_user_query( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		// Order users by certification date.
		$orderby = $query->get( 'orderby' );

		if ( 'certification_date' === $orderby ) {
			$query->set( 'meta_key', 'certification' );
			$query->set( 'orderby', 'meta_value_num date' );
		}

		// When users with the 'VALS Registered Trainee' role are viewing the user list,
		// only show users associated with the same VALS center.
		$user = wp_get_current_user();

		if ( $this->vals_admin_role( $user ) ) {
			$center = wp_get_object_terms( $user->ID, $this->taxonomy_slug );

			// There's no `tax_query` implementation in `WP_User_Query`, so we'll try
			// to grab an array of user IDs to pass as the `include` parameter value.
			$center_users = get_objects_in_term( $center[0]->term_id, $this->taxonomy_slug );

			if ( is_array( $center ) && is_array( $center_users ) ) {
				$query->set( 'role__in', $this->roles );
				$query->set( 'include', $center_users );
			}
		}
	}

	/**
	 * When users with the 'VALS Center Admin' role are viewing the user list,
	 * remove the default views and add a new one for the the user's VALS center.
	 *
	 * @since 0.0.1
	 *
	 * @param array $views Default list table views.
	 *
	 * @return array $views Modified list table views.
	 */
	public function vals_center_admin_views_users( $views ) {
		$user = wp_get_current_user();

		if ( $this->vals_admin_role( $user ) ) {
			$center = wp_get_object_terms( $user->ID, $this->taxonomy_slug );
			$center_users = get_objects_in_term( $center[0]->term_id, $this->taxonomy_slug );
			$view_value = '<a href="users.php" class="current">' . $center[0]->name . ' Users ';
			$view_value .= '<span class="count">(' . count( $center_users ) . ')</span></a>';
			$views = array( 'vals_center' => $view_value );
		}

		return $views;
	}

	/**
	 * Filter the roles that users with the 'VALS Center Admin' role can assign to others.
	 *
	 * @since 0.0.1
	 *
	 * @param array $all_roles All roles.
	 *
	 * @return array $all_roles Modified roles.
	 */
	public function vals_center_admin_editable_roles( $all_roles ) {
		$user = wp_get_current_user();

		if ( $this->vals_admin_role( $user ) ) {
			unset( $all_roles['subscriber'] );
			unset( $all_roles['contributor'] );
			unset( $all_roles['author'] );
			unset( $all_roles['editor'] );
			unset( $all_roles['vals_center_admin'] );
			unset( $all_roles['vals_certified'] );
		}

		return $all_roles;
	}

	/**
	 * Associate new users added by a VALS Center Admin with the respective VALS Center.
	 *
	 * @since 0.0.2
	 *
	 * @param int $user_id The ID of the new user.
	 */
	public function save_new_user_center( $user_id ) {
		$current_user = wp_get_current_user();

		if ( $this->vals_admin_role( $current_user ) ) {
			$center = wp_get_object_terms( $current_user->ID, $this->taxonomy_slug );
			wp_set_object_terms( $user_id, array( $center[0]->slug ), $this->taxonomy_slug, false );
		}
	}

	/**
	 * Set the Certification Date when a user's role is changed to 'VALS Certified'.
	 *
	 * @since 0.0.2
	 *
	 * @param int    $user_id The user ID.
	 * @param string $role    The user's new role.
	 */
	public function add_certification_date( $user_id, $role ) {
		if ( $this->roles['certified'] === $role ) {
			// Use today's date. (This can be manually changed by editing the user, if needed.)
			update_user_meta( $user_id, 'certification', sanitize_text_field( date( 'Y-m-d' ) ) );
		}
	}

	/**
	 * Allow site Administrators to edit users with a VALS role.
	 *
	 * @param array  $caps    The user's actual capabilities.
	 * @param string $cap     Capability name.
	 * @param int    $user_id The user ID.
	 * @param array  $args    Adds the context to the cap (typically the object ID).
	 *
	 * @return
	 */
	public function admin_edit_vals_roles( $caps, $cap, $user_id, $args ) {
		if ( 'edit_user' === $cap ) {
			$user = get_userdata( $user_id );

			// Bail if the current user doesn't have the Administrator role.
			if ( ! $user || ! in_array( 'administrator', (array) $user->roles, true ) || ! $args ) {
				return $caps;
			}

			$vals_user = get_userdata( $args[0] );

			// Bail if the user being edited doesn't have a VALS role, or is the same as the current user.
			if ( ! $vals_user || ! array_intersect( $this->roles, (array) $vals_user->roles ) || $user->ID === $vals_user->ID ) {
				return $caps;
			}

			$caps[0] = 'edit_users';
		}

		return $caps;
	}
}
