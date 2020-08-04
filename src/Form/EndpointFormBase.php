<?php

namespace Drupal\salesmanago_integration\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EndpointFormBase.
 *
 * @ingroup salesmanago_integration
 */
class EndpointFormBase extends EntityForm {

  /**
   * An entity query factory for the endpoint entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Construct the EndpointFormBase.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   An entity query factory for the endpoint entity type.
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * Factory method for EndpointFormBase.
   *
   */
  public static function create(ContainerInterface $container) {
    $form = new static($container->get('entity_type.manager')->getStorage('endpoint'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the endpoint add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);

    $endpoint = $this->entity;

    // Build the form.
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $endpoint->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ],
      '#disabled' => !$endpoint->isNew(),
    ];
    $form['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint address'),
      '#maxlength' => 255,
      '#default_value' => $endpoint->address,
      '#required' => TRUE,
    ];
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#maxlength' => 255,
      '#default_value' => $endpoint->client_id,
      '#required' => TRUE,
    ];
    $form['api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Secret'),
      '#maxlength' => 255,
      '#default_value' => $endpoint->api_secret,
      '#required' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SALESmanago account email address'),
      '#maxlength' => 255,
      '#default_value' => $endpoint->email,
      '#required' => TRUE,
    ];

    // Return the form.
    return $form;
  }

  /**
   * Checks for an existing endpoint.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    // Use the query factory to build a new endpoint entity query.
    $query = $this->entityStorage->getQuery();

    // Query the entity ID to see if its in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actins from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Return the result.
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Add code here to validate your config entity's form elements.
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   * @return int|void
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(array $form, FormStateInterface $form_state) {
    // EntityForm provides us with the entity we're working on.
    $endpoint = $this->getEntity();
    $status = $endpoint->save();
    $url = $endpoint->toUrl();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      $this->messenger()->addMessage($this->t('Endpoint %address has been updated.', ['%address' => $endpoint->address]));
      $this->logger('salesmanago_integration')->notice('Endpoint %address has been updated.', ['%address' => $endpoint->address, 'link' => $edit_link]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('Endpoint %address has been added.', ['%address' => $endpoint->address]));
      $this->logger('salesmanago_integration')->notice('Endpoint %address has been added.', ['%address' => $endpoint->address, 'link' => $edit_link]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.endpoint.list');
  }
}
