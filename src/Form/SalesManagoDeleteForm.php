<?php

namespace Drupal\salesmanago_integration\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SalesManagoDeleteForm.
 *
 * @ingroup salesmanago_integration
 */
class SalesManagoDeleteForm extends EntityConfirmFormBase {

  /**
   * Gathers a confirmation question.
   *
   * @return string
   *   Translated string.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete form %id?', [
      '%id' => $this->entity->id,
    ]);
  }

  /**
   * Gather the confirmation text.
   *
   * @return string
   *   Translated string.
   */
  public function getConfirmText() {
    return $this->t('Delete SALESmanago Form');
  }

  /**
   * Gets the cancel URL.
   *
   * @return \Drupal\Core\Url
   *   The URL to go to if the user cancels the deletion.
   */
  public function getCancelUrl() {
    return new Url('entity.salesmanago.list');
  }

  /**
   * The submit handler for the confirm form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the entity.
    $this->entity->delete();

    // Set a message that the entity was deleted.
    $this->messenger()->addMessage($this->t('Form %id was deleted.', [
      '%id' => $this->entity->id,
    ]));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
