<?php

namespace Drupal\salesmanago_integration\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the endpoint entity.
 *
 * @ingroup salesmanago_integration
 *
 * @ConfigEntityType(
 *   id = "endpoint",
 *   label = @Translation("Endpoint"),
 *   admin_permission = "administer salesmanago",
 *   handlers = {
 *     "access" = "Drupal\salesmanago_integration\EndpointAccessController",
 *     "list_builder" = "Drupal\salesmanago_integration\Controller\EndpointListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\salesmanago_integration\Form\EndpointEditForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/salesmanago-integration/endpoint/{endpoint}"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "address",
 *     "client_id",
 *     "api_secret",
 *     "email",
 *   }
 * )
 */
class Endpoint extends ConfigEntityBase {

  /**
   * The endpoint ID.
   *
   * @var string
   */
  public $id;

  /**
   * The endpoint UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The endpoint address.
   *
   * @var string
   */
  public $address;

  /**
   * The client id.
   *
   * @var string
   */
  public $client_id;

  /**
   * The API secret key.
   *
   * @var string
   */
  public $api_secret;

  /**
   * The SALESmanago account email address.
   *
   * @var string
   */
  public $email;
}
