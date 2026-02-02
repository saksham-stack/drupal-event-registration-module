<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a form to list event registrations.
 */
class RegistrationListForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new RegistrationListForm.
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
    return 'event_registration_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Event Registrations');

    // Query to fetch registration data along with event information
    $query = $this->database->select('event_registration_entry', 'r');
    $query->join('event_registration_event', 'e', 'r.event_id = e.id');
    $query->fields('r', ['id', 'full_name', 'email', 'college', 'department', 'created']);
    $query->addField('e', 'event_name', 'event_name');
    $query->orderBy('r.created', 'DESC');
    
    $results = $query->execute();

    // Prepare table header
    $header = [
      $this->t('ID'),
      $this->t('Event Name'),
      $this->t('Full Name'),
      $this->t('Email'),
      $this->t('College'),
      $this->t('Department'),
      $this->t('Registration Date'),
    ];

    // Prepare table rows
    $rows = [];
    foreach ($results as $record) {
      $rows[] = [
        $record->id,
        $record->event_name,
        $record->full_name,
        $record->email,
        $record->college,
        $record->department,
        date('Y-m-d H:i:s', $record->created),
      ];
    }

    // Build the table
    $form['registrations_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No registrations found.'),
    ];

    // Add export button
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['export'] = [
      '#type' => 'link',
      '#title' => $this->t('Export to CSV'),
      '#url' => Url::fromRoute('event_registration.csv_export'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submission handling needed for this form
  }

}