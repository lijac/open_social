<?php

namespace Drupal\social_event_an_enroll\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_event\Form\EnrollActionForm;

/**
 * Class EventAnEnrollController.
 *
 * @package Drupal\social_event_an_enroll\Controller
 */
class EventAnEnrollController extends ControllerBase {

  /**
   * Determines if user has access to enroll form.
   */
  public function enrollAccess(NodeInterface $node) {
    $current_user = \Drupal::currentUser();

    $node_visibility = $node->get('field_content_visibility')->getString();
    if ($node_visibility !== 'public') {
      return AccessResult::forbidden();
    }

    $groups = EnrollActionForm::getGroups($node);
    if (!empty($groups)) {
      $group_type_ids = $this->configFactory->getEditable('social_event.settings')
        ->get('enroll');

      foreach ($groups as $group) {
        $group_type_id = $group->bundle();

        if (in_array($group_type_id, $group_type_ids) && $group->hasPermission('join group', $current_user)) {
          break;
        }

        if ($group->hasPermission('enroll to events in groups', $current_user) == FALSE) {
          return AccessResult::forbidden();
        }
      }
    }

    return AccessResult::allowed();
  }

  /**
   * Enroll dialog callback.
   */
  public function enrollDialog(NodeInterface $node) {
    $action_links = [
      'login' => [
        'uri' => Url::fromRoute('user.login', [], [
          'query' => [
            'destination' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()])
              ->toString(),
          ],
        ])->toString(),
      ],
      'register' => [
        'uri' => Url::fromRoute('user.register', [], [
          'query' => [
            'destination' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()])
              ->toString(),
          ],
        ])->toString(),
      ],
      'guest' => [
        'uri' => Url::fromRoute('social_event_an_enroll.enroll_form', ['node' => $node->id()], [])
          ->toString(),
      ],
    ];

    $output = [
      '#theme' => 'event_an_enroll_dialog',
      '#links' => $action_links,
    ];

    return $output;
  }

  /**
   * The _title_callback for the event enroll dialog route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   *
   * @return string
   *   The page title.
   */
  public function enrollTitle(NodeInterface $node) {
    return $this->t('Enroll in @label Event', ['@label' => $node->label()]);
  }

}
