<?php
if (!defined('EVENT_ESPRESSO_VERSION'))
	exit('No direct script access allowed');

/**
 * This scenario creates an event that has:
 * - Three Datetimes
 *      - D1 - reg limit 5
 *      - D2 - reg limit 20
 *      - D3 - reg limit 12
 * - Four Tickets
 *      - TA - qty 5 	( D1, D2, D3 )
 *      - TB - qty 5 	( D1, D2 )
 *      - TC - qty 5 	( D1, D3 )
 *      - TD - qty 10 	( D2, D3 )
 *
 *  MAX SELLOUT:
 *        5 TA tickets for D1 ( D1 sold out + TA, TB, & TC sold out )
 *        10 TD tickets for D3 ( TD sold out )
 *        ( D2 + D3 sold out because ALL tickets sold out )
 *
 * @package    Event Espresso
 * @subpackage tests/scenarios
 * @author     Darren Ethier
 */
class EE_Event_Scenario_D extends EE_Test_Scenario {

	public function __construct( EE_UnitTestCase $eetest ) {
		$this->type = 'event';
		$this->name = 'Event Scenario D';
		parent::__construct( $eetest );
	}

	protected function _set_up_expected(){
		$this->_expected_values = array(
			'total_available_spaces' => 15,
			'total_remaining_spaces' => 15
		);
	}


	protected function _set_up_scenario(){
		$event = $this->generate_objects_for_scenario(
			array(
				'Event' => array(
					'EVT_name'  => 'Test Scenario EVT D',
					'Datetime'  => array(
						'DTT_name'      => 'Datetime 1',
						'DTT_reg_limit' => 5,
						'Ticket'        => array(
							'TKT_name' => 'Ticket A',
							'TKT_qty'  => 5,
						),
						'Ticket*'       => array(
							'TKT_name' => 'Ticket B',
							'TKT_qty'  => 5,
						),
						'Ticket**'      => array(
							'TKT_name' => 'Ticket C',
							'TKT_qty'  => 5,
						),
					),
					'Datetime*' => array(
						'DTT_name'      => 'Datetime 2',
						'DTT_reg_limit' => 20,
						'Ticket'        => array(
							'TKT_name' => 'Ticket A',
							'TKT_qty'  => 5,
						),
						'Ticket*'       => array(
							'TKT_name' => 'Ticket B',
							'TKT_qty'  => 5,
						),
						'Ticket**'       => array(
							'TKT_name' => 'Ticket D',
							'TKT_qty'  => 10,
						),
					),
					'Datetime**' => array(
						'DTT_name'      => 'Datetime 3',
						'DTT_reg_limit' => 12,
						'Ticket'        => array(
							'TKT_name' => 'Ticket A',
							'TKT_qty'  => 5,
						),
						'Ticket*'       => array(
							'TKT_name' => 'Ticket C',
							'TKT_qty'  => 5,
						),
						'Ticket**' => array(
							'TKT_name' => 'Ticket D',
							'TKT_qty'  => 10,
						),
					),
				),
			)
		);
		//assign the event object as the scenario object
		$this->_scenario_object = reset( $event );
	}



	protected function _get_scenario_object(){
		return $this->_scenario_object;
	}
}