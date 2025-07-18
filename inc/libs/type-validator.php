<?php
/**
 * Type Validator & Converter Class
 *
 * Usage Examples:
 * 
 * Basic validation with type strings:
 * $data = array( 'age' => '25', 'name' => 'John' );
 * $rules = array( 'age' => 'int', 'name' => 'string' );
 * $result = WORRPB_Type_Validator::validate( $data, $rules );
 * // Returns: array( 'age' => 25, 'name' => 'John' )
 *
 * Advanced validation with rule arrays:
 * $data = array( 'count' => '10' );
 * $rules = array(
 *     'count' => array( 'type' => 'int', 'required' => true ),
 *     'status' => array( 'type' => 'bool', 'default' => false )
 * );
 * $result = WORRPB_Type_Validator::validate( $data, $rules );
 * // Returns: array( 'count' => 10, 'status' => false )
 *
 * Single value validation:
 * $validated = WORRPB_Type_Validator::validate_single( '123', 'int' );
 * // Returns: 123
 *
 * Quick type checking:
 * if ( WORRPB_Type_Validator::is_supported_type( 'string' ) ) {
 *     // Type is supported
 * }
 *
 * Supported types: int, integer, string, bool, boolean, array
 * 
 * @author: @Mike
 * @version: 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Type Validator Class
 *
 * Validates and converts data types according to WordPress coding standards.
 *
 * @since 1.0.0
 */
class WORRPB_Type_Validator {

	/**
	 * Supported data types
	 *
	 * @since 1.0.0
	 * @var array
	 */
	const SUPPORTED_TYPES = array( 'int', 'integer', 'string', 'bool', 'boolean', 'array' );

	/**
	 * Validate and convert data types
	 *
	 * @since 1.0.0
	 *
	 * @param array $data  Data to validate.
	 * @param array $rules Rules defining types.
	 * @return array Converted data with correct types.
	 * @throws WORRPB_Validation_Exception When validation fails.
	 */
	public static function validate( $data, $rules ) {
		$instance = new self();
		return $instance->process_validation( $data, $rules );
	}

	/**
	 * Process validation internally
	 *
	 * @since 1.0.0
	 *
	 * @param array $data  Data to validate.
	 * @param array $rules Validation rules.
	 * @return array Processed data.
	 * @throws WORRPB_Validation_Exception When validation fails.
	 */
	private function process_validation( $data, $rules ) {
		$result = array();

		foreach ( $rules as $field => $rule ) {
			try {
				$processed_value = $this->process_field( $data, $field, $rule );
				if ( null !== $processed_value ) {
					$result[ $field ] = $processed_value;
				}
			} catch ( Exception $e ) {
				throw new WORRPB_Validation_Exception(
					sprintf(
						/* translators: %1$s: field name, %2$s: error message */
						__( 'Field "%1$s": %2$s', 'worrpb' ),
						$field,
						$e->getMessage()
					),
					$field
				);
			}
		}

		return $result;
	}

	/**
	 * Process single field
	 *
	 * @since 1.0.0
	 *
	 * @param array  $data  Data array.
	 * @param string $field Field name.
	 * @param mixed  $rule  Validation rule.
	 * @return mixed|null Processed value or null.
	 * @throws WORRPB_Validation_Exception When field validation fails.
	 */
	private function process_field( $data, $field, $rule ) {
		$config = $this->parse_rule( $rule );

		// Check if field exists.
		if ( ! isset( $data[ $field ] ) ) {
			if ( $config['required'] ) {
				throw new WORRPB_Validation_Exception( __( 'Field is required', 'worrpb' ) );
			}
			return $config['default'];
		}

		$value = $data[ $field ];

		// Convert to specified type.
		return $this->convert_to_type( $value, $config['type'] );
	}

	/**
	 * Parse rule configuration
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $rule Rule configuration.
	 * @return array Parsed configuration.
	 * @throws WORRPB_Validation_Exception When rule format is invalid.
	 */
	private function parse_rule( $rule ) {
		if ( is_string( $rule ) ) {
			return array(
				'type'     => $rule,
				'default'  => null,
				'required' => false,
			);
		}

		if ( is_array( $rule ) ) {
			return array(
				'type'     => isset( $rule['type'] ) ? $rule['type'] : 'string',
				'default'  => isset( $rule['default'] ) ? $rule['default'] : null,
				'required' => isset( $rule['required'] ) ? $rule['required'] : false,
			);
		}

		throw new WORRPB_Validation_Exception( __( 'Invalid rule format', 'worrpb' ) );
	}

	/**
	 * Convert value to specified type
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value Value to convert.
	 * @param string $type  Target type.
	 * @return mixed Converted value.
	 * @throws WORRPB_Type_Exception When type is unsupported or conversion fails.
	 */
	private function convert_to_type( $value, $type ) {
		if ( ! in_array( $type, self::SUPPORTED_TYPES, true ) ) {
			throw new WORRPB_Type_Exception(
				sprintf(
					/* translators: %s: type name */
					__( 'Unsupported type: %s', 'worrpb' ),
					$type
				)
			);
		}

		switch ( $type ) {
			case 'int':
			case 'integer':
				return $this->convert_to_int( $value );

			case 'string':
				return $this->convert_to_string( $value );

			case 'bool':
			case 'boolean':
				return $this->convert_to_bool( $value );

			case 'array':
				return $this->convert_to_array( $value );

			default:
				throw new WORRPB_Type_Exception(
					sprintf(
						/* translators: %s: type name */
						__( 'Unknown type: %s', 'worrpb' ),
						$type
					)
				);
		}
	}

	/**
	 * Convert to integer
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to convert.
	 * @return int Converted integer.
	 * @throws WORRPB_Type_Exception When conversion fails.
	 */
	private function convert_to_int( $value ) {
		if ( is_int( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) && is_numeric( $value ) ) {
			return (int) $value;
		}

		if ( is_bool( $value ) ) {
			return $value ? 1 : 0;
		}

		if ( is_float( $value ) ) {
			return (int) $value;
		}

		throw new WORRPB_Type_Exception(
			sprintf(
				/* translators: %s: value type */
				__( 'Cannot convert "%s" to integer', 'worrpb' ),
				gettype( $value )
			)
		);
	}

	/**
	 * Convert to string
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to convert.
	 * @return string Converted string.
	 * @throws WORRPB_Type_Exception When conversion fails.
	 */
	private function convert_to_string( $value ) {
		if ( is_string( $value ) ) {
			return sanitize_text_field( trim( $value ) );
		}

		if ( is_scalar( $value ) ) {
			return sanitize_text_field( (string) $value );
		}

		if ( is_array( $value ) ) {
			return wp_json_encode( $value );
		}

		throw new WORRPB_Type_Exception(
			sprintf(
				/* translators: %s: value type */
				__( 'Cannot convert "%s" to string', 'worrpb' ),
				gettype( $value )
			)
		);
	}

	/**
	 * Convert to boolean
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to convert.
	 * @return bool Converted boolean.
	 */
	private function convert_to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$lower = strtolower( trim( $value ) );
			if ( in_array( $lower, array( 'true', '1', 'yes', 'on' ), true ) ) {
				return true;
			}
			if ( in_array( $lower, array( 'false', '0', 'no', 'off', '' ), true ) ) {
				return false;
			}
		}

		if ( is_numeric( $value ) ) {
			return (bool) $value;
		}

		return (bool) $value;
	}

	/**
	 * Convert to array
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to convert.
	 * @return array Converted array.
	 */
	private function convert_to_array( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		if ( is_string( $value ) ) {
			// Try JSON first.
			$decoded = json_decode( $value, true );
			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
				return array_map( 'sanitize_text_field', $decoded );
			}

			// Split by comma.
			if ( false !== strpos( $value, ',' ) ) {
				return array_map( 'sanitize_text_field', array_map( 'trim', explode( ',', $value ) ) );
			}

			// Single value array.
			return array( sanitize_text_field( $value ) );
		}

		// Convert scalar to array.
		return array( sanitize_text_field( (string) $value ) );
	}

	/**
	 * Validate single value (static helper)
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value   Value to validate.
	 * @param string $type    Target type.
	 * @param array  $options Additional options.
	 * @return mixed Validated value.
	 * @throws WORRPB_Type_Exception When validation fails.
	 */
	public static function validate_single( $value, $type, $options = array() ) {
		$rule   = array_merge( array( 'type' => $type ), $options );
		$result = self::validate( array( 'value' => $value ), array( 'value' => $rule ) );
		return $result['value'];
	}

	/**
	 * Quick validation (static helper)
	 *
	 * @since 1.0.0
	 *
	 * @param array $data  Data to validate.
	 * @param array $types Type definitions.
	 * @return array Validated data.
	 * @throws WORRPB_Validation_Exception When validation fails.
	 */
	public static function quick_validate( $data, $types ) {
		return self::validate( $data, $types );
	}

	/**
	 * Check if type is supported
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Type to check.
	 * @return bool True if supported, false otherwise.
	 */
	public static function is_supported_type( $type ) {
		return in_array( $type, self::SUPPORTED_TYPES, true );
	}

	/**
	 * Get supported types
	 *
	 * @since 1.0.0
	 *
	 * @return array List of supported types.
	 */
	public static function get_supported_types() {
		return self::SUPPORTED_TYPES;
	}
}