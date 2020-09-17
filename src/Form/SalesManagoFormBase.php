<?php

namespace Drupal\salesmanago_integration\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
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
   * @var EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Construct the SalesManagoFormBase.
   *
   * @param EntityStorageInterface $entity_storage
   *   An entity query factory for the salesmanago entity type.
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * Factory method for SalesManagoFormBase.
   *
   * @param ContainerInterface $container
   * @return SalesManagoFormBase|static
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
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the salesmanago add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);
    $salesmanago = $this->entity;

    $remove_consent = $form_state->get('removeConsent');
    $remove_detail = $form_state->get('removeDetail');
    $remove_note = $form_state->get('removeNote');

    $consent_count = $form_state->get('consentCount');
    $detail_count = $form_state->get('detailCount');
    $note_count = $form_state->get('noteCount');

    if (empty($consent_count) && !empty($salesmanago->consentDetails)) {
      $form_state->set('consentCount', count($salesmanago->consentDetails));
      $consent_count = $form_state->get('consentCount');
    }
    elseif (empty($consent_count) && empty($salesmanago->consentDetails)) {
      $form_state->set('consentCount', 0);
      $consent_count = $form_state->get('consentCount');
    }

    if (empty($detail_count) && !empty($salesmanago->standardDetails)) {
      $form_state->set('detailCount', count($salesmanago->standardDetails));
      $detail_count = $form_state->get('detailCount');
    }
    elseif (empty($detail_count) && empty($salesmanago->standardDetails)) {
      $form_state->set('detailCount', 0);
      $detail_count = $form_state->get('detailCount');
    }

    if (empty($note_count) && !empty($salesmanago->notes)) {
      $form_state->set('noteCount', count($salesmanago->notes));
      $note_count = $form_state->get('noteCount');
    }
    elseif (empty($note_count) && empty($salesmanago->notes)) {
      $form_state->set('noteCount', 0);
      $note_count = $form_state->get('noteCount');
    }

    // Build the form.
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Webform form ID'),
      '#default_value' => $salesmanago->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => "([^a-z0-9_]+)|(^custom$)",
        'error' => "The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word 'custom'.",
      ],
      '#disabled' => !$salesmanago->isNew(),
    ];

    $form['contact_info'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => $this->t('Contact information fields'),
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

    $form['contact_info']['tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Contact tags'),
      '#description' => $this->t('You can set multiple tags by separating then with a comma'),
      '#default_value' => $salesmanago->tags,
    ];

    $form['consents'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Contact consent setting'),
      '#description' => $this->t('Settings from configuring a custom SM consent and a checkbox to accept or decline it'),
      '#attributes' => ['id' => 'consent-fieldset-wrapper'],
    ];

    $form['consents']['forceOptIn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email opt in field ID'),
      '#description' => $this->t('From checkbox field for SM email consent'),
      '#maxlength' => 255,
      '#default_value' => $salesmanago->forceOptIn,
    ];

    $form['consents']['forcePhoneOptIn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone opt in field ID'),
      '#description' => $this->t('From checkbox field for SM phone consent'),
      '#maxlength' => 255,
      '#default_value' => $salesmanago->forcePhoneOptIn,
    ];

    $form['consents']['consentDetails']['#tree'] = TRUE;

    for ($index = 0; $index < $consent_count; $index++) {
      $consent_name = isset($salesmanago->consentDetails[$index]['name']) ? $salesmanago->consentDetails[$index]['name'] : '';
      $form_field = isset($salesmanago->consentDetails[$index]['field']) ? $salesmanago->consentDetails[$index]['field'] : '';

      $form['consents']['consentDetails'][$index] = [
        '#type' => 'details',
        '#title' => ($consent_name != '') ?
          $this->t("Custom consent - '" . $consent_name . "'") :
          $this->t('Custom consent #' . ($index + 1)),
      ];

      $form['consents']['consentDetails'][$index]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Custom consent name in SM CRM'),
        '#description' => $this->t('The custom consent must be created in your SM setting before being used here'),
        '#maxlength' => 255,
        '#default_value' => $consent_name,
      ];

      $form['consents']['consentDetails'][$index]['field'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Custom consent form field ID'),
        '#description' => $this->t('From checkbox field for accepting the custom consent'),
        '#maxlength' => 255,
        '#default_value' => $form_field,
      ];

      $form['consents']['consentDetails'][$index]['remove'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#name' => 'consent-' . $index,
        '#submit' => array('::removeElement'),
        '#ajax' => [
          'wrapper' => 'consent-fieldset-wrapper',
          'callback' => '::actionCallback',
        ],
      ];

      if (isset($remove_consent[$index])) {
        $form['consents']['consentDetails'][$index]['#disabled'] = TRUE;
        $form['consents']['consentDetails'][$index]['remove']['#attributes'] = array('disabled' => 'disabled');
        $form['consents']['consentDetails'][$index]['#title'] = $form['consents']['consentDetails'][$index]['#title'] . ' (Marked for removal)';
      }
    }

    $form['consents']['add'] = [
      '#type' => 'submit',
      '#value' => t('Add consent'),
      '#name' => 'consent',
      '#submit' => array('::addElement'),
      '#ajax' => [
        'wrapper' => 'consent-fieldset-wrapper',
        'callback' => '::actionCallback',
      ],
    ];

    $form['standardDetails'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact standard detail fields'),
      '#description' => $this->t('A standard detail consists of a label and a value. Both the label and value can either be form fields or custom text and tokens'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#attributes' => ['id' => 'details-fieldset-wrapper'],
    ];

    for ($index = 0; $index < $detail_count; $index++) {
      $detail_label = isset($salesmanago->standardDetails[$index]['label']) ? $salesmanago->standardDetails[$index]['label'] : '';
      $form_field = isset($salesmanago->standardDetails[$index]['field']) ? $salesmanago->standardDetails[$index]['field'] : '';

      $form['standardDetails'][$index] = [
        '#type' => 'details',
        '#title' => $this->t('Standard detail #' . ($index + 1)),
      ];

      $form['standardDetails'][$index]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Standard detail label'),
        '#maxlength' => 255,
        '#element_validate' => array('token_element_validate'),
        '#token_types' => array('current-date', 'current-page', 'current-user', 'random', 'site'),
        '#default_value' => $detail_label,
      ];

      $form['standardDetails'][$index]['token'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => array('current-date', 'current-page', 'current-user', 'random', 'site'),
      ];

      $form['standardDetails'][$index]['field'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Standard detail value'),
        '#maxlength' => 255,
        '#element_validate' => array('token_element_validate'),
        '#token_types' => array('current-date', 'current-page', 'current-user', 'random', 'site'),
        '#default_value' => $form_field,
      ];

      $form['standardDetails'][$index]['remove'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#name' => 'detail-' . $index,
        '#submit' => array('::removeElement'),
        '#ajax' => [
          'wrapper' => 'details-fieldset-wrapper',
          'callback' => '::actionCallback',
        ],
      ];

      if (isset($remove_detail[$index])) {
        $form['standardDetails'][$index]['#disabled'] = TRUE;
        $form['standardDetails'][$index]['remove']['#attributes'] = array('disabled' => 'disabled');
        $form['standardDetails'][$index]['#title'] = $form['standardDetails'][$index]['#title'] . ' (Marked for removal)';
      }
    }

    $form['standardDetails']['add'] = [
      '#type' => 'submit',
      '#value' => t('Add standard detail'),
      '#name' => 'detail',
      '#submit' => array('::addElement'),
      '#ajax' => [
        'wrapper' => 'details-fieldset-wrapper',
        'callback' => '::actionCallback',
      ],
    ];

    $form['notes'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact notes settings'),
      '#description' => $this->t('Settings for creating notes in contact profiles'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#attributes' => ['id' => 'notes-fieldset-wrapper'],
    ];

    for ($index = 0; $index < $note_count; $index++) {
      $note_label = isset($salesmanago->notes[$index]['label']) ? $salesmanago->notes[$index]['label'] : '';
      $form_field = isset($salesmanago->notes[$index]['field']) ? $salesmanago->notes[$index]['field'] : '';

      $form['notes'][$index] = [
        '#type' => 'details',
        '#title' => $this->t('Note #' . ($index + 1)),
      ];

      $form['notes'][$index]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Note header'),
        '#maxlength' => 255,
        '#element_validate' => array('token_element_validate'),
        '#token_types' => array('current-date', 'current-page', 'current-user', 'random', 'site'),
        '#default_value' => $note_label,
      ];

      $form['notes'][$index]['token'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => array('current-date', 'current-page', 'current-user', 'random', 'site'),
      ];

      $form['notes'][$index]['field'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Note content form field'),
        '#maxlength' => 255,
        '#element_validate' => array('token_element_validate'),
        '#token_types' => array('current-date', 'current-page', 'current-user', 'random', 'site'),
        '#default_value' => $form_field,
      ];

      $form['notes'][$index]['remove'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#name' => 'note-' . $index,
        '#submit' => array('::removeElement'),
        '#ajax' => [
          'wrapper' => 'notes-fieldset-wrapper',
          'callback' => '::actionCallback',
        ],
      ];

      if (isset($remove_note[$index])) {
        $form['notes'][$index]['#disabled'] = TRUE;
        $form['notes'][$index]['remove']['#attributes'] = array('disabled' => 'disabled');
        $form['notes'][$index]['#title'] = $form['notes'][$index]['#title'] . ' (Marked for removal)';
      }
    }

    $form['notes']['add'] = [
      '#type' => 'submit',
      '#value' => t('Add note'),
      '#name' => 'note',
      '#submit' => array('::addElement'),
      '#ajax' => [
        'wrapper' => 'notes-fieldset-wrapper',
        'callback' => '::actionCallback',
      ],
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
   * @param FormStateInterface $form_state
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
   * @param FormStateInterface $form_state
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
   * Handle form add buttons
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function addElement(&$form, FormStateInterface $form_state) {
    $triggerd_element = $form_state->getTriggeringElement();
    $trigger = explode('-', $triggerd_element['#name']);
    $type = isset($trigger[0]) ? $trigger[0] : '';

    switch ($type) {
      case 'consent':
      {
        $consent_count = $form_state->get("consentCount");
        $form_state->set("consentCount", ($consent_count + 1));
        break;
      }
      case 'detail':
      {
        $detail_count = $form_state->get("detailCount");
        $form_state->set("detailCount", ($detail_count + 1));
        break;
      }
      case 'note':
      {
        $note_count = $form_state->get("noteCount");
        $form_state->set("noteCount", ($note_count + 1));
        break;
      }
    }

    $form_state->setRebuild();
  }

  public function removeElement(&$form, FormStateInterface $form_state) {
    $triggerd_element = $form_state->getTriggeringElement();
    $trigger = explode('-', $triggerd_element['#name']);
    $type = $trigger[0];
    $index = $trigger[1];

    switch ($type) {
      case 'consent':
      {
        $remove_consent = $form_state->get("removeConsent");
        $remove_consent[$index] = 1;
        $form_state->set("removeConsent", $remove_consent);
        break;
      }
      case 'detail':
      {
        $remove_detail = $form_state->get("removeDetail");
        $remove_detail[$index] = 1;
        $form_state->set("removeDetail", $remove_detail);
        break;
      }
      case 'note':
      {
        $remove_note = $form_state->get("removeNote");
        $remove_note[$index] = 1;
        $form_state->set("removeNote", $remove_note);
        break;
      }
    }

    $form_state->setRebuild();
  }

  /**
   * Rebuild specific form element
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return mixed
   */
  public function actionCallback($form, FormStateInterface $form_state) {
    $triggerd_element = $form_state->getTriggeringElement();
    $trigger = explode('-', $triggerd_element['#name']);
    $type = isset($trigger[0]) ? $trigger[0] : '';

    switch ($type) {
      case 'consent':
      {
        return $form['consents'];
      }
      case 'detail':
      {
        return $form['standardDetails'];
      }
      case 'note':
      {
        return $form['notes'];
      }
    }
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
   * @throws EntityMalformedException
   * @throws EntityStorageException
   */
  public function save(array $form, FormStateInterface $form_state) {
    // EntityForm provides us with the entity we're working on.
    $salesmanago = $this->getEntity();

    // Array of indexes of elements which are marked to be deleted
    $remove_consent = $form_state->get('removeConsent');
    $remove_detail = $form_state->get("removeDetail");
    $remove_note = $form_state->get("removeNote");

    // Go through the array and delete the elements
    if (!empty($remove_consent)) {
      foreach ($remove_consent as $key => $value) {
        unset($salesmanago->consentDetails[$key]);
      }
    }

    if (!empty($remove_detail)) {
      foreach ($remove_detail as $key => $value) {
        unset($salesmanago->standardDetails[$key]);
      }
    }

    if (!empty($remove_note)) {
      foreach ($remove_note as $key => $value) {
        unset($salesmanago->notes[$key]);
      }
    }

    $status = $salesmanago->save();
    // Grab the URL of the new entity. We'll use it in the message.
    $url = $salesmanago->toUrl();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      $this->messenger()->addMessage($this->t('SALESmanago Form %id has been updated.', ['%id' => $salesmanago->id]));
      $this->logger('salesmanago_integration')->notice('SALESmanago Form %id has been updated.', ['%id' => $salesmanago->id, 'link' => $edit_link]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('SALESmanago Form %id has been added.', ['%id' => $salesmanago->id]));
      $this->logger('salesmanago_integration')->notice('SALESmanago Form %id has been added.', ['%id' => $salesmanago->id, 'link' => $edit_link]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.salesmanago.list');
  }
}
