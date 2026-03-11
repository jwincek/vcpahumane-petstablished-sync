<?php
/**
 * Petstablished Admin - Settings & Sync UI
 *
 * @package Petstablished_Sync
 * @since 2.0.0
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Petstablished_Admin {

	public const OPTION_NAME = 'petstablished_sync_settings';
	public const PAGE_SLUG   = 'petstablished-sync';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PETSTABLISHED_SYNC_FILE ), array( $this, 'add_settings_link' ) );
	}

	public function add_menu(): void {
		add_submenu_page(
			'edit.php?post_type=pet',
			__( 'Petstablished Sync', 'petstablished-sync' ),
			__( 'Sync Settings', 'petstablished-sync' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings(): void {
		register_setting( self::PAGE_SLUG, self::OPTION_NAME, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
			'default'           => self::get_defaults(),
		) );

		// API Settings Section.
		add_settings_section(
			'api_settings',
			__( 'API Configuration', 'petstablished-sync' ),
			fn() => printf( '<p>%s</p>', esc_html__( 'Connect to your Petstablished account.', 'petstablished-sync' ) ),
			self::PAGE_SLUG
		);

		add_settings_field( 'public_key', __( 'Public Key', 'petstablished-sync' ), array( $this, 'render_public_key_field' ), self::PAGE_SLUG, 'api_settings' );

		// Sync Settings Section.
		add_settings_section(
			'sync_settings',
			__( 'Sync Options', 'petstablished-sync' ),
			fn() => printf( '<p>%s</p>', esc_html__( 'Configure automatic synchronization.', 'petstablished-sync' ) ),
			self::PAGE_SLUG
		);

		add_settings_field( 'auto_sync', __( 'Auto Sync', 'petstablished-sync' ), array( $this, 'render_auto_sync_field' ), self::PAGE_SLUG, 'sync_settings' );
		add_settings_field( 'sync_interval', __( 'Sync Interval', 'petstablished-sync' ), array( $this, 'render_sync_interval_field' ), self::PAGE_SLUG, 'sync_settings' );
		add_settings_field( 'batch_size', __( 'Batch Size', 'petstablished-sync' ), array( $this, 'render_batch_size_field' ), self::PAGE_SLUG, 'sync_settings' );
	}

	public static function get_defaults(): array {
		return array(
			'public_key'    => '',
			'auto_sync'     => true,
			'sync_interval' => 'hourly',
			'batch_size'    => 10,
		);
	}

	public static function get_settings(): array {
		return wp_parse_args( get_option( self::OPTION_NAME, array() ), self::get_defaults() );
	}

	public function sanitize_settings( $input ): array {
		$sanitized = array();
		$sanitized['public_key']    = sanitize_text_field( $input['public_key'] ?? '' );
		$sanitized['auto_sync']     = ! empty( $input['auto_sync'] );
		$sanitized['sync_interval'] = in_array( $input['sync_interval'] ?? '', array( 'hourly', 'twicedaily', 'daily' ), true )
			? $input['sync_interval'] : 'hourly';
		$sanitized['batch_size']    = absint( $input['batch_size'] ?? 10 );
		$sanitized['batch_size']    = max( 1, min( 50, $sanitized['batch_size'] ) );

		// Reschedule cron if auto_sync changed.
		$old = self::get_settings();
		if ( $sanitized['auto_sync'] !== $old['auto_sync'] || $sanitized['sync_interval'] !== $old['sync_interval'] ) {
			wp_clear_scheduled_hook( 'petstablished_scheduled_sync' );
			if ( $sanitized['auto_sync'] ) {
				wp_schedule_event( time(), $sanitized['sync_interval'], 'petstablished_scheduled_sync' );
			}
		}

		return $sanitized;
	}

	// Field Renderers.

	public function render_public_key_field(): void {
		$settings = self::get_settings();
		printf(
			'<input type="text" name="%s[public_key]" value="%s" class="regular-text">
			<p class="description">%s</p>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $settings['public_key'] ),
			esc_html__( 'Your Petstablished public key (found in account settings).', 'petstablished-sync' )
		);
	}

	public function render_auto_sync_field(): void {
		$settings = self::get_settings();
		printf(
			'<label><input type="checkbox" name="%s[auto_sync]" value="1" %s> %s</label>',
			esc_attr( self::OPTION_NAME ),
			checked( $settings['auto_sync'], true, false ),
			esc_html__( 'Automatically sync pets on a schedule', 'petstablished-sync' )
		);
	}

	public function render_sync_interval_field(): void {
		$settings = self::get_settings();
		$intervals = array(
			'hourly'     => __( 'Hourly', 'petstablished-sync' ),
			'twicedaily' => __( 'Twice Daily', 'petstablished-sync' ),
			'daily'      => __( 'Daily', 'petstablished-sync' ),
		);
		echo '<select name="' . esc_attr( self::OPTION_NAME ) . '[sync_interval]">';
		foreach ( $intervals as $value => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( $settings['sync_interval'], $value, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	public function render_batch_size_field(): void {
		$settings = self::get_settings();
		printf(
			'<input type="number" name="%s[batch_size]" value="%d" min="1" max="50" class="small-text">
			<p class="description">%s</p>',
			esc_attr( self::OPTION_NAME ),
			$settings['batch_size'],
			esc_html__( 'Number of pets to process per batch (1-50). Lower values for shared hosting.', 'petstablished-sync' )
		);
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings   = self::get_settings();
		$last_sync  = get_option( 'petstablished_last_sync' );
		$sync_stats = get_option( 'petstablished_last_sync_stats', array() );
		$is_syncing = get_transient( 'petstablished_sync_in_progress' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Petstablished Sync', 'petstablished-sync' ); ?></h1>

			<!-- Sync Status Card -->
			<div class="card" style="max-width: 600px; margin-bottom: 20px;">
				<h2><?php esc_html_e( 'Sync Status', 'petstablished-sync' ); ?></h2>
				
				<div id="sync-status">
					<?php if ( $last_sync ) : ?>
						<p>
							<strong><?php esc_html_e( 'Last sync:', 'petstablished-sync' ); ?></strong>
							<?php echo esc_html( human_time_diff( $last_sync ) . ' ' . __( 'ago', 'petstablished-sync' ) ); ?>
							<br>
							<small><?php echo esc_html( wp_date( 'F j, Y g:i a', $last_sync ) ); ?></small>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'No sync has been performed yet.', 'petstablished-sync' ); ?></p>
					<?php endif; ?>
				</div>

				<!-- Progress Bar (hidden by default) -->
				<div id="sync-progress" style="display: none; margin: 15px 0;">
					<div style="background: #e0e0e0; border-radius: 4px; overflow: hidden;">
						<div id="sync-progress-bar" style="background: #0073aa; height: 20px; width: 0%; transition: width 0.3s;"></div>
					</div>
					<p id="sync-progress-text" style="margin: 5px 0; font-size: 13px;"></p>
				</div>

				<!-- Results (shown after sync) -->
				<div id="sync-results" style="display: none; margin: 15px 0; padding: 10px; background: #d4edda; border-radius: 4px;">
				</div>

				<p>
					<button type="button" id="sync-button" class="button button-primary" <?php echo empty( $settings['public_key'] ) ? 'disabled' : ''; ?>>
						<?php esc_html_e( 'Sync Now', 'petstablished-sync' ); ?>
					</button>
					
					<?php
					$pet_count = wp_count_posts( 'pet' );
					$total     = ( $pet_count->publish ?? 0 ) + ( $pet_count->draft ?? 0 );
					?>
					<span id="pet-count" style="margin-left: 10px;">
						<?php printf( esc_html__( '%d pets in database', 'petstablished-sync' ), $total ); ?>
					</span>
				</p>

				<?php if ( empty( $settings['public_key'] ) ) : ?>
					<p class="description" style="color: #d63638;">
						<?php esc_html_e( 'Please enter your Public Key below to enable syncing.', 'petstablished-sync' ); ?>
					</p>
				<?php endif; ?>
			</div>

			<!-- Settings Form -->
			<form method="post" action="options.php">
				<?php
				settings_fields( self::PAGE_SLUG );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>

		<script>
		(function() {
			const syncButton = document.getElementById('sync-button');
			const progressDiv = document.getElementById('sync-progress');
			const progressBar = document.getElementById('sync-progress-bar');
			const progressText = document.getElementById('sync-progress-text');
			const resultsDiv = document.getElementById('sync-results');
			const statusDiv = document.getElementById('sync-status');

			let totalPets = 0;
			let processed = 0;
			let batchSize = <?php echo (int) $settings['batch_size']; ?>;
			let stats = { created: 0, updated: 0, unchanged: 0 };

			syncButton.addEventListener('click', startSync);

			async function startSync() {
				syncButton.disabled = true;
				syncButton.textContent = '<?php esc_html_e( 'Starting...', 'petstablished-sync' ); ?>';
				progressDiv.style.display = 'block';
				resultsDiv.style.display = 'none';
				progressBar.style.width = '0%';
				progressText.textContent = '<?php esc_html_e( 'Fetching pets from Petstablished...', 'petstablished-sync' ); ?>';

				try {
					const startRes = await fetch(ajaxurl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({
							action: 'petstablished_start_sync',
							nonce: '<?php echo wp_create_nonce( 'petstablished_sync' ); ?>'
						})
					});
					const startData = await startRes.json();

					if (!startData.success) {
						throw new Error(startData.data || 'Failed to start sync');
					}

					totalPets = startData.data.total;
					batchSize = startData.data.batchSize;
					processed = 0;
					stats = { created: 0, updated: 0, unchanged: 0 };

					progressText.textContent = `<?php esc_html_e( 'Found', 'petstablished-sync' ); ?> ${totalPets} <?php esc_html_e( 'pets. Processing...', 'petstablished-sync' ); ?>`;

					// Process in batches
					while (processed < totalPets) {
						await processBatch();
					}

					// Finish sync
					await finishSync();

				} catch (error) {
					progressText.textContent = '<?php esc_html_e( 'Error:', 'petstablished-sync' ); ?> ' + error.message;
					progressText.style.color = '#d63638';
				}

				syncButton.disabled = false;
				syncButton.textContent = '<?php esc_html_e( 'Sync Now', 'petstablished-sync' ); ?>';
			}

			async function processBatch() {
				const res = await fetch(ajaxurl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({
						action: 'petstablished_process_batch',
						nonce: '<?php echo wp_create_nonce( 'petstablished_sync' ); ?>',
						offset: processed,
						limit: batchSize
					})
				});
				const data = await res.json();

				if (!data.success) {
					throw new Error(data.data || 'Batch processing failed');
				}

				processed += data.data.processed;
				stats.created += data.data.stats.created || 0;
				stats.updated += data.data.stats.updated || 0;
				stats.unchanged += data.data.stats.unchanged || 0;

				const percent = Math.round((processed / totalPets) * 100);
				progressBar.style.width = percent + '%';
				progressText.textContent = `<?php esc_html_e( 'Processing:', 'petstablished-sync' ); ?> ${processed} / ${totalPets} (${percent}%)`;
			}

			async function finishSync() {
				progressText.textContent = '<?php esc_html_e( 'Finishing up...', 'petstablished-sync' ); ?>';

				const res = await fetch(ajaxurl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({
						action: 'petstablished_finish_sync',
						nonce: '<?php echo wp_create_nonce( 'petstablished_sync' ); ?>'
					})
				});
				const data = await res.json();

				progressBar.style.width = '100%';
				progressDiv.style.display = 'none';

				resultsDiv.innerHTML = `
					<strong><?php esc_html_e( 'Sync Complete!', 'petstablished-sync' ); ?></strong><br>
					<?php esc_html_e( 'Created:', 'petstablished-sync' ); ?> ${stats.created} |
					<?php esc_html_e( 'Updated:', 'petstablished-sync' ); ?> ${stats.updated} |
					<?php esc_html_e( 'Unchanged:', 'petstablished-sync' ); ?> ${stats.unchanged}
					${data.data?.removed ? ` | <?php esc_html_e( 'Removed:', 'petstablished-sync' ); ?> ${data.data.removed}` : ''}
				`;
				resultsDiv.style.display = 'block';

				// Update status text
				statusDiv.innerHTML = '<p><strong><?php esc_html_e( 'Last sync:', 'petstablished-sync' ); ?></strong> <?php esc_html_e( 'Just now', 'petstablished-sync' ); ?></p>';
			}
		})();
		</script>
		<?php
	}

	public function enqueue_assets( $hook ): void {
		if ( ! str_contains( $hook, self::PAGE_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'petstablished-admin',
			PETSTABLISHED_SYNC_URL . 'assets/css/admin.css',
			array(),
			PETSTABLISHED_SYNC_VERSION
		);
	}

	public function display_notices(): void {
		if ( ! isset( $_GET['petstablished_sync'] ) ) {
			return;
		}

		$status  = sanitize_text_field( $_GET['petstablished_sync'] );
		$message = '';
		$type    = 'success';

		switch ( $status ) {
			case 'started':
				$message = __( 'Sync started. This may take a few minutes.', 'petstablished-sync' );
				$type    = 'info';
				break;
			case 'complete':
				$message = __( 'Sync completed successfully.', 'petstablished-sync' );
				break;
			case 'error':
				$message = __( 'Sync failed. Please check your API credentials.', 'petstablished-sync' );
				$type    = 'error';
				break;
		}

		if ( $message ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), esc_html( $message ) );
		}
	}

	public function add_settings_link( $links ): array {
		$url  = admin_url( 'edit.php?post_type=pet&page=' . self::PAGE_SLUG );
		$link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'petstablished-sync' ) );
		array_unshift( $links, $link );
		return $links;
	}
}
