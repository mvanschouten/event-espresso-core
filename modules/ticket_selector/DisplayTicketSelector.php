<?php
namespace EventEspresso\modules\ticket_selector;

if ( ! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}



/**
 * Class DisplayTicketSelector
 * Description
 *
 * @package       Event Espresso
 * @subpackage    core
 * @author        Brent Christensen
 * @since         $VID:$
 */
class DisplayTicketSelector
{

    /**
     * event that ticket selector is being generated for
     *
     * @access protected
     * @var \EE_Event $event
     */
    protected $event;

    /**
     * Used to flag when the ticket selector is being called from an external iframe.
     *
     * @var bool $iframe
     */
    protected $iframe = false;



    /**
     * @param boolean $iframe
     */
    public function setIframe($iframe = true)
    {
        $this->iframe = filter_var($iframe, FILTER_VALIDATE_BOOLEAN);
    }



    /**
     *    finds and sets the \EE_Event object for use throughout class
     *
     * @access    public
     * @param    mixed $event
     * @return    bool
     */
    protected function setEvent($event = null)
    {
        if ($event === null) {
            global $post;
            $event = $post;
        }
        if ($event instanceof \EE_Event) {
            $this->event = $event;
        } else if ($event instanceof \WP_Post) {
            if (isset($event->EE_Event) && $event->EE_Event instanceof \EE_Event) {
                $this->event = $event->EE_Event;
            } else if ($event->post_type === 'espresso_events') {
                $event->EE_Event = \EEM_Event::instance()->instantiate_class_from_post_object($event);
                $this->event = $event->EE_Event;
            }
        } else {
            $user_msg = __('No Event object or an invalid Event object was supplied.', 'event_espresso');
            $dev_msg = $user_msg . __(
                    'In order to generate a ticket selector, please ensure you are passing either an EE_Event object or a WP_Post object of the post type "espresso_event" to the EE_Ticket_Selector class constructor.',
                    'event_espresso'
                );
            \EE_Error::add_error($user_msg . '||' . $dev_msg, __FILE__, __FUNCTION__, __LINE__);
            return false;
        }
        return true;
    }



    /**
     *    creates buttons for selecting number of attendees for an event
     *
     * @access    public
     * @param    \WP_Post|int $event
     * @param    bool         $view_details
     * @return    string
     */
    public function display($event = null, $view_details = false)
    {
        // reset filter for displaying submit button
        remove_filter('FHEE__EE_Ticket_Selector__display_ticket_selector_submit', '__return_true');
        // poke and prod incoming event till it tells us what it is
        if ( ! $this->setEvent($event)) {
            return false;
        }
        $event_post = $this->event instanceof \EE_Event ? $this->event->ID() : $event;
        // grab event status
        $_event_active_status = $this->event->get_active_status();
        if (
            ! is_admin()
            && (
                ! $this->event->display_ticket_selector()
                || $view_details
                || post_password_required($event_post)
                || (
                    $_event_active_status !== \EE_Datetime::active
                    && $_event_active_status !== \EE_Datetime::upcoming
                    && $_event_active_status !== \EE_Datetime::sold_out
                    && ! (
                        $_event_active_status === \EE_Datetime::inactive
                        && is_user_logged_in()
                    )
                )
            )
        ) {
            return ! is_single() ? $this->displayViewDetailsButton() : '';
        }
        $template_args = array();
        $template_args['event_status'] = $_event_active_status;
        $template_args['date_format'] = apply_filters(
            'FHEE__EED_Ticket_Selector__display_ticket_selector__date_format',
            get_option('date_format')
        );
        $template_args['time_format'] = apply_filters(
            'FHEE__EED_Ticket_Selector__display_ticket_selector__time_format',
            get_option('time_format')
        );
        $template_args['EVT_ID'] = $this->event->ID();
        $template_args['event'] = $this->event;
        // is the event expired ?
        $template_args['event_is_expired'] = $this->event->is_expired();
        if ($template_args['event_is_expired']) {
            return '<div class="ee-event-expired-notice"><span class="important-notice">' . __(
                'We\'re sorry, but all tickets sales have ended because the event is expired.',
                'event_espresso'
            ) . '</span></div>';
        }
        $ticket_query_args = array(
            array('Datetime.EVT_ID' => $this->event->ID()),
            'order_by' => array(
                'TKT_order'              => 'ASC',
                'TKT_required'           => 'DESC',
                'TKT_start_date'         => 'ASC',
                'TKT_end_date'           => 'ASC',
                'Datetime.DTT_EVT_start' => 'DESC',
            ),
        );
        if ( ! \EE_Registry::instance()->CFG->template_settings->EED_Ticket_Selector->show_expired_tickets) {
            //use the correct applicable time query depending on what version of core is being run.
            $current_time = method_exists('EEM_Datetime', 'current_time_for_query')
                ? time()
                : current_time(
                    'timestamp'
                );
            $ticket_query_args[0]['TKT_end_date'] = array('>', $current_time);
        }
        // get all tickets for this event ordered by the datetime
        $template_args['tickets'] = \EEM_Ticket::instance()->get_all($ticket_query_args);
        if (count($template_args['tickets']) < 1) {
            return '<div class="ee-event-expired-notice"><span class="important-notice">' . __(
                'We\'re sorry, but all ticket sales have ended.',
                'event_espresso'
            ) . '</span></div>';
        }
        // filter the maximum qty that can appear in the Ticket Selector qty dropdowns
        $template_args['max_atndz'] = apply_filters(
            'FHEE__EE_Ticket_Selector__display_ticket_selector__max_tickets',
            $this->event->additional_limit()
        );
        if ($template_args['max_atndz'] < 1) {
            $sales_closed_msg = __(
                'We\'re sorry, but ticket sales have been closed at this time. Please check back again later.',
                'event_espresso'
            );
            if (current_user_can('edit_post', $this->event->ID())) {
                $sales_closed_msg .= sprintf(
                    __(
                        '%sNote to Event Admin:%sThe "Maximum number of tickets allowed per order for this event" in the Event Registration Options has been set to "0". This effectively turns off ticket sales. %s(click to edit this event)%s',
                        'event_espresso'
                    ),
                    '<div class="ee-attention" style="text-align: left;"><b>',
                    '</b><br />',
                    $link = '<span class="edit-link"><a class="post-edit-link" href="' . get_edit_post_link(
                            $this->event->ID()
                        ) . '">',
                    '</a></span></div>'
                );
            }
            return '<p><span class="important-notice">' . $sales_closed_msg . '</span></p>';
        }
        $templates['ticket_selector'] = TICKET_SELECTOR_TEMPLATES_PATH . 'ticket_selector_chart.template.php';
        $templates['ticket_selector'] = apply_filters(
            'FHEE__EE_Ticket_Selector__display_ticket_selector__template_path',
            $templates['ticket_selector'],
            $this->event
        );
        // redirecting to another site for registration ??
        $external_url = $this->event->external_url() !== null || $this->event->external_url() !== ''
            ? $this->event->external_url() : false;
        // set up the form (but not for the admin)
        $ticket_selector = ! is_admin() ? $this->formOpen(
            $this->event->ID(),
            $external_url
        ) : '';
        // if not redirecting to another site for registration
        if ( ! $external_url) {
            \EE_Registry::instance()->load_helper('Template');
            \EE_Registry::instance()->load_helper('URL');
            // then display the ticket selector
            $ticket_selector .= \EEH_Template::locate_template($templates['ticket_selector'], $template_args);
        } else {
            // if not we still need to trigger the display of the submit button
            add_filter('FHEE__EE_Ticket_Selector__display_ticket_selector_submit', '__return_true');
            //display notice to admin that registration is external
            $ticket_selector .= ! is_admin()
                ? ''
                : __(
                    'Registration is at an external URL for this event.',
                    'event_espresso'
                );
        }
        // submit button and form close tag
        $ticket_selector .= ! is_admin() ? $this->displaySubmitButton() : '';
        // set no cache headers and constants
        \EE_System::do_not_cache();
        return $ticket_selector;
    }



    /**
     *    formOpen
     *
     * @access        public
     * @param        int    $ID
     * @param        string $external_url
     * @return        string
     */
    public function formOpen($ID = 0, $external_url = '')
    {
        // if redirecting, we don't need any anything else
        if ($external_url) {
            \EE_Registry::instance()->load_helper('URL');
            $html = '<form method="GET" action="' . \EEH_URL::refactor_url($external_url) . '">';
            $query_args = \EEH_URL::get_query_string($external_url);
            foreach ($query_args as $query_arg => $value) {
                $html .= '<input type="hidden" name="' . $query_arg . '" value="' . $value . '">';
            }
            return $html;
        }
        \EE_Registry::instance()->load_helper('Event_View');
        $checkout_url = \EEH_Event_View::event_link_url($ID);
        if ( ! $checkout_url) {
            \EE_Error::add_error(
                __('The URL for the Event Details page could not be retrieved.', 'event_espresso'),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
        }
        $extra_params = $this->iframe ? ' target="_blank"' : '';
        $html = '<form method="POST" action="' . $checkout_url . '"' . $extra_params . '>';
        $html .= wp_nonce_field('process_ticket_selections', 'process_ticket_selections_nonce', true, false);
        $html .= '<input type="hidden" name="ee" value="process_ticket_selections">';
        $html = apply_filters('FHEE__EE_Ticket_Selector__ticket_selector_form_open__html', $html, $this->event);
        return $html;
    }



    /**
     *    displaySubmitButton
     *
     * @access        public
     * @access        public
     * @return        string
     */
    public function displaySubmitButton()
    {
        if ( ! is_admin()) {
            if (apply_filters('FHEE__EE_Ticket_Selector__display_ticket_selector_submit', false)) {
                $btn_text = apply_filters(
                    'FHEE__EE_Ticket_Selector__display_ticket_selector_submit__btn_text',
                    __('Register Now', 'event_espresso'),
                    $this->event
                );
                $external_url = $this->event->external_url();
                $html = '<input id="ticket-selector-submit-' . $this->event->ID() . '-btn"';
                $html .= ' class="ticket-selector-submit-btn ';
                $html .= empty($external_url) ? 'ticket-selector-submit-ajax"' : '"';
                $html .= ' type="submit" value="' . $btn_text . '" />';
                $html .= apply_filters('FHEE__EE_Ticket_Selector__after_ticket_selector_submit', '', $this->event);
                $html .= '<div class="clear"><br/></div></form>';
                return $html;
            } else if (is_archive()) {
                return $this->formClose()
                       . $this->displayViewDetailsButton();
            }
        }
        return '';
    }



    /**
     *    formClose
     *
     * @access        public
     * @access        public
     * @return        string
     */
    public function formClose()
    {
        return '</form>';
    }



    /**
     *    displayViewDetailsButton
     *
     * @access        public
     * @access        public
     * @return        string
     */
    public function displayViewDetailsButton()
    {
        if ( ! $this->event->get_permalink()) {
            \EE_Error::add_error(
                __('The URL for the Event Details page could not be retrieved.', 'event_espresso'),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
        }
        $view_details_btn = '<form method="POST" action="' . $this->event->get_permalink() . '">';
        $btn_text = apply_filters(
            'FHEE__EE_Ticket_Selector__display_view_details_btn__btn_text',
            __('View Details', 'event_espresso'),
            $this->event
        );
        $view_details_btn .= '<input id="ticket-selector-submit-'
                             . $this->event->ID()
                             . '-btn" class="ticket-selector-submit-btn view-details-btn" type="submit" value="'
                             . $btn_text
                             . '" />';
        $view_details_btn .= apply_filters('FHEE__EE_Ticket_Selector__after_view_details_btn', '', $this->event);
        $view_details_btn .= '<div class="clear"><br/></div>';
        $view_details_btn .= '</form>';
        return $view_details_btn;
    }



    /**
     * @return string
     */
    public static function noTicketSelectorEndDiv()
    {
        return '<div class="clear"></div></div>';
    }


}
// End of file DisplayTicketSelector.php
// Location: /DisplayTicketSelector.php