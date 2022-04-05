<?php

/**
 * Handles log entries by writing to database.
 * 
 * @since 1.0.0
 *
 * @class BT_EDD_Log_Handler
 */

class BT_EDD_Log_Handler {

	/**
	 * Log Levels
	 *
	 * Description of levels:.
	 *     'error': Error conditions.
	 *     'warning': Warning conditions.
	 *     'notice': Normal but significant condition.
	 *     'info': Informational messages.
	 *
	 * @see @link {https://tools.ietf.org/html/rfc5424}
     * 
     * @since 1.0.0
	 */
	const ERROR   = 'error';
	const WARNING = 'warning';
	const NOTICE  = 'notice';
	const INFO    = 'info';

	/**
	 * Level strings mapped to integer severity.
     * 
     * @since 1.0.0
	 *
	 * @var array
	 */
	protected static $level_to_severity = array(
		self::ERROR   => 500,
		self::WARNING => 400,
		self::NOTICE  => 300,
		self::INFO    => 200,
	);

	/**
	 * Severity integers mapped to level strings.
	 *
	 * This is the inverse of $level_severity.
     * 
     * @since 1.0.0
	 *
	 * @var array
	 */
	protected static $severity_to_level = array(
		500 => self::ERROR,
		400 => self::WARNING,
		300 => self::NOTICE,
		200 => self::INFO,
	);

	/**
	 * Constructor for the logger.
     * 
     * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Prepares logging functionalty if enabled
     * 
     * @since 1.0.0
	 *
	 * @access public
	 * @return void
	 */
	public function init() {

		//if ( bt_edd_mailjet()->settings->get( 'enable_logging' ) != true ) {
			//return;
		//}
		add_action( 'admin_menu', array( $this, 'register_logger_subpage' ) );
		// Screen options
		add_action( 'load-tools_page_bt-edd-settings-logs', array( $this, 'add_screen_options' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
		// Error handling
		add_action( 'shutdown', array( $this, 'shutdown' ) );

		$this->create_update_table();

	}

	/**
	 * Adds per-page screen option
	 *
	 * @since 1.0.0
	 * 
	 * @access public
	 * @return void
	 */
	public function add_screen_options() {

		$args = array(
			'label'   => __( 'Entries per page', 'bt-edd-mailjet' ),
			'default' => 20,
			'option'  => 'bt_edd_status_log_items_per_page',
		);

		add_screen_option( 'per_page', $args );

	}

	/**
	 * Save screen options
	 *
	 * @since 1.0.0
	 * 
	 * @access public
	 * @return int Value
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'bt_edd_status_log_items_per_page' == $option ) {
			return $value;
		}
		return $status;
	}
	
	/**
	 * Adds standalone log management page
	 *
	 * @since 1.0.0
	 * 
	 * @access public
	 * @return void
	 */
	public function register_logger_subpage() {

		$page = add_submenu_page(
			'tools.php',
			'EDD Mailjet Activity Logs',
			'EDD Mailjet Activity Logs',
			'manage_options',
			'bt-edd-settings-logs',
			array( $this, 'show_logs_section' )
		);

	}

	/**
	 * Logging page content
	 * 
	 * @since 1.0.0
	 *
	 * @access public
	 * @return void
	 */
	public function show_logs_section() {

		include_once BT_EDD_MAILJET_PLUGIN_INCLUDES . '/logging/class-log-table-list.php';

		// Flush
		if ( ! empty( $_REQUEST['flush-logs'] ) ) {
			self::flush();
		}

		// Bulk actions
		if ( isset( $_REQUEST['action'] ) && isset( $_REQUEST['log'] ) ) {
			self::log_table_bulk_actions();
		}

		$log_table_list = new BT_EDD_Log_Table_List();
		$log_table_list->prepare_items();

		// Stop _wp_http_referer getting appended to the logs URL, so it doesn't get too long
		add_filter(
			'removable_query_args', function( $query_args ) {

				$query_args[] = '_wp_http_referer';
				return $query_args;

			}
		);

		?>

		<div class="wrap">
			<h1><?php _e( 'EDD Mailjet Activity Log', 'bt-edd-mailjet' ); ?></h1>

			<form method="get" id="mainform">

				<input type="hidden" name="page" value="bt-edd-settings-logs">

				<?php $log_table_list->display(); ?>

				<?php submit_button( __( 'Flush all logs', 'bt-edd-mailjet' ), 'delete', 'flush-logs' ); ?>
				<?php wp_nonce_field( 'bt-edd-mailjet-status-logs' ); ?>

			</form>
		</div>

		<?php

	}

	/**
	 * Bulk DB log table actions.
	 *
	 * @since 1.0.0
	 */
	private function log_table_bulk_actions() {

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bt-edd-mailjet-status-logs' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'bt-edd-mailjet' ) );
		}

		$log_ids = array_map( 'absint', (array) $_REQUEST['log'] );

		if ( 'delete' === $_REQUEST['action'] || 'delete' === $_REQUEST['action2'] ) {
			self::delete( $log_ids );
		}
	}

	/**
	 * Delete selected logs from DB.
	 * 
	 * @since 1.0.0
	 *
	 * @param int|string|array Log ID or array of Log IDs to be deleted.
	 *
	 * @return bool
	 */
	public static function delete( $log_ids ) {
		global $wpdb;

		if ( ! is_array( $log_ids ) ) {
			$log_ids = array( $log_ids );
		}

		$format = array_fill( 0, count( $log_ids ), '%d' );

		$query_in = '(' . implode( ',', $format ) . ')';

		$query = $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}bt_edd_logging WHERE log_id IN {$query_in}",
			$log_ids
		);

		return $wpdb->query( $query );
	}

    /**
	 * Creates logging table if logging enabled
     * 
     * @since 1.0.0
	 *
	 * @access public
	 * @return void
	 */
	public function create_update_table() {

		global $wpdb;
		$table_name = $wpdb->prefix . 'bt_edd_logging';

		if ( $wpdb->get_var( "show tables like '$table_name'" ) != $table_name ) {

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$sql = 'CREATE TABLE ' . $table_name . " (
				log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				timestamp datetime NOT NULL,
				level smallint(4) NOT NULL,
				user bigint(8) NOT NULL,
				source varchar(200) NOT NULL,
				message longtext NOT NULL,
				context longtext NULL,
				PRIMARY KEY (log_id),
				KEY level (level)
			) $collate;";

			dbDelta( $sql );

		}

    }
    
    /**
	 * Check for PHP errors on shutdown and log them
     * 
     * @since 1.0.0
	 *
	 * @access public
	 * @return void
	 */
	public function shutdown() {
		$error = error_get_last();
		if ( is_null( $error ) ) {
			return;
		}
		if ( false !== strpos( $error['file'], 'bt-edd-mailjet' ) || false !== strpos( $error['message'], 'bt-edd-mailjet' ) ) {
			if ( E_ERROR == $error['type'] || E_WARNING == $error['type'] ) {
				// Get the source
				$source = 'unknown';
				$slugs = array( 'class-actions', 'class-mailjet', 'class-eddmailjet-control', 'class-user');
				foreach ( $slugs as $slug ) {
					if ( empty( $slug ) ) {
						continue;
					}
					if ( strpos( $error['file'], $slug ) !== false ) {
						$source = $slug;
						break;
					}
				}
				if ( E_ERROR == $error['type'] ) {
					$level = 'error';
				} elseif ( E_WARNING == $error['type'] ) {
					$level = 'warning';
				}
				$this->handle( $level, get_current_user_id(), '<strong>PHP error:</strong> ' . nl2br( $error['message'] ) . '<br /><br />' . $error['file'] . ':' . $error['line'], array( 'source' => $source ) );
			}
		}
    }
	
	/**
	 * Validate a level string.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $level
	 * @return bool True if $level is a valid level.
	 */
	public static function is_valid_level( $level ) {
		return array_key_exists( strtolower( $level ), self::$level_to_severity );
	}

	/**
	 * Translate level string to integer.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @return int 100 (debug) - 800 (emergency) or 0 if not recognized
	 */
	public static function get_level_severity( $level ) {
		if ( self::is_valid_level( $level ) ) {
			$severity = self::$level_to_severity[ strtolower( $level ) ];
		} else {
			$severity = 0;
		}
		return $severity;
	}

	/**
	 * Translate severity integer to level string.
	 *
	 * @since 1.0.0
	 * 
	 * @param int $severity
	 * @return bool|string False if not recognized. Otherwise string representation of level.
	 */
	public static function get_severity_level( $severity ) {
		if ( array_key_exists( $severity, self::$severity_to_level ) ) {
			return self::$severity_to_level[ $severity ];
		} else {
			return false;
		}
	}

    /**
	 * Handle a log entry.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param array  $context {
	 *      Additional information for log handlers.
	 *
	 *     @type string $source Optional. Source will be available in log table.
	 *                  If no source is provided, attempt to provide sensible default.
	 * }
	 *
	 * @see BT_EDD_Log_Handler::get_log_source() for default source.
     * 
     * @since 1.0.0
	 *
	 * @return bool False if value was not handled and true if value was handled.
	 */
	public function handle( $level, $user, $message, $context = array() ) {

		$timestamp = current_time( 'timestamp' );
		do_action( 'bt_edd_handle_log', $timestamp, $level, $user, $message, $context );
        /*
 		if ( bt_edd_mailjet()->settings->get( 'enable_logging' ) != true ) {
			return;
		}

		if ( bt_edd_mailjet()->settings->get( 'logging_errors_only' ) == true && $level != 'error' ) {
			return;
		} */

		if ( isset( $context['source'] ) && $context['source'] ) {
			$source = $context['source'];
		} else {
			$source = $this->get_log_source();
		}
		if ( empty( $user ) ) {
			$user = 0;
		}
		do_action( 'bt_edd_log_handled', $timestamp, $level, $user, $message, $source, $context );
		return $this->add( $timestamp, $level, $user, $message, $source, $context );
    }
    
    /**
	 * Get appropriate source based on file name.
	 *
	 * Try to provide an appropriate source in case none is provided.
     * 
     * @since 1.0.0
	 *
	 * @return string Text to use as log source. "" (empty string) if none is found.
	 */
	protected static function get_log_source() {

		static $ignore_files = array( 'class-log-handler' );
		/**
		 * PHP < 5.3.6 correct behavior
		 *
		 * @see http://php.net/manual/en/function.debug-backtrace.php#refsect1-function.debug-backtrace-parameters
		 */
		if ( defined( 'DEBUG_BACKTRACE_IGNORE_ARGS' ) ) {
			$debug_backtrace_arg = DEBUG_BACKTRACE_IGNORE_ARGS;
		} else {
			$debug_backtrace_arg = false;
		}
		$full_trace = debug_backtrace( $debug_backtrace_arg );
		$slugs = array( 'class-actions', 'class-mailjet', 'class-eddmailjet-control', 'class-user');
		$found_sources = array();
		foreach ( $full_trace as $i => $trace ) {
			if ( isset( $trace['file'] ) ) {
				foreach ( $slugs as $slug ) {
					if ( empty( $slug ) ) {
						continue;
					}
					if ( strpos( $trace['file'], $slug ) !== false ) {
						$found_sources[] = $slug;
					}
				}
			}
		}
		// Figure out most likely integration
		if ( ! empty( $found_sources ) ) {
			$source = serialize( array_reverse( array_unique( $found_sources ) ) );
		} else {
			$source = 'unknown';
		}
		return $source;
    }
    
    /**
	 * Add a log entry to chosen file.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param string $source Log source. Useful for filtering and sorting.
	 * @param array  $context {
	 *      Context will be serialized and stored in database.
	 *  }
     * 
     * @since 1.0.0
	 *
	 * @return bool True if write was successful.
	 */
	protected static function add( $timestamp, $level, $user, $message, $source, $context ) {
		global $wpdb;
		$insert = array(
			'timestamp' => date( 'Y-m-d H:i:s', $timestamp ),
			'level'     => self::get_level_severity( $level ),
			'user'      => $user,
			'message'   => $message,
			'source'    => $source,
		);
		$format = array(
			'%s',
			'%d',
			'%d',
			'%s',
			'%s',
			'%s', // possible serialized context
		);
		if ( ! empty( $context ) ) {
			$insert['context'] = serialize( $context );
		}
		$result = $wpdb->insert( "{$wpdb->prefix}bt_edd_logging", $insert, $format );
		if ( $result === false ) {
			return false;
		}
		$rowcount = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bt_edd_logging" );
		$max_log_size = apply_filters( 'bt_edd_log_max_entries', 10000 );
		if ( $rowcount > $max_log_size ) {
			$wpdb->query( "DELETE FROM {$wpdb->prefix}bt_edd_logging ORDER BY log_id ASC LIMIT 1" );
		}
		return $result;
    }
    
    /**
	 * Clear all logs from the DB.
     * 
     * @since 1.0.0
	 *
	 * @return bool True if flush was successful.
	 */
	public static function flush() {
		global $wpdb;
		return $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}bt_edd_logging" );
	}
}