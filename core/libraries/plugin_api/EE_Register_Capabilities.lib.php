<?php
/**
 * This file contains the EE_Register_Capabilities class that implements EEI_Plugin_API
 * @package      Event Espresso
 * @subpackage plugin api, capabilities
 * @since           4.5.0
 */
if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');

/**
 * Use this to register new capabilities for the EE capabilities system.
 *
 * @package        Event Espresso
 * @subpackage  plugin api, capabilities
 * @since            4.5.0
 * @author          Darren Ethier
 */
class EE_Register_Capabilities implements EEI_Plugin_API {

	/**
	 * Holds the settings for a specific registration.
	 *
	 * @var array
	 */
	protected static $_registry = array();



	/**
	 * Used to register capability items with EE core.
	 *
	 * @since 4.5.0
	 *
	 * @param string $cap_reference usually will be a class name that references capability related items setup for something.
	 * @param array  $setup_args    {
	 *                              An array of items related to registering capabilities.
	 *                              @type array $capabilities 	An array mapping capability strings to core WP Role.  Something like array( 'administrator' => array( 'read_cap', 'edit_cap', 'delete_cap'), 'author' => array( 'read_cap' ) ).
	 *                              @type array $capability_maps EE_Meta_Capability_Map[]   @see EE_Capabilities.php for php docs on these objects.
	 * }
	 *
	 * @return void
	 */
	public static function register( $cap_reference, $setup_args = array() ) {
		//required fields MUST be present, so let's make sure they are.
		if ( ! isset( $cap_reference ) || ! is_array( $setup_args ) || empty( $setup_args['capabilities'] ) ) {
			throw new EE_Error(
				__( 'In order to register capabilities with EE_Register_Capabilities::register, you must include a unique name to reference the capabilities being registered, plus an array containing the following keys: "capabilities".', 'event_espresso' )
			);
		}

		//make sure this is not registered too late
		if ( did_action( 'AHEE__EE_System__core_loaded_and_ready' ) ) {
			EE_Error::doing_it_wrong( __METHOD__, sprintf( __('%s has been registered too late.  Please ensure that EE_Register_Capabilities::register has been called at some point before the "AHEE__EE_System__core_loaded_and_ready" action hook has been called.', 'event_espresso'), $cap_reference ), '4.5.0' );
		}

		//some preliminary sanitization and setting to the $_registry property

		self::$_registry[$cap_reference] = array(
			'caps' => isset( $setup_args['capabilities'] ) && is_array( $setup_args['capabilities'] ) ? $setup_args['capabilities'] : array(),
			'cap_maps' => isset( $setup_args['capability_maps'] ) && reset( $setup_args['capability_maps'] ) instanceof EE_Meta_Capability_Map ? $setup_args['capability_maps'] : array()
			);


		//set initial caps (note that EE_Capabilities takes care of making sure that the caps get added donly once)
		if ( ! empty( self::$_registry['caps'] ) ) {
			EE_Registry::instance()->CAP->init_role_caps( FALSE, self::$_registry['caps'] );
		}

		//set maps if present
		if ( ! empty( self::$_registry['cap_maps'] ) ) {
			add_filter( 'FHEE__EE_Capabilities___set_meta_caps__meta_caps', array( 'EE_Register_Capabilities', 'register_cap_maps' ), 10 );
		}
	}



	/**
	 * Callback for the 'FHEE__EE_Capabilities___set_meta_caps__meta_caps' filter which regsiters an array of capability maps for the WP meta_caps filter called in EE_Capabilities.
	 *
	 * @since 4.5.0
	 *
	 * @param EE_Meta_Capability_Map[] $cap_maps The existing cap maps array.
	 *
	 * @return EE_Meta_Capability_Map[]
	 */
	public static function register_cap_maps( $cap_maps ) {
		return array_merge( $cap_maps, self::$_registry['cap_maps'] );
	}




	public static function deregister( $cap_reference = NULL ) {
		if ( !empty( self::$_registry[$cap_reference] ) )
    		unset( self::$_registry[$cap_reference] );
	}
}
