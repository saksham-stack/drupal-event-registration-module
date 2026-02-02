<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring event registration events.
 */
class EventConfigForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new EventConfigForm.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_event_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter the name of the event.'),
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#required' => TRUE,
      '#options' => [
        '' => $this->t('- Select -'),
        'conference' => $this->t('Conference'),
        'workshop' => $this->t('Workshop'),
        'seminar' => $this->t('Seminar'),
        'meetup' => $this->t('Meetup'),
        'webinar' => $this->t('Webinar'),
        'other' => $this->t('Other'),
      ],
      '#description' => $this->t('Select the event category.'),
    ];

    $form['registration_start_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Registration Start Date'),
      '#required' => TRUE,
      '#description' => $this->t('When registration opens for this event.'),
    ];

    $form['registration_end_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Registration End Date'),
      '#required' => TRUE,
      '#description' => $this->t('When registration closes for this event.'),
    ];

    $form['event_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
      '#description' => $this->t('When the event takes place.'),
    ];

    $form['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location'),
      '#maxlength' => 255,
      '#description' => $this->t('Event location (optional).'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Event description (optional).'),
    ];

    $form['max_attendees'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Attendees'),
      '#min' => 1,
      '#description' => $this->t('Leave empty for unlimited attendees.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Event'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $registration_start = $form_state->getValue('registration_start_date');
    $registration_end = $form_state->getValue('registration_end_date');
    $event_date = $form_state->getValue('event_date');

    if ($registration_start && $registration_end) {
      $start_timestamp = $registration_start->getTimestamp();
      $end_timestamp = $registration_end->getTimestamp();

      if ($end_timestamp <= $start_timestamp) {
        $form_state->setErrorByName('registration_end_date',
          $this->t('Registration end date must be after registration start date.'));
      }
    }

    if ($event_date && $registration_end) {
      $event_timestamp = $event_date->getTimestamp();
      $end_timestamp = $registration_end->getTimestamp();

      if ($event_timestamp <= $end_timestamp) {
        $form_state->setErrorByName('event_date',
          $this->t('Event date should be after registration end date.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $current_time = \Drupal::time()->getRequestTime();

    $category = $form_state->getValue('category');
    $registration_start = $form_state->getValue('registration_start_date')->getTimestamp();
    $registration_end = $form_state->getValue('registration_end_date')->getTimestamp();

    $fields = [
      'event_name' => $form_state->getValue('event_name'), // Changed from 'title' to 'event_name'
      'description' => $form_state->getValue('description') ?: '',
      'event_date' => $form_state->getValue('event_date')->getTimestamp(),
      'location' => $form_state->getValue('location') ?: '',
      'max_attendees' => $form_state->getValue('max_attendees') ?: NULL,
      'status' => 1,
      'uid' => $current_user->id(),
      'created' => $current_time,
      'changed' => $current_time,
      'category' => $category,
      'registration_start' => $registration_start,
      'registration_end' => $registration_end,
    ];

    try {
      $event_id = $this->database->insert('event_registration_event')
        ->fields($fields)
        ->execute();

      $this->messenger()->addStatus(
        $this->t('Event "@title" has been created successfully with ID @id.', [
          '@title' => $fields['event_name'],
          '@id' => $event_id,
        ])
      );

      $form_state->setRedirect('event_registration.event_config'); // Redirect back to add another event
    }
    catch (\Exception $e) {
      $this->messenger()->addError(
        $this->t('An error occurred while saving the event: @error', [
          '@error' => $e->getMessage(),
        ])
      );
    }
  }

}
