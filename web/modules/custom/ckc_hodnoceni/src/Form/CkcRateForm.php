<?php

namespace Drupal\ckc_hodnoceni\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckc_hodnoceni\CkcHodnoceniService;

/**
 * Implements an example form.
 */
class CkcRateForm extends FormBase {

  public const CKC_FORM_STATUS_WORKS_EMPTY = 1;
  public const CKC_FORM_STATUS_WORKS_OK = 2;
  public const CKC_FORM_STATUS_INVALID_YEAR = 3;
  public const CKC_FORM_STATUS_INVALID_CATEGORY = 4;

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
    $year = \Drupal::routeMatch()->getParameter('ckc_rocnik');
    $category = \Drupal::routeMatch()->getParameter('ckc_kategorie');
    $works_raw =  CkcHodnoceniService::get_works_by_year_and_category($year, $category);
    $works = CkcHodnoceniService::works($year, $category);
    $works_options = ['---' => '---'] + $works;

    ksm($works_raw);

    $form['#theme'] = 'ckc_rate_form';

    if (empty($works)) {
      $form['without_works'] = [
        '#type' => 'value',
        '#value' => '1',
      ];
      $form['message'] = [
        '#markup' => 'V tehle kategorii zatim neni zadna prace.'
      ];
    } else {
      $form['without_works'] = [
        '#type' => 'value',
        '#value' => '0',
      ];

      $form['works_list'] = [
        '#type' => 'value',
        '#value' => $works_raw,
      ];

      $form['#attached']['library'][] = 'ckc_hodnoceni/hodnoceni';
      $form['#attached']['drupalSettings']['ckcHodnoceni']['works'] = $works;

      $form['ckc_year'] = [
        '#type' => 'hidden',
      ];
      $form['ckc_category'] = [
        '#type' => 'hidden',
      ];

      $form['order_1_1_exclude'] = [
        '#type' => 'checkbox',
        '#title' => 'nevyhlašovat',
      ];

      $places = [
        'order_1_1',
        'order_2_1', 'order_2_2',
        'order_3_1', 'order_3_2', 'order_3_3',
        'order_4_1', 'order_4_2', 'order_4_3', 'order_4_4',
        'order_5_1', 'order_5_2', 'order_5_3', 'order_5_4', 'order_5_5',
        'order_6_1', 'order_6_2', 'order_6_3', 'order_6_4', 'order_6_5', 'order_6_6',
      ];

      foreach ($places as $place) {
        $form[$place] = [
          '#type' => 'textfield',
          '#maxlength' => 3,
          '#size' => 3,
          '#default_value' => $form_state->getValue($place),
          '#attributes' => [
            'placeholder' => '000',
          ]
        ];
      }

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ksm($form_state->getValues());
  }

}
