<?php
/**
 * @file class.ResponderPlugin.php :: Requires PHP5.6+
*  @  requires osTicket 1.17+ & PHP8.0+
 * @  multi-instance: yes
 * @author Grizly <clonemeagain@gmail.com>
 * @see https://github.com/clonemeagain/osticket-plugin-responder
 * @fork by HairyDuck 
 * @see https://github.com/HairyDuck/osticket-plugin-responder
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
  const DEBUG = FALSE;

  /**
   * Keeps all log entries for each run
   * for output to syslog
   *
   * @var array
   */
  private $LOG = array();

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

    // ---------------------------------------------------------------------
    // Fetch the config
    // ---------------------------------------------------------------------
    // Save config and instance for use later in the signal, when it is called
    $config = $this->getConfig();
    $instance = $this->getConfig()->instance;

    // Listen for ticket created Signal
    Signal::connect('ticket.created',
      function ($ticket) {
        if (self::DEBUG) {
          error_log("Signal received.");
        }
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
    if ($config->get('response') == 0) {
      if (self::DEBUG) {
        error_log("Configured with no response.");
      }
      return FALSE;
    }

    // eg: 1902, or 912 or 2344
    $time = Misc::dbtime();
    $now = (int) date('Hi', $time);
    $day_of_week = date('w', $time);

    // Parse the configuration for today:
    list ($start, $end) = explode('-', $config->get('day-' . $day_of_week));

    if (self::DEBUG) {
      error_log("Testing today $now against start: $start and end: $end");
    }

    // Compare the exact string values stored, if both zeros, the whole day we're shut
    if ($start == '0000' && $end == '0000') {
      return TRUE;
    }

    // Make em numbers, for comparisons
    $start = (int) $start;
    // If they put ????-0000, assume they mean ????-2359
    if ($end == '0000') {
      $end = '2359';
    }
    $end = (int) $end;

    // Check that now is outside the start and end times
    if ($now < $start || $now > $end) {
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
    $reply = Canned::lookup($config->get('response'));

    // We need to override this for the notifications
    global $thisstaff;

    // Should be nothing for cron or user ticket creations
    $current_staff = $thisstaff;

    // This actually bypasses any authentication/validation checks..
    $thisstaff = $robot;

    // Replace any ticket variables in the message:
//     $variables = [
//         'recipient' => $ticket->getOwner()
//     ];

//     $vars = [
//         'response' => $ticket->replaceVars($reply, $variables)
//     ];
//     $errors = [];
    $msg = $ticket->getThreadId();

    // Send the alert without claiming the ticket on our assignee's behalf.
    if (! $sent = $ticket->postCannedReply($reply,$msg,true)) {
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
  function uninstall(&$errors) {
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

  /**
   * New function to get the configuration instance.
   */
  function getConfig() {
    if (!$this->config) {
        $this->config = new ResponderConfig($this->getId());
    }
    return $this->config;
  }
}
