<?php

/**
 * @file
 * Contains \Drupal\decoupled_auth\DecoupledAuthSettingsForm
 */

namespace Drupal\decoupled_auth;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Configure decoupled auth settings for this site.
 */
class DecoupledAuthSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'decoupled_auth_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'decoupled_auth.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('decoupled_auth.settings');

    $form['acquisitions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User acquisitions'),
      '#tree' => TRUE,
    ];

    $form['acquisitions']['behavior_first'] = [
      '#type' => 'radios',
      '#title' => $this->t('Acquire the first match if there are multiple matches'),
      '#description' => $this->t('Setting this to yes may reduce the occurrences of duplicates, but may risk acquiring of incorrect users in the case of multiple users.'),
      '#default_value' => (int) $config->get('acquisitions.behavior_first'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
    ];

    $form['acquisitions']['registration'] = [
      '#type' => 'radios',
      '#title' => $this->t('On user registration'),
      '#default_value' => (int) $config->get('acquisitions.registration'),
      '#options' => [
        0 => $this->t('Always create a new user'),
        1 => $this->t('Attempt to acquire an existing unauthenticated user'),
      ],
    ];

    $form['acquisitions']['registration_notice_demote'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Demote the security error when user registration acquisitions are enabled without email verification to a warning.'),
      '#description' => $this->t('It is not recommended that user registration acquisitions are enabled without account verification such as email. This could allow malicious registrations to access data they should not be authorised to see.'),
      '#default_value' => $config->get('acquisitions.registration_notice_demote'),
      '#states' => ['visible' => ['input[name="acquisitions[registration]"' => ['value' => '1']]],
    ];

    $form['unique_emails'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Unique emails'),
      '#description' => $this->t('Coupled users must always have unique email addresses. Decoupled users can optionally have non-unique emails and this can be configured per role.'),
      '#tree' => TRUE,
    ];

    $form['unique_emails']['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select which users must have unique email addresses'),
      '#options' => [
        'all' => $this->t('All users'),
        'include' => $this->t('Any decoupled user with the selected roles'),
        'exclude' => $this->t('Any decoupled user without the selected roles'),
        'none' => $this->t('No decoupled users'),
      ],
      '#default_value' => $config->get('unique_emails.mode'),
    ];

    $form['unique_emails']['roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Selected roles'),
      '#multiple' => TRUE,
      '#options' => [],
      '#default_value' => $config->get('unique_emails.roles'),
      '#states' => ['visible' => ['input[name="unique_emails[mode]"' => [
        ['value' => 'include'],
        ['value' => 'exclude'],
      ]]],
    ];
    foreach (Role::loadMultiple() as $role) {
      // We aren't interested in anon/auth roles as thay are locked roles.
      if (!in_array($role->id(), [Role::ANONYMOUS_ID, Role::AUTHENTICATED_ID])) {
        $form['unique_emails']['roles']['#options'][$role->id()] = $role->label();
      }
    }
    $form['unique_emails']['roles']['#size'] = min(10, count($form['unique_emails']['roles']['#options']));

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('decoupled_auth.settings')
      ->set('acquisitions.behavior_first', $form_state->getValue(['acquisitions','behavior_first']))
      ->set('acquisitions.registration', $form_state->getValue(['acquisitions','registration']))
      ->set('acquisitions.registration_notice_demote', $form_state->getValue(['acquisitions','registration_notice_demote']))
      ->set('unique_emails.mode', $form_state->getValue(['unique_emails','mode']))
      ->set('unique_emails.roles', $form_state->getValue(['unique_emails','roles']))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
