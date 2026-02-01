<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

class MailService {
  
  protected $mailManager;
  protected $configFactory;
  protected $logger;
  protected $dateFormatter;

  public function __construct(
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    DateFormatterInterface $date_formatter
  ) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('event_registration');
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Send registration confirmation email to user
   */
  public function sendConfirmationEmail($recipient_email, $recipient_name, $event_details) {
    $params = [
      'recipient_name' => $recipient_name,
      'event_title' => $event_details['title'],
      'event_date' => $this->dateFormatter->format($event_details['event_date'], 'custom', 'F j, Y'),
      'event_location' => $event_details['location'] ?? 'TBD',
      'event_category' => $event_details['category'] ?? 'N/A',
    ];

    $module = 'event_registration';
    $key = 'registration_confirmation';
    $to = $recipient_email;
    $langcode = 'en';

    $result = $this->mailManager->mail($module, $key, $to, $langcode, $params);

    if ($result['result']) {
      $this->logger->info('Registration confirmation email sent to @email for event @event.', [
        '@email' => $recipient_email,
        '@event' => $event_details['title']
      ]);
    } else {
      $this->logger->error('Failed to send registration confirmation email to @email for event @event.', [
        '@email' => $recipient_email,
        '@event' => $event_details['title']
      ]);
    }
  }

  /**
   * Send notification email to admin
   */
  public function sendAdminNotification($registrant_info, $event_details) {
    $admin_email = $this->configFactory->get('event_registration.settings')->get('admin_email') ?: \Drupal::config('system.site')->get('mail');
    
    if (empty($admin_email)) {
      $this->logger->warning('No admin email configured for event registration notifications.');
      return;
    }

    $params = [
      'registrant_name' => $registrant_info['full_name'],
      'registrant_email' => $registrant_info['email'],
      'registrant_college' => $registrant_info['college'],
      'registrant_department' => $registrant_info['department'],
      'event_title' => $event_details['title'],
      'event_date' => $this->dateFormatter->format($event_details['event_date'], 'custom', 'F j, Y'),
      'registration_time' => $this->dateFormatter->format(time(), 'custom', 'F j, Y \a\t g:i A'),
    ];

    $module = 'event_registration';
    $key = 'admin_notification';
    $to = $admin_email;
    $langcode = 'en';

    $result = $this->mailManager->mail($module, $key, $to, $langcode, $params);

    if ($result['result']) {
      $this->logger->info('Admin notification email sent for registration of @name to event @event.', [
        '@name' => $registrant_info['full_name'],
        '@event' => $event_details['title']
      ]);
    } else {
      $this->logger->error('Failed to send admin notification email for registration of @name to event @event.', [
        '@name' => $registrant_info['full_name'],
        '@event' => $event_details['title']
      ]);
    }
  }
}