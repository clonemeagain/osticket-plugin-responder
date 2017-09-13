<?php
require_once INCLUDE_DIR . 'class.plugin.php';
require_once INCLUDE_DIR . 'class.translation.php';

class ResponderPluginConfig extends PluginConfig {

  const days = [
      0 => __('Sunday'),
      1 => __('Monday'),
      2 => __('Tuesday'),
      3 => __('Wednesday'),
      4 => __('Thursday'),
      5 => __('Friday'),
      6 => __('Saturday')
  ];

  function pre_save($config, &$errors) {
    foreach (self::days as $day => $day_name) {
      list ($start, $end) = explode('-', $config['day-' . $day]);
      if ($start == '0000' && $end == '0000') {
        // peachy.
      }
      if ((int) $end > (int) $start) {
        $errors['err'] = __("Invalid end time, can't be before start.");
        return FALSE;
      }
      //TODO: Come up with better validators.
    }
    return TRUE;
  }

  /**
   * Build an Admin settings page.
   *
   * {@inheritdoc}
   *
   * @see PluginConfig::getOptions()
   */
  function getOptions() {

    // Get all the canned responses to use as responses (might want to just use a textbox)
    $responses = Canned::getCannedResponses();
    $responses['0'] = __('Send no Reply');

    $day_configs['response'] = new ChoiceField(
      [
          'label' => __('Auto-Reply Canned Response'),
          'hint' => __(
            'Select a canned response to use as a reply when the office is closed, configure in /scp/canned.php'),
          'choices' => $responses
      ]);
    $day_configs['sender'] = new ChoiceField(
      [
          'label' => __('Sender Username'),
          'default' => 0,
          'hint' => __(
            'Enter the username/id/email of Agent who will be sending the messages.')
      ]);
    foreach (self::days as $day => $day_name) {
      $day_configs['day-' . $day] = new TextboxField(
        [
            'label' => $day_name,
            'hint' => __(
              'Enter hours open only, in 24hr hyphenated format, or leave as 0000-0000 for closed all day. '),
            'default' => ($day !== 0 || $day !== 6) ? '0900-1700' : '0000-0000',
            'configuration' => [
                'html' => FALSE,
                'size' => 100,
                'length' => 100
            ]
        ]);
    }
    return $day_configs;
  }
}