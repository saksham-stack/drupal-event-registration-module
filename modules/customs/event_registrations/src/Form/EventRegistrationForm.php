<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\TimeInterface;

class EventRegistrationForm extends FormBase {

  protected Connection $database;
  protected TimeInterface $time;

  public function __construct(Connection $database, TimeInterface $time) {
    $this->database = $database;
    $this->time = $time;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('datetime.time')
    );
  }

  public function getFormId() {
    return 'event_registration_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      // Get all active events with their details
      $query = $this->database->select('event_registration_event', 'e');
      $query->fields('e', ['id', 'event_name', 'category', 'event_date']);
      $query->condition('e.status', 1); // Only active events
      $query->condition('e.registration_start', time(), '<=');
      $query->condition('e.registration_end', time(), '>=');
      $query->orderBy('e.category');
      $query->orderBy('e.event_date');
      $query->orderBy('e.event_name');
      
      $result = $query->execute();
      $events = [];
      
      foreach ($result as $record) {
        // Format the date appropriately
        if (is_numeric($record->event_date)) {
          // If it's a numeric timestamp
          $formatted_date = date('M j, Y', (int)$record->event_date);
        } else {
          // If it's a datetime string
          $timestamp = strtotime($record->event_date);
          $formatted_date = $timestamp ? date('M j, Y', $timestamp) : $record->event_date;
        }
        
        // Create a combined label with category, event name, and date
        $combined_label = $record->category . ' - ' . $record->event_name . ' (' . $formatted_date . ')';
        $events[$record->id] = $combined_label;
      }
      
      if (empty($events)) {
        $form['no_events'] = [
          '#markup' => '<p>No events are currently available for registration.</p>',
        ];
        return $form;
      }
      
      $form['event_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Event'),
        '#required' => TRUE,
        '#options' => $events,
        '#empty_option' => $this->t('- Select an Event -'),
      ];

      $form['full_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Full Name'),
        '#required' => TRUE,
      ];

      $form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#required' => TRUE,
      ];

      $form['college'] = [
        '#type' => 'textfield',
        '#title' => $this->t('College'),
        '#required' => TRUE,
      ];

      $form['department'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Department'),
        '#required' => TRUE,
      ];

      $form['actions'] = [
        '#type' => 'actions',
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Register'),
      ];

    } catch (\Exception $e) {
      // Log the error and show a user-friendly message
      \Drupal::logger('event_registration')->error('Error loading events: @message', ['@message' => $e->getMessage()]);
      $form['error_message'] = [
        '#markup' => '<div class="messages messages--error">Unable to load events. Please try again later.</div>',
      ];
    }

    return $form;
  }

  /* ---------- VALIDATION ---------- */

  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      $event_id = $form_state->getValue('event_id');
      $full_name = trim($form_state->getValue('full_name'));
      $email = $form_state->getValue('email');
      $college = trim($form_state->getValue('college'));
      $department = trim($form_state->getValue('department'));
      
      // Validate event selection
      if (empty($event_id)) {
        $form_state->setErrorByName('event_id', $this->t('Please select an event.'));
      } else {
        // Verify the event exists and registration is open
        $event = $this->database->select('event_registration_event', 'e')
          ->fields('e', ['id', 'registration_start', 'registration_end', 'status'])
          ->condition('id', $event_id)
          ->execute()
          ->fetchAssoc();
        
        if (!$event || $event['status'] != 1) {
          $form_state->setErrorByName('event_id', $this->t('Selected event is not available.'));
        } elseif (time() < $event['registration_start'] || time() > $event['registration_end']) {
          $form_state->setErrorByName('event_id', $this->t('Registration for this event is not currently open.'));
        }
      }
      
      // Validate full name
      if (empty($full_name)) {
        $form_state->setErrorByName('full_name', $this->t('Full name is required.'));
      } elseif (strlen($full_name) < 2) {
        $form_state->setErrorByName('full_name', $this->t('Full name must be at least 2 characters long.'));
      }
      
      // Validate email
      if (empty($email)) {
        $form_state->setErrorByName('email', $this->t('Email is required.'));
      } elseif (!\Drupal::service('email.validator')->isValid($email)) {
        $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
      }
      
      // Check for duplicate registration
      if (!empty($email) && !empty($event_id)) {
        $existing_registration = $this->database->select('event_registration_entry', 'ere')
          ->fields('ere', ['id'])
          ->condition('event_id', $event_id)
          ->condition('email', $email)
          ->execute()
          ->fetchField();
        
        if ($existing_registration) {
          $form_state->setErrorByName('email', $this->t('You are already registered for this event.'));
        }
      }
      
      // Validate college
      if (empty($college)) {
        $form_state->setErrorByName('college', $this->t('College is required.'));
      }
      
      // Validate department
      if (empty($department)) {
        $form_state->setErrorByName('department', $this->t('Department is required.'));
      }
    } catch (\Exception $e) {
      \Drupal::logger('event_registration')->error('Validation error: @message', ['@message' => $e->getMessage()]);
      $form_state->setErrorByName('', $this->t('An error occurred during validation. Please try again.'));
    }
  }

  /* ---------- SUBMIT ---------- */

public function submitForm(array &$form, FormStateInterface $form_state) {
  try {
    $event_id = $form_state->getValue('event_id');
    
    // Validate the event exists - only selecting columns that exist in your database
    $event_exists = $this->database->select('event_registration_event', 'e')
      ->fields('e', ['id', 'event_name', 'event_date', 'category'])  // Removed 'location' since it doesn't exist
      ->condition('id', $event_id)
      ->condition('status', 1)
      ->condition('registration_start', time(), '<=')
      ->condition('registration_end', time(), '>=')
      ->execute()
      ->fetchAssoc();
    
    if (!$event_exists) {
      $this->messenger()->addError($this->t('Invalid event selected.'));
      return;
    }
    
    // Check if max attendees limit exists and if we've reached it
    try {
      $event_data = $this->database->select('event_registration_event', 'e')
        ->fields('e', ['max_attendees'])
        ->condition('id', $event_id)
        ->execute()
        ->fetchAssoc();
      
      if (!empty($event_data['max_attendees'])) {
        $current_registrations = $this->database->select('event_registration_entry', 'er')
          ->countQuery()
          ->condition('event_id', $event_id)
          ->execute()
          ->fetchField();
          
        if ($current_registrations >= $event_data['max_attendees']) {
          $this->messenger()->addError($this->t('Sorry, this event has reached its maximum capacity.'));
          return;
        }
      }
    } catch (\Exception $e) {
      // If max_attendees column doesn't exist, just continue without the check
    }

    // Insert the registration
    $entry_id = $this->database->insert('event_registration_entry')
      ->fields([
        'event_id' => $event_id,
        'full_name' => $form_state->getValue('full_name'),
        'email' => $form_state->getValue('email'),
        'college' => $form_state->getValue('college'),
        'department' => $form_state->getValue('department'),
        'created' => time(),
      ])
      ->execute();

    // For now, skip email sending until cache is cleared
    // After clearing cache, you can uncomment the email code below
    $this->messenger()->addStatus($this->t('Registration successful.'));

    /*
    // Send confirmation email to user
    try {
      $mail_service = \Drupal::service('event_registration.mail_service');
      $registrant_info = [
        'full_name' => $form_state->getValue('full_name'),
        'email' => $form_state->getValue('email'),
        'college' => $form_state->getValue('college'),
        'department' => $form_state->getValue('department'),
      ];
      
      // Update the event details array to only include columns that exist in your database
      $event_details_for_email = [
        'title' => $event_exists['event_name'],
        'event_date' => $event_exists['event_date'],
        'location' => '', // Set to empty since location column doesn't exist
        'category' => $event_exists['category'],
      ];
      
      $mail_service->sendConfirmationEmail(
        $form_state->getValue('email'),
        $form_state->getValue('full_name'),
        $event_details_for_email
      );
      
      // Send notification to admin
      $mail_service->sendAdminNotification($registrant_info, $event_details_for_email);
      
      $this->messenger()->addStatus($this->t('Registration successful. A confirmation email has been sent to your email address.'));
    } catch (\Exception $e) {
      // If email service fails, still complete the registration but log the error
      \Drupal::logger('event_registration')->warning('Email service failed: @message', ['@message' => $e->getMessage()]);
      $this->messenger()->addStatus($this->t('Registration successful.'));
    }
    */
  } catch (\Exception $e) {
    \Drupal::logger('event_registration')->error('Registration error: @message', ['@message' => $e->getMessage()]);
    $this->messenger()->addError($this->t('An error occurred during registration. Please try again.'));
  }
}

}