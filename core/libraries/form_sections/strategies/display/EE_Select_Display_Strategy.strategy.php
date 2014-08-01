<?php
/**
 * displays either simple arrays as selected, or if a 2d array is provided, seperates them
 * into optgroups
 */
class EE_Select_Display_Strategy extends EE_Display_Strategy_Base{
	/**
	 *
	 * @return string of html to display the field
	 */
	function display(){
		EE_Registry::instance()->load_helper('Formatter');
		$input = $this->_input;
		if( ! $input instanceof EE_Form_Input_With_Options_Base){
			throw new EE_Error(sprintf(__("Cannot use Select Display Strategy with an input that doesn't ahve options", "event_espresso")));
		}
		$class = $this->_input->required() ? 'ee-needs-value ' . $input->html_class() : $input->html_class();
		$html= EEH_Formatter::nl() . "<select id='{$input->html_id()}' name='{$input->html_name()}' class='{$class}' style='{$input->html_style()}'/>" ;
		EE_Registry::instance()->load_helper('Array');
		EEH_Formatter::increase_indent( 1 );
		if(EEH_Array::is_multi_dimensional_array($input->options())){
			foreach($input->options() as $opt_group_label => $opt_group){
				$opt_group_label = esc_attr($opt_group_label);
				$html.=EEH_Formatter::nl() . "<optgroup label='{$opt_group_label}'>";
				EEH_Formatter::increase_indent( 1 );
				$html.=$this->_display_options($opt_group);
				$html.=EEH_Formatter::nl( -1 ) . "</optgroup>";
			}
		}else{
			$html.=$this->_display_options($input->options());
		}

		$html.= EEH_Formatter::nl(-1) . "</select>";
		return $html;
	}
	/**
	 * Displays a falt list of options as option tags
	 * @param type $options
	 * @return string
	 */
	protected function _display_options($options){

		$html = '';
		foreach($options as $value => $display_text){
			if($this->_check_if_option_selected($value)){
				$selected_attr = 'selected="selected"';
			}else{
				$selected_attr ='';
			}
			$value_in_form = esc_attr( $this->_input->get_normalization_strategy()->unnormalize( $value ) );
			$html.= EEH_Formatter::nl() . "<option value='$value_in_form' $selected_attr>$display_text</option>";
		}
		return $html;
	}
	/**
	 * Checks if that value is the one selected
	 * @param string|int $value
	 * @return boolean
	 */
	protected function _check_if_option_selected($value){
		$equality = ($this->_input->normalized_value() == $value);
		return $equality;
	}
}