<?php
require_once INCLUDE_DIR . 'class.plugin.php';
require_once INCLUDE_DIR . 'class.translation.php';

class ResponderPluginConfig extends PluginConfig {

  private function days(){
    return [
        0 => __('Sunday'),
        1 => __('Monday'),
        2 => __('Tuesday'),
        3 => __('Wednesday'),
        4 => __('Thursday'),
        5 => __('Friday'),
        6 => __('Saturday')
    ];
  }

  function pre_save(&$config, &$errors) {
    foreach ($this->days() as $day => $day_name) {
      $times = $config['day-' . $day];
      if (strpos($times, '-') === FALSE || strlen($times) !== 9) {
        $errors['err'] = __(
          'Invalid time format, use 0000-0000 format for ' . $day_name);
        return FALSE;
      }
      list ($start, $end) = explode('-', $times);
      if ($start == '0000' && $end == '0000') {
        // peachy.
      }
      if ((int) $end < (int) $start) {
        $errors['err'] = __(
          "Invalid end time, can't be before start for $day_name.");
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
    $day_configs['sender'] = new TextboxField(
      [
          'label' => __('Sender Username'),
          'default' => '1',
          'hint' => __(
            'Enter the username/id/email of Agent who will be sending the messages.')
      ]);
    foreach ($this->days() as $day => $day_name) {
      $day_configs['day-' . $day] = new TextboxField(
        [
            'label' => $day_name,
            'hint' => __(
              'Enter hours open only, in 24hr hyphenated format, or leave as 0000-0000 for closed all day. '),
            'default' => ($day == 0 || $day == 6) ? '0000-0000' : '0900-1700',
            'configuration' => [
                'html' => FALSE,
                'size' => 10,
                'length' => 9
            ]
        ]);
    }
    return $day_configs;
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
