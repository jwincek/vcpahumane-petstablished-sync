<?php
/**
 * Config-contract validator for Pet Sync for Petstablished.
 *
 * Static analysis — no WordPress, no Composer dependencies. It cross-checks
 * the JSON config (config/*.json) against the PHP that consumes it, catching
 * the config-drift bug classes a config-driven plugin is most exposed to:
 *
 *   1. config-path  — every Config::get_path()/get_item() string literal
 *                     resolves to a real key (would have caught the
 *                     entities.pet -> entities.vcps_pet rename regression).
 *   2. computed     — entities.json `computed` keys <-> Pet_Hydrator::
 *                     compute_field() match arms (a declared computed field
 *                     with no arm silently hydrates to null).
 *   3. profiles     — every name in summary/grid/comparison_fields resolves
 *                     to a real base/taxonomy/field/api_field/computed field.
 *   4. abilities    — every abilities.json ability has a resolvable callback
 *                     and a known permission; no dead callback mappings.
 *   5. taxonomies   — entity taxonomies + attribute_map reference real
 *                     taxonomies / api_field keys.
 *   6. interactivity (heuristic) — actions.X / callbacks.X referenced in
 *                     block render.php are defined in a view.js / store.
 *
 * Usage:
 *   php bin/validate-config.php [--format=human|json]
 *
 * Exit code: 1 if any ERROR-level issue is found (warnings do not fail CI).
 *
 * @package Petstablished_Sync
 */

declare( strict_types = 1 );

$root   = dirname( __DIR__ );
$format = 'human';
foreach ( array_slice( $argv, 1 ) as $arg ) {
	if ( str_starts_with( $arg, '--format=' ) ) {
		$format = substr( $arg, strlen( '--format=' ) );
	}
}

/** Collected issues: each ['level' => 'error'|'warning', 'check' => string, 'message' => string]. */
$issues = [];

/** Record an issue. */
$add = static function ( string $level, string $check, string $message ) use ( &$issues ): void {
	$issues[] = compact( 'level', 'check', 'message' );
};

/** Load + decode a config JSON file (no $ref resolution — only top-level keys are checked). */
$load_json = static function ( string $rel ) use ( $root, $add ): ?array {
	$path = $root . '/' . $rel;
	if ( ! is_file( $path ) ) {
		$add( 'error', 'config', "Missing config file: $rel" );
		return null;
	}
	$data = json_decode( (string) file_get_contents( $path ), true );
	if ( ! is_array( $data ) ) {
		$add( 'error', 'config', "Invalid JSON in $rel: " . json_last_error_msg() );
		return null;
	}
	return $data;
};

/** Read a PHP/JS source file as text. */
$read = static function ( string $rel ) use ( $root ): string {
	$path = $root . '/' . $rel;
	return is_file( $path ) ? (string) file_get_contents( $path ) : '';
};

// ── Load configs ────────────────────────────────────────────────────────────
$entities_json   = $load_json( 'config/entities.json' );
$abilities_json  = $load_json( 'config/abilities.json' );
$taxonomies_json = $load_json( 'config/taxonomies.json' );
$posttypes_json  = $load_json( 'config/post-types.json' );

$entity_key = 'vcps_pet';
$entity     = $entities_json['entities'][ $entity_key ] ?? null;
if ( null === $entity ) {
	$add( 'error', 'config', "entities.json has no '$entity_key' entity (key renamed?)." );
}

// ── Check 1: Config::get_path()/get_item() string literals resolve ───────────
$php_files = [];
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/includes', FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $f ) {
	if ( $f->getExtension() === 'php' ) {
		$php_files[] = $f->getPathname();
	}
}
$php_files[] = $root . '/petstablished-sync.php';

foreach ( $php_files as $path ) {
	$src = (string) file_get_contents( $path );
	$rel = str_replace( $root . '/', '', $path );

	// Config::get_item( 'name', 'key' )
	if ( preg_match_all( "/Config::get_item\(\s*'([^']+)'\s*,\s*'([^']+)'/", $src, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $hit ) {
			$cfg = $load_json( "config/{$hit[1]}.json" );
			if ( is_array( $cfg ) && ! array_key_exists( $hit[2], $cfg ) ) {
				$add( 'error', 'config-path', "$rel: Config::get_item('{$hit[1]}', '{$hit[2]}') — key '{$hit[2]}' not in {$hit[1]}.json" );
			}
		}
	}

	// Config::get_path( 'name', 'dot.path' )
	if ( preg_match_all( "/Config::get_path\(\s*'([^']+)'\s*,\s*'([^']+)'/", $src, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $hit ) {
			$cfg = $load_json( "config/{$hit[1]}.json" );
			if ( ! is_array( $cfg ) ) {
				continue;
			}
			$node    = $cfg;
			$ok      = true;
			foreach ( explode( '.', $hit[2] ) as $seg ) {
				if ( ! is_array( $node ) || ! array_key_exists( $seg, $node ) ) {
					$ok = false;
					break;
				}
				$node = $node[ $seg ];
			}
			if ( ! $ok ) {
				$add( 'error', 'config-path', "$rel: Config::get_path('{$hit[1]}', '{$hit[2]}') — path does not resolve in {$hit[1]}.json" );
			}
		}
	}
}

// ── Build the valid field-name universe for this entity ──────────────────────
$base_fields    = [ 'id', 'name' ]; // Always emitted by Pet_Hydrator::hydrate().
$tax_keys       = array_keys( $entity['taxonomies'] ?? [] );
$field_keys     = array_keys( $entity['fields'] ?? [] );
$api_field_keys = array_keys( $entity['api_fields'] ?? [] );
$computed_keys  = array_keys( $entity['computed'] ?? [] );
$valid_fields   = array_merge( $base_fields, $tax_keys, $field_keys, $api_field_keys, $computed_keys );

// ── Check 2: computed dispatch coverage (entities.computed <-> compute_field) ─
$hydrator = $read( 'includes/core/class-pet-hydrator.php' );
$match_arms = [];
if ( preg_match( '/function compute_field\(.*?match\s*\(\s*\$name\s*\)\s*\{(.*?)\n\t\t\};/s', $hydrator, $mm ) ) {
	preg_match_all( "/'([a-z0-9_]+)'\s*=>/i", $mm[1], $am );
	$match_arms = $am[1];
} else {
	$add( 'warning', 'computed', 'Could not locate Pet_Hydrator::compute_field() match block to verify dispatch.' );
}
foreach ( $computed_keys as $ck ) {
	if ( $match_arms && ! in_array( $ck, $match_arms, true ) ) {
		$add( 'error', 'computed', "computed field '$ck' is declared in entities.json but has no arm in compute_field() — it will hydrate to null." );
	}
}
foreach ( $match_arms as $arm ) {
	if ( ! in_array( $arm, $computed_keys, true ) ) {
		$add( 'warning', 'computed', "compute_field() has a match arm '$arm' with no entities.json `computed` declaration — dead/unreachable via hydration." );
	}
}

// ── Check 3: profile field-list integrity ────────────────────────────────────
foreach ( [ 'summary_fields', 'grid_fields', 'comparison_fields' ] as $list ) {
	foreach ( (array) ( $entity[ $list ] ?? [] ) as $name ) {
		if ( ! in_array( $name, $valid_fields, true ) ) {
			$add( 'error', 'profiles', "$list references '$name', which is not a declared base/taxonomy/field/api_field/computed field." );
		}
	}
}

// ── Check 4: ability callbacks + permissions ─────────────────────────────────
$ability_names = array_keys( $abilities_json['abilities'] ?? [] );
$provider      = $read( 'includes/abilities/class-provider.php' );

// Parse the resolve_callback() $explicit_map (name => FQN).
$explicit_map = [];
if ( preg_match( '/\$explicit_map\s*=\s*\[(.*?)\];/s', $provider, $em ) ) {
	if ( preg_match_all( "/'([^']+)'\s*=>\s*'([^']+)'/", $em[1], $pairs, PREG_SET_ORDER ) ) {
		foreach ( $pairs as $p ) {
			$explicit_map[ $p[1] ] = str_replace( '\\\\', '\\', $p[2] );
		}
	}
}

// Collect every function FQN defined in the ability files.
$defined_fns = [];
foreach ( glob( $root . '/includes/abilities/*.php' ) as $af ) {
	$src = (string) file_get_contents( $af );
	if ( ! preg_match( '/namespace\s+([^;]+);/', $src, $nm ) ) {
		continue;
	}
	$ns = trim( $nm[1] );
	if ( preg_match_all( '/^\s*function\s+(\w+)\s*\(/m', $src, $fm ) ) {
		foreach ( $fm[1] as $fn ) {
			$defined_fns[ $ns . '\\' . $fn ] = true;
		}
	}
}

$known_perms = [ 'public', 'logged_in', 'public_with_session', 'admin', 'manage_options', 'edit_posts' ];
foreach ( $ability_names as $name ) {
	if ( ! isset( $explicit_map[ $name ] ) ) {
		$add( 'error', 'abilities', "ability '$name' has no callback mapping in Provider::resolve_callback() \$explicit_map — it will not register." );
	} elseif ( ! isset( $defined_fns[ $explicit_map[ $name ] ] ) ) {
		$add( 'error', 'abilities', "ability '$name' maps to {$explicit_map[$name]}() which is not defined in includes/abilities/." );
	}
	$perm = $abilities_json['abilities'][ $name ]['permission'] ?? 'public';
	if ( is_string( $perm ) && ! in_array( $perm, $known_perms, true ) ) {
		$add( 'warning', 'abilities', "ability '$name' permission '$perm' is not a known keyword — falls through to current_user_can('$perm')." );
	}
}
// Dead mappings: explicit_map entries with no matching ability.
foreach ( array_keys( $explicit_map ) as $mapped ) {
	if ( ! in_array( $mapped, $ability_names, true ) ) {
		$add( 'warning', 'abilities', "Provider \$explicit_map maps '$mapped' but no such ability exists in abilities.json — dead mapping." );
	}
}

// ── Check 5: taxonomy + attribute_map consistency ────────────────────────────
$registered_tax = array_keys( $taxonomies_json['taxonomies'] ?? [] );
foreach ( (array) ( $entity['taxonomies'] ?? [] ) as $key => $cfg ) {
	$tax = $cfg['taxonomy'] ?? null;
	if ( $tax && ! in_array( $tax, $registered_tax, true ) ) {
		$add( 'error', 'taxonomies', "entity taxonomy '$key' => '$tax' is not registered in taxonomies.json." );
	}
}
$attr_tax = $entity['attribute_taxonomy'] ?? null;
if ( $attr_tax && ! in_array( $attr_tax, $registered_tax, true ) ) {
	$add( 'error', 'taxonomies', "attribute_taxonomy '$attr_tax' is not registered in taxonomies.json." );
}
$api_keys = [];
foreach ( (array) ( $entity['api_fields'] ?? [] ) as $cfg ) {
	if ( isset( $cfg['api_key'] ) ) {
		$api_keys[] = $cfg['api_key'];
	}
}
foreach ( array_keys( (array) ( $entity['attribute_map'] ?? [] ) ) as $src_key ) {
	if ( ! in_array( $src_key, $api_keys, true ) ) {
		$add( 'warning', 'taxonomies', "attribute_map source key '$src_key' is not an api_field api_key — it can only match raw API data." );
	}
}

// ── Check 6 (heuristic): interactivity action/callback references resolve ─────
$ref_names = [];
foreach ( array_merge(
	glob( $root . '/blocks/*/render.php' ),
	glob( $root . '/blocks/*/partials/*.php' ),
	glob( $root . '/blocks/*/template-default.php' )
) as $pf ) {
	$src = (string) file_get_contents( $pf );
	if ( preg_match_all( '/(?:actions|callbacks)\.([a-zA-Z_]\w*)/', $src, $rm ) ) {
		foreach ( $rm[1] as $n ) {
			$ref_names[ $n ][] = str_replace( $root . '/', '', $pf );
		}
	}
}
$defined_methods = [];
foreach ( array_merge(
	glob( $root . '/assets/js/store.js' ),
	glob( $root . '/assets/js/interactivity/*.js' ),
	glob( $root . '/blocks/*/view.js' )
) as $jf ) {
	$src = (string) file_get_contents( $jf );
	// Method shorthand: `name( args ) {` or `*name() {`.
	if ( preg_match_all( '/^\s*\*?\s*([a-zA-Z_]\w*)\s*\([^)]*\)\s*\{/m', $src, $dm ) ) {
		foreach ( $dm[1] as $n ) {
			$defined_methods[ $n ] = true;
		}
	}
	// Property form: `name: function`, `name: async ...`, `name: (` (arrow),
	// or `name: wrapper(` (e.g. `navigateToPage: withSyncEvent( function* ...)`).
	if ( preg_match_all( '/([a-zA-Z_]\w*)\s*:\s*(?:async\s+)?(?:function\b|[a-zA-Z_$][\w$]*\s*\(|\()/', $src, $dm2 ) ) {
		foreach ( $dm2[1] as $n ) {
			$defined_methods[ $n ] = true;
		}
	}
}
$js_keywords = [ 'if', 'for', 'while', 'switch', 'catch', 'function', 'return' ];
foreach ( $ref_names as $name => $where ) {
	if ( ! isset( $defined_methods[ $name ] ) && ! in_array( $name, $js_keywords, true ) ) {
		$add( 'warning', 'interactivity', "actions/callbacks.$name is referenced in " . implode( ', ', array_unique( $where ) ) . ' but no matching method is defined in any store/view.js.' );
	}
}

// ── Report ───────────────────────────────────────────────────────────────────
$errors   = array_filter( $issues, static fn( $i ) => $i['level'] === 'error' );
$warnings = array_filter( $issues, static fn( $i ) => $i['level'] === 'warning' );

if ( $format === 'json' ) {
	echo json_encode( [
		'ok'       => count( $errors ) === 0,
		'errors'   => count( $errors ),
		'warnings' => count( $warnings ),
		'issues'   => array_values( $issues ),
	], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
} else {
	$colors = [ 'error' => "\033[31m", 'warning' => "\033[33m" ];
	$reset  = "\033[0m";
	$tty    = function_exists( 'posix_isatty' ) ? @posix_isatty( STDOUT ) : false;
	if ( empty( $issues ) ) {
		echo ( $tty ? "\033[32m" : '' ) . "✓ config validation passed — no issues." . ( $tty ? $reset : '' ) . "\n";
	} else {
		foreach ( $issues as $i ) {
			$tag = strtoupper( $i['level'] );
			$c   = $tty ? ( $colors[ $i['level'] ] ?? '' ) : '';
			$r   = $tty ? $reset : '';
			echo "{$c}[{$tag}]{$r} ({$i['check']}) {$i['message']}\n";
		}
		echo "\n" . count( $errors ) . " error(s), " . count( $warnings ) . " warning(s).\n";
	}
}

exit( count( $errors ) > 0 ? 1 : 0 );
