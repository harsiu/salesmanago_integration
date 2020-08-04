<?php

namespace Drupal\salesmanago_integration\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SalesManagoFormBase.
 *
 * @ingroup salesmanago_integration
 */
class SalesManagoFormBase extends EntityForm {

  /**
   * An entity query factory for the salesmanago entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Construct the SalesManagoFormBase.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   An entity query factory for the salesmanago entity type.
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * Factory method for SalesManagoFormBase.
   */
  public static function create(ContainerInterface $container) {
    $form = new static($container->get('entity_type.manager')->getStorage('salesmanago'));
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
   *   An associative array containing the salesmanago add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);
    $salesmanago = $this->entity;

    // Build the form.
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Webform form ID'),
      '#default_value' => $salesmanago->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ],
      '#disabled' => !$salesmanago->isNew(),
    ];

    $form['contact_info'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => $this->t('Contact information fields'),
    ];

    $form['consents'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact consent fields'),
    ];

    $form['standard_detail'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact standard detail fields'),
    ];

    $form['contact_info']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name field ID'),
      '#maxlength' => 255,
      '#default_value' => $salesmanago->name,
      '#required' => TRUE,
    ];

    $form['contact_info']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email field ID'),
      '#maxlength' => 255,
      '#default_value' => $salesmanago->email,
      '#required' => TRUE,
    ];

    $form['contact_info']['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone field ID'),
      '#maxlength' => 255,
      '#default_value' => $salesmanago->phone,
    ];

    $form['consents']['forceOptIn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email opt in checkbox ID'),
      '#maxlength' => 255,
      '#default_value' => $salesmanago->forceOptIn,
    ];

    $form['consents']['forcePhoneOptIn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone opt in checkbox ID'),
      '#maxlength' => 255,
      '#default_value' => $salesmanago->forcePhoneOptIn,
    ];

    $form['standard_detail']['pickList'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Picklist field ID'),
      '#maxlength' => 255,
      '#default_value' => $salesmanago->pickList,
    ];

    // Return the form.
    return $form;
  }

  /**
   * Checks for an existing salesmanago entity.
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
    // Use the query factory to build a new salesmanago entity query.
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
   * To set the submit button text, we need to override actions().
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
   * Saves the entity.
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
    $salesmanago = $this->getEntity();
    $status = $salesmanago->save();
    // Grab the URL of the new entity. We'll use it in the message.
    $url = $salesmanago->toUrl();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      $this->messenger()->addMessage($this->t('SALESmanago Form %apiform_id has been updated.', ['%apiform_id' => $salesmanago->apiform_id]));
      $this->logger('salesmanago_integration')->notice('SALESmanago Form %apiform_id has been updated.', ['%apiform_id' => $salesmanago->apiform_id, 'link' => $edit_link]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('SALESmanago Form %apiform_id has been added.', ['%apiform_id' => $salesmanago->apiform_id]));
      $this->logger('salesmanago_integration')->notice('SALESmanago Form %apiform_id has been added.', ['%apiform_id' => $salesmanago->apiform_id, 'link' => $edit_link]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.salesmanago.list');
  }
}
