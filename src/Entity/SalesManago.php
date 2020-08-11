<?php

namespace Drupal\salesmanago_integration\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the salesmanago entity.
 *
 * @ingroup salesmanago_integration
 *
 * @ConfigEntityType(
 *   id = "salesmanago",
 *   label = @Translation("SALESmanago form settings"),
 *   admin_permission = "administer salesmanago",
 *   handlers = {
 *     "access" = "Drupal\salesmanago_integration\EndpointAccessController",
 *     "list_builder" = "Drupal\salesmanago_integration\Controller\SalesManagoListBuilder",
 *     "form" = {
 *       "add" = "Drupal\salesmanago_integration\Form\SalesManagoAddForm",
 *       "edit" = "Drupal\salesmanago_integration\Form\SalesManagoEditForm",
 *       "delete" = "Drupal\salesmanago_integration\Form\SalesManagoDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/salesmanago-integration/form/{salesmanago}",
 *     "delete-form" = "/admin/config/salesmanago-integration/form/{salesmanago}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "name",
 *     "email",
 *     "phone",
 *     "tags",
 *     "message",
 *     "forceOptIn",
 *     "forcePhoneOptIn",
 *     "consentCount",
 *     "consentDetails",
 *     "pickList",
 *   }
 * )
 */
class SalesManago extends ConfigEntityBase {

  /**
   * The salesmanago entity ID.
   *
   * @var string
   */
  public $id;

  /**
   * The salesmanago entity UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * Webform name field ID.
   *
   * @var string
   */
  public $name;

  /**
   * Webform email field ID.
   *
   * @var string
   */
  public $email;

  /**
   * Webform phone field ID.
   *
   * @var string
   */
  public $phone;

  /**
   * Contact tags.
   *
   * @var string
   */
  public $tags;

  /**
   * Message field ID
   *
   * @var string
   */
  public $message;

  /**
   * Email consent.
   *
   * @var string
   */
  public $forceOptIn;

  /**
   * Phone consent.
   *
   * @var string
   */
  public $forcePhoneOptIn;

  /**
   * Custom consents.
   *
   * @var integer
   */
  public $consentCount;

  /**
   * Custom consents.
   *
   * @var array
   */
  public $consentDetails;

  /**
   * Picklist field ID
   *
   * @var string
   */
  public $pickList;
}
