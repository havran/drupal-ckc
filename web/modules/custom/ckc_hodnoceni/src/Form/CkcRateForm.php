<?php

namespace Drupal\ckc_hodnoceni\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an example form.
 */
class CkcRateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckc_rate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#theme'] = 'ckc_rate_form';

    $form['ckc_rocnik'] = [
      '#type' => 'hidden',
    ];
    $form['ckc_kategorie'] = [
      '#type' => 'hidden',
    ];

    // row 1
    $form['poradi_1_1'] = [
      '#type' => 'select',
    ];

    // row 2
    $form['poradi_2_1'] = [
      '#type' => 'select',
    ];
    $form['poradi_2_2'] = [
      '#type' => 'select',
    ];

    // row 3
    $form['poradi_3_1'] = [
      '#type' => 'select',
    ];
    $form['poradi_3_2'] = [
      '#type' => 'select',
    ];
    $form['poradi_3_3'] = [
      '#type' => 'select',
    ];

    // row 4
    $form['poradi_4_1'] = [
      '#type' => 'select',
    ];
    $form['poradi_4_2'] = [
      '#type' => 'select',
    ];
    $form['poradi_4_3'] = [
      '#type' => 'select',
    ];
    $form['poradi_4_4'] = [
      '#type' => 'select',
    ];

    // row 5
    $form['poradi_5_1'] = [
      '#type' => 'select',
    ];
    $form['poradi_5_2'] = [
      '#type' => 'select',
    ];
    $form['poradi_5_3'] = [
      '#type' => 'select',
    ];
    $form['poradi_5_4'] = [
      '#type' => 'select',
    ];
    $form['poradi_5_5'] = [
      '#type' => 'select',
    ];

    // row 5
    $form['poradi_6_1'] = [
      '#type' => 'select',
    ];
    $form['poradi_6_2'] = [
      '#type' => 'select',
    ];
    $form['poradi_6_3'] = [
      '#type' => 'select',
    ];
    $form['poradi_6_4'] = [
      '#type' => 'select',
    ];
    $form['poradi_6_5'] = [
      '#type' => 'select',
    ];
    $form['poradi_6_6'] = [
      '#type' => 'select',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('phone_number')) < 3) {
      $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Your phone number is @number', ['@number' => $form_state->getValue('phone_number')]));
  }

}
