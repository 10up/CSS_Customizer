<?php
/**
 * 10up CSS Customizer
 *
 * Creates a textarea powered by the Ajax.org Cloud9 Editor.  Can be used either in a standalone settings page
 * or in a custom meta box.  Merely specify the scheme when instantiating the object to switch where data is stored.
 */

if ( ! defined( 'TenUp_CSS_URL' ) ) {
	$here = dirname( dirname( __FILE__ ) );
	if ( 0 === strpos( $here, WP_CONTENT_DIR ) ) {
		$here = str_replace( WP_CONTENT_DIR, '', $here );
		$url = WP_CONTENT_URL;
	} elseif ( 0 === strpos( $here, WP_PLUGIN_DIR ) ) {
		$here = str_replace( WP_PLUGIN_DIR, '', $here );
		$url = WP_PLUGIN_URL;
	} elseif ( 0 === strpos( $here, get_theme_root() ) ) {
		$here = str_replace( get_theme_root(), '', $here );
		$url = get_theme_root_uri();
	} else {
		$here = str_replace( substr( ABSPATH, 0, -1 ), '', $here );
		$url = get_option( 'siteurl' );
	}
	$here = str_replace( '\\', '/', $here );

	define( 'TenUp_CSS_URL', $url . $here . '/css-customizer/' );
	unset( $here, $url );
}

if ( ! defined( 'TenUp_CSS_VERSION' ) ) {
	define( 'TenUp_CSS_VERSION', '1.0' );
}

if ( ! class_exists( 'TenUp_CSS_Customizer' ) ) :

	class TenUp_CSS_Customizer {
		/**
		 * @var string Setting name used to store custom CSS.
		 */
		private $handle;

		/**
		 * @var string Scheme used to store data.  Either 'page' or 'meta'.
		 */
		private $scheme;

		/**
		 * @var array Custom settings and localization
		 */
		private $settings;

		public function __construct( $handle = 'tenup_css', $scheme = 'page', $settings = array() ) {
			$this->handle = $handle;
			$this->scheme = $scheme;
			$this->settings = wp_parse_args(
				$settings,
				array(
				     'settings-section-label' => __( 'Custom CSS Settings', 'tenup_css_translate' ),
				     'settings-field-label'   => __( 'Custom CSS', 'tenup_css_translate' ),
				     'options-page-label'     => __( 'Custom CSS Settings', 'tenup_css_translate' ),
				     'metabox-label'          => __( 'Custom CSS', 'tenup_css_translate' ),
				     'metabox-post-types'     => array( 'post' )
				)
			);

			// Wire actions
			add_action( 'init',                        array( $this, 'register_endpoints' ) );
			add_action( 'tenup_css_register_rewrites', array( $this, 'register_endpoints' ) );
			add_action( 'admin_enqueue_scripts',       array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'permalink_structure_changed', array( $this, 'register_endpoints' ), 9 );
			add_action( 'template_redirect',           array( $this, 'custom_css_template_redirect' ) );
			add_action( 'wp_enqueue_scripts',          array( $this, 'enqueue_custom_css' ), 11 );

			// Wire filters
			add_filter( 'tenup_css_customizer_instances', array( $this, 'filter_instances' ) );

			switch( $this->scheme ) {
				case 'meta':
					add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
					add_action( 'save_post',      array( $this, 'save_post' ) );
					break;
				case 'page':
				default:
					// Wire up the admin settings page based on passed parameters
					add_action( 'admin_init', array( $this, 'admin_init' ) );
					add_action( 'admin_menu', array( $this, 'admin_menu' ) );
					break;
			}
		}

		/**
		 * Filter the array of registered CSS Customizer instances, indexed by the name
		 * used to register them.
		 *
		 * @param array $instances
		 *
		 * @return array
		 */
		public function filter_instances( $instances = array() ) {
			$instances[ $this->handle ] = $this;

			return $instances;
		}

		/**
		 * Initialize the various settings sections and fields.
		 */
		public function admin_init() {
			// Register setting and sanitation callback
			register_setting( $this->handle, $this->handle );

			// Add sections
			add_settings_section( $this->handle, $this->settings['settings-section-label'], '__return_null', $this->handle );

			// Add customization fields
			add_settings_field( 'custom_css', $this->settings['settings-field-label'], array( $this, 'custom_css_form' ), $this->handle, $this->handle );
		}

		/**
		 * Actually add the admin menu to the site's navigation.
		 */
		public function admin_menu() {
			add_options_page(
				$this->settings['options-page-label'],
				$this->settings['options-page-label'],
				'manage_options',
				$this->handle,
				array( $this, 'settings_page' )
			);
		}

		/**
		 * Build out the markup for the settings page.
		 */
		public function settings_page() {
			?>
        <div class="wrap">
            <form action="options.php" method="post" id="<?php echo $this->handle; ?>_form" class="tenup_css_form">
                <h2><?php echo $this->settings['options-page-label']; ?></h2>
				<?php settings_fields( $this->handle ); ?>
				<?php do_settings_sections( $this->handle ); ?>
				<?php submit_button(); ?>
            </form>
        </div>
		<?php
		}

		/**
		 * Add the custom meta box to the edit post screen.
		 */
		public function add_meta_boxes() {
			foreach( $this->settings['metabox-post-types'] as $post_type ) {
				add_meta_box(
					$this->handle,
					$this->settings['metabox-label'],
					array( $this, 'custom_css_form' ),
					$post_type,
					'normal',
					'default'
				);
			}
		}

		/**
		 * Save the custom CSS from the meta field.
		 *
		 * @param int $post_id
		 */
		public function save_post( $post_id ) {
			// Verify this isn't an autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Verify this came from our page
			if ( ! isset( $_POST[ "{$this->handle}_nonce" ] ) || ! wp_verify_nonce( $_POST[ "{$this->handle}_nonce" ], $this->handle ) ) {
				return;
			}

			// Verify user has permissions
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$css = $_POST[ $this->handle ];

			$this->save_css( $css, $post_id );
		}

		/**
		 * Custom CSS form.
		 */
		public function custom_css_form() {
			if ( 'meta' === $this->scheme ) {
				global $post;
				$css = $this->get_css( $post->ID );
			} else {
				$css = $this->get_css();
			}

			wp_nonce_field( $this->handle, "{$this->handle}_nonce" );
			echo '<div id="safecss-container"><div id="safecss-ace"></div></div>';
			echo "<textarea name='{$this->handle}' id='{$this->handle}' class='tenup_css hide-if-js'>" . esc_textarea( $css ) . '</textarea>';
		}

		/**
		 * Enqueue the scripts and styles used on the CSS Customization page.
		 *
		 * @param string $hook Name of the hook to filter by before enqueueing scripts and styles.
		 */
		public function admin_enqueue_scripts( $hook ) {
			if ( "settings_page_{$this->handle}" !== $hook && "post.php" !== $hook && "post-new.php" !== $hook ) {
				return;
			}

			wp_enqueue_style( "{$this->handle}_admin", TenUp_CSS_URL . 'css/admin.css', false, TenUp_CSS_VERSION );
			wp_enqueue_script( "{$this->handle}_admin", TenUp_CSS_URL . 'js/admin.js', false, TenUp_CSS_VERSION );
			wp_enqueue_script( 'jquery.spin' );
			wp_enqueue_script( 'safecss-ace', TenUp_CSS_URL . 'js/ace/ace.js', array(), '20121228', true );
			wp_enqueue_script( 'safecss-ace-css', TenUp_CSS_URL . 'js/ace/mode-css.js', array( 'safecss-ace' ), '20121228', true );
			wp_enqueue_script( 'safecss-ace-use', TenUp_CSS_URL . 'js/safecss-ace.js', array( 'jquery', 'safecss-ace-css' ), '20121228', true );
		}

		/**
		 * Register custom rewrite endpoint
		 */
		public function register_endpoints() {
			add_rewrite_tag( "%{$this->handle}%", '(.+)' );
			add_rewrite_tag( "%pid%", '(.+)' );
			add_rewrite_rule( "^{$this->handle}/?$", "index.php?{$this->handle}=1", 'top' );
			add_rewrite_rule( "^{$this->handle}/(.+)/?$", 'index.php?pid=$matches[1]&' . $this->handle . '=1', 'top' );
		}

		/**
		 * Output custom CSS
		 */
		public function custom_css_template_redirect() {
			global $wp_query;

			if ( ! isset( $wp_query->query_vars[$this->handle] ) ) {
				return;
			}

			if ( isset( $wp_query->query_vars['pid'] ) ) {
				$css = $this->get_css( intval( $wp_query->query_vars['pid'] ) );
			} else {
				$css     = $this->get_css();
			}

			$expires = apply_filters( "{$this->handle}_expire", gmdate( 'D, d M Y H:i:s', time() + 31536000 ) . ' GMT' );

			header( 'Content-Type: text/css', true, 200 );
			header( 'Expires: ' . $expires );

			echo apply_filters( "{$this->handle}_output", $css );

			exit;
		}

		/**
		 * Enqueue the custom stylesheet.
		 */
		public function enqueue_custom_css() {
			$css = $this->get_css();

			if ( '' !== $css ) {
				wp_enqueue_style( $this->handle, $this->get_custom_link( $this->handle, home_url() ) );
			}
		}

		/**
		 * Get the stored CSS for this instance from the database.
		 *
		 * @return string
		 */
		private function get_css( $post_id = 0 ) {
			switch( $this->scheme ) {
				case 'meta':
					$css = get_post_meta( $post_id, "_{$this->handle}", true );
					break;
				case 'page':
				default:
					$css = get_option( $this->handle );
					break;
			}

			return $css;
		}

		/**
		 * Store the CSS for this instance in the database.
		 *
		 * @param string $css
		 */
		private function save_css( $css, $post_id = 0 ) {
			switch( $this->scheme ) {
				case 'meta':
					update_post_meta( $post_id, "_{$this->handle}", $css );
					break;
				case 'page':
				default:
					if ( false === get_option( $this->handle ) ) {
						add_option( $this->handle, $css, '', 'no' );
					} else {
						update_option( $this->handle, $css );
					}
					break;
			}
		}

		/**
		 * Generate a custom link based on the home URL.
		 */
		private function get_custom_link( $endpoint = '', $base = '' ) {
			$permalink = $base == '' ? get_option( 'home' ) : $base;

			$structure = get_option( 'permalink_structure' );

			if ( empty( $structure ) ) {
				$new = add_query_arg( $endpoint, 1, $permalink );
			} else {
				$new = trailingslashit( $permalink ) . $endpoint;
			}

			return $new;
		}
	}

endif;
