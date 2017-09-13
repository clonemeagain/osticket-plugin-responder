<?php
/**
 * @file class.ResponderPlugin.php :: Requires PHP5.6+
 *
 * @author Grizly <clonemeagain@gmail.com>
 * @see https://github.com/clonemeagain/osticket-plugin-responder
 */
require_once 'config.php';

/**
 * The goal of this Plugin is to respond to tickets outside of office hours.
 */
class ResponderPlugin extends Plugin {

  var $config_class = 'ResponderPluginConfig';

  /**
   * Set to TRUE to enable extra logging.
   *
   * @var boolean
   */
  const DEBUG = TRUE;

  /**
   * The name that appears in threads
   *
   * @var string
   */
  const PLUGIN_NAME = 'Responder Plugin';

  /**
   * Hook the bootstrap process Run on every instantiation, so needs to be
   * concise.
   *
   * {@inheritdoc}
   *
   * @see Plugin::bootstrap()
   */
  public function bootstrap() {
    // Listen for ticket created Signal
    Signal::connect('ticket.created',
      function ($ticket) {
        // Get the admin config & the date, compare the two.
        $config = $this->getConfig();
        if ($this->is_time_to_run($config)) {
          $this->post_reply($ticket, $config);
        }
      });
  }

  /**
   * Calculates when it's time to run the plugin, based on the config.
   *
   * @param PluginConfig $config
   * @return boolean
   */
  private function is_time_to_run(PluginConfig $config) {
    // eg: 1902, or 912 or 2344
    $now = (int) date('Hi');

    // Parse the configuration for today:
    list ($start, $end) = explode('-', $config->get('day-' . date('w')));

    // Compare the exact string values stored, if both zeros, the whole day we're shut
    if ($start == '0000' && $end == '0000') {
      return TRUE;
    }

    // Make em numbers, for comparisons
    $start = (int) $start;
    // If they put ????-0000, assume they mean ????-2359
    $end = (int) ($end == '0000') ? '2359' : $end;

    // Check that now is between the start and end times
    if ($start < $now && $end > $now) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Sends a reply to the ticket creator Wrapper/customizer around the
   * Ticket::postReply method.
   *
   * @param Ticket $ticket
   * @param TicketStatus $new_status
   * @param string $admin_reply
   */
  private function post_reply(Ticket $ticket, PluginConfig $config) {
    $robot = Staff::lookup($config->get('sender'));
    $reply = Canned::lookup($config->get('response'))->getFormattedResponse(
      'html');

    // We need to override this for the notifications
    global $thisstaff;

    // Should be nothing for cron or user ticket creations
    $current_staff = $thisstaff;

    // This actually bypasses any authentication/validation checks..
    $thisstaff = $robot;

    // Replace any ticket variables in the message:
    $variables = [
        'recipient' => $ticket->getOwner()
    ];

    $vars = [
        'response' => $ticket->replaceVars($reply, $variables)
    ];
    $errors = [];

    // Send the alert without claiming the ticket on our assignee's behalf.
    if (! $sent = $ticket->postReply($vars, $errors, TRUE, FALSE)) {
      $ticket->LogNote(__('Error Notification'),
        __('We were unable to post a reply to the ticket creator.'),
        self::PLUGIN_NAME, FALSE);
    }

    // Restore the staff variable after sending.
    $thisstaff = $current_staff;
  }

  /**
   * Required stub.
   *
   * {@inheritdoc}
   *
   * @see Plugin::uninstall()
   */
  function uninstall() {
    $errors = array();
    global $ost;
    // Send an alert to the system admin:
    $ost->alertAdmin(self::PLUGIN_NAME . ' has been uninstalled',
      "You wanted that right?", true);

    parent::uninstall($errors);
  }

  /**
   * Plugins seem to want this.
   */
  public function getForm() {
    return array();
  }
}