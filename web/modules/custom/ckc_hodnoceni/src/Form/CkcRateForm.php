<?php

namespace Drupal\ckc_hodnoceni\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckc_hodnoceni\CkcHodnoceniService;
use Exception;

/**
 * Implements an example form.
 */
class CkcRateForm extends FormBase {

  public const CKC_HODNOCENI_PLACES = [
    'order_1' => '1. místo:',
    'order_2' => '2. místo:',
    'order_3' => '3. místo:',
    'order_4' => '4. místo:',
    'order_5' => '5. místo:',
    'order_6' => '6. místo:',
  ];
  public const CKC_HODNOCENI_TABLE_FIELDS_BASE = [
    'rid', 'ckc_year', 'ckc_category', 'uid', 'note', 'order_1_1_exclude',
  ];
  public const CKC_HODNOCENI_TABLE_FIELDS_PLACES = [
    'order_1_1',
    'order_2_1', 'order_2_2',
    'order_3_1', 'order_3_2', 'order_3_3',
    'order_4_1', 'order_4_2', 'order_4_3', 'order_4_4',
    'order_5_1', 'order_5_2', 'order_5_3', 'order_5_4', 'order_5_5',
    'order_6_1', 'order_6_2', 'order_6_3', 'order_6_4', 'order_6_5', 'order_6_6',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckc_rate_form';
  }

  private function get_year() {
    return  (string) \Drupal::routeMatch()->getParameter('ckc_rocnik');
  }

  private function get_category_string() {
    return (string) \Drupal::routeMatch()->getParameter('ckc_kategorie');
  }

  private function get_category_id() {
    return array_flip(CkcHodnoceniService::categories(true))[$this->get_category_string()];
  }

  private function get_uid() {
    return (int) \Drupal::currentUser()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $year = (string) \Drupal::routeMatch()->getParameter('ckc_rocnik');
    $category = (string) \Drupal::routeMatch()->getParameter('ckc_kategorie');
    $category_id = array_flip(CkcHodnoceniService::categories(true))[$category];
    $uid = (int) \Drupal::currentUser()->id();

    $works_raw = CkcHodnoceniService::get_works_by_year_and_category($year, $category);
    $works = CkcHodnoceniService::works($year, $category);
    $works_keys = array_map('strval', array_keys($works));

    $selected_values = $this->prepareSelectedValues($works_raw, $works_keys, $form_state->getUserInput());

    $form['#theme'] = 'ckc_rate_form';
    $form['#cache']['max-age'] = 0;

    if (empty($works)) {
      $form['without_works'] = [
        '#type' => 'value',
        '#value' => '1',
      ];
      $form['message'] = [
        '#markup' => 'V tehle kategorii zatim neni zadna prace.'
      ];

      return $form;
    }

    // main form
    $form['without_works'] = [
      '#type' => 'value',
      '#value' => '0',
    ];

    /** BASE DATA */

    $form['places'] = [
      '#type' => 'value',
      '#value' => self::CKC_HODNOCENI_PLACES,
    ];
    $form['works_keys'] = [
      '#type' => 'value',
      '#value' => $works_keys,
    ];
    $form['works_list'] = [
      '#type' => 'value',
      '#value' => $works_raw,
    ];
    $form['selected_values'] = [
      '#type' => 'value',
      '#value' => $selected_values,
    ];

    /** DATA **/

    $form['rid'] = [
      '#type' => 'hidden',
      '#value' => $selected_values['rid'],
    ];
    $form['ckc_year'] = [
      '#type' => 'hidden',
      '#value' => $this->get_year(),
    ];
    $form['ckc_category'] = [
      '#type' => 'hidden',
      '#value' => $this->get_category_id(),
    ];
    $form['uid'] = [
      '#type' => 'hidden',
      '#value' => $this->get_uid(),
    ];

    $form['order_1_1_exclude'] = [
      '#type' => 'checkbox',
      '#title' => 'nevyhlašovat',
      '#attributes' => [
        'tabindex' => -1,
      ],
      '#default_value' => $selected_values['order_1_1_exclude'],
    ];

    foreach(self::CKC_HODNOCENI_TABLE_FIELDS_PLACES as $place) {
      $this->addPlaceFromElement($place, $selected_values, $form);
    }

    foreach ($works_raw as $work) {
      $this->addWorkMarkupElement($work['code'], $selected_values, $form);
    }

    $form['note'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#cols' => 60,
      '#title' => 'Poznámka',
      '#default_value' => $form_state->getValue('note'),
      '#attributes' => [
      ],
    ];

    // ksm($form);

    /** END DATA **/

    $form['#attached']['library'][] = 'ckc_hodnoceni/hodnoceni';
    $form['#attached']['drupalSettings']['ckcHodnoceni']['selectedValues'] = $selected_values;
    $form['#attached']['drupalSettings']['ckcHodnoceni']['worksKeys'] = $works_keys;
    $form['#attached']['drupalSettings']['ckcHodnoceni']['works'] = $works;

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $form_state->getValue('rid') ? 'Upravit hodnocení' : 'Uložit hodnocení',
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $data = array_filter(
      $form_state->getValues(),
      function ($key) {
        return in_array($key, self::CKC_HODNOCENI_TABLE_FIELDS_PLACES);
      },
      ARRAY_FILTER_USE_KEY,
    );
    $valid_works = $form_state->getValue('works_keys');
    $map = [];
    foreach($data as $key => $value) {
      if (empty($value)) {
        continue;
      }
      if (in_array($value, $valid_works, TRUE)) {
        // $this->messenger()->addMessage('???'.$value);
        if (empty($map[$value])) $map[$value] = [];
        $map[$value][] = $key;
        continue;
      } else {
        $form_state->setErrorByName($key, 'Práce s číslem '. $value .' neexistuje!');
      }
    }
    foreach ($map as $value => $names) {
      if (count($names) > 1) {
        foreach($names as $name) {
          $form_state->setErrorByName($name);
        }
        $this->messenger()->addError('Práce '. $value .' může být v hodnocení jenom jednou!');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $allowed_fields = array_merge(self::CKC_HODNOCENI_TABLE_FIELDS_BASE, self::CKC_HODNOCENI_TABLE_FIELDS_PLACES);
    $data = array_filter(
      $form_state->getValues(),
      function ($key) use ($allowed_fields) {
        return in_array($key, $allowed_fields);
      },
      ARRAY_FILTER_USE_KEY,
    );
    ksm($data);
    if (empty($data['rid'])) {
      unset($data['rid']);
      $this->createRateRecord($data);
    } else {
      $this->updateRateRecord($data);
    }
  }

  private function prepareSelectedValues($works_raw, $works_keys, $user_input) {
    if (empty($user_input)) {
      $data_from_db = $this->readRateRecord(
        $this->get_year(),
        $this->get_category_id(),
        $this->get_uid()
      );
    }

    $selected_values = [
      'rid' => $user_input ? $user_input['rid'] : ($data_from_db && $data_from_db['rid'] ? $data_from_db['rid'] : null),
      'order_1_1_exclude' => 0,
      'map' => [
        'byInputName' => [],
        'byInputValue' => [],
      ],
    ];

    // Prepare map by work code (by input value).
    foreach ($works_raw as $work) {
      $selected_values['map']['byInputValue'][$work['code']] = [
        'workCode' => $work['code'],
        'title' => $work['title'],
        'inputName' => '',
        'mlok' => false,
      ];
    }

    // Prepare map by input name.
    foreach (self::CKC_HODNOCENI_TABLE_FIELDS_PLACES as $place) {
      $value = $user_input ? $user_input[$place] : ($data_from_db && $data_from_db[$place] ? $data_from_db[$place] : '');
      $value_not_in_by_input_value = empty($selected_values['map']['byInputValue'][$value]['inputName']);
      $valid = $value_not_in_by_input_value && in_array($value, $works_keys);
      $selected_values['map']['byInputName'][$place] = [
        'value' => $value,
        'valid' => $valid,
        'extra' => [],
      ];
      if ($place === 'order_1_1') {
        $exclude = $user_input && $user_input['order_1_1_exclude'] === 1 ? 1 : ($data_from_db && $data_from_db['order_1_1_exclude'] === '1' ? 1 : 0);
        if ($exclude === 1) {
          $selected_values['order_1_1_exclude'] = 1;
          $selected_values['map']['byInputName'][$place]['extra']['disabled'] = true;
        }
      }
      if ($valid) {
        $selected_values['map']['byInputValue'][$value]['inputName'] = $place;
      }
    }
    return $selected_values;
  }

  // private function updateSelectedValues(FormStateInterface $form_state) {
  //   $works_raw = $form_state->getValue('works_raw');
  //   $works_keys = $form_state->getValue('works_keys');
  //
  //   $selected_values['order_1_1_exclude'] = 0;
  //
  //   // Prepare map by work code (by input value).
  //   foreach ($works_raw as $work) {
  //     $selected_values['map']['byInputValue'][$work['code']] = [
  //       'workCode' => $work['code'],
  //       'title' => $work['title'],
  //       'inputName' => '',
  //       'mlok' => false,
  //     ];
  //   }
  //
  //   // Update map by input name.
  //   foreach (self::CKC_HODNOCENI_TABLE_FIELDS_PLACES as $place) {
  //     $value = $form_state->getValue($place);
  //     $valid = in_array($value, $works_keys);
  //     $selected_values['map']['byInputName'][$place] = [
  //       'value' => $value,
  //       'valid' => $valid,
  //       'extra' => [],
  //     ];
  //     if ($place === 'order_1_1' && $form_state->getValue('order_1_1_exclude') === '1') {
  //       $selected_values['order_1_1_exclude'] = 1;
  //       $selected_values['map']['byInputName'][$place]['extra']['disabled'] = true;
  //     }
  //     if ($valid) {
  //       $selected_values['map']['byInputValue'][$value]['inputName'] = $place;
  //     }
  //   }
  //
  //   return $selected_values;
  // }

  private function addPlaceFromElement($place, $selected_values, &$form) {
    $attributes = [
      'placeholder' => '000',
      'autocomplete' => 'off',
    ];
    if (!empty($selected_values['map']['byInputName'][$place]['extra']['disabled'])) {
      $attributes['disabled'] = true;
    }
    if ($selected_values['map']['byInputName'][$place]['valid']) {
      $attributes['class'][] = 'valid-input';
    }
    $form[$place] = [
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $selected_values['map']['byInputName'][$place]['value'],
      '#attributes' => $attributes,
    ];
  }

  private function addWorkMarkupElement($work_code, $selected_values, &$form) {
    $attributes = [
      'class' => [
        'work-item',
        'work-item-'.$work_code,
      ],
      'data-work-code' => $work_code,
      'data-to-input' => '',
    ];
    $rank_text = '';
    if ($selected_values['map']['byInputValue'][$work_code]['inputName'] !== '') {
      $attributes['class'][] = 'selected';
      $attributes['data-to-input'] = $selected_values['map']['byInputValue'][$work_code]['inputName']
        ? $selected_values['map']['byInputValue'][$work_code]['inputName']
        : '';
      $rank_text = $selected_values['map']['byInputValue'][$work_code]['inputName']
        ? self::CKC_HODNOCENI_PLACES[substr($selected_values['map']['byInputValue'][$work_code]['inputName'], 0, 7)]
        : '';
    }

    $form["work_{$work_code}_rank"] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' =>  ['class' => ['work-item-rank']],
      '#value' => $rank_text,
    ];
    $form["work_{$work_code}_mlok"] = [
      '#type' => 'checkbox',
      '#return_value' => $work_code,
    ];
    $form["work_{$work_code}"] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => $attributes,
      '#value' => $work_code.': '.$selected_values['map']['byInputValue'][$work_code]['title'],
    ];
  }

  private function createRateRecord($data) {
    $connection = Database::getConnection();
    $txn = $connection->startTransaction();
    $query = $connection->insert('ckc_hodnoceni')
      ->fields($data);
    try {
      $query->execute();
      $this->messenger()->addStatus('Vaše hodnocení bylo uloženo do databáze!');
    }
    catch (Exception $e) {
      $txn->rollBack();
      $this->messenger()->addError('Vaše hodnocení se nepovedlo uložit do databáze! Chyba:');
      $this->messenger()->addError($e->getMessage());
    }
  }

  private function readRateRecord(string $ckc_year, string $ckc_category, int $uid) {
    $connection = Database::getConnection();
    $query = $connection->select('ckc_hodnoceni', 'h')
      ->fields('h')
      ->condition('ckc_year', $ckc_year)
      ->condition('ckc_category', $ckc_category)
      ->condition('uid', $uid)
      ->range(0, 1);
    $result = $query->execute();
    return $result->fetchAssoc();
  }

  private function updateRateRecord($data) {
    $connection = Database::getConnection();
    $txn = $connection->startTransaction();
    $query = $connection->update('ckc_hodnoceni')
      ->fields($data)
      ->condition('rid', $data['rid']);
    try {
      $query->execute();
      $this->messenger()->addStatus('Vaše uložené hodnocení bylo upraveno!');
    }
    catch (Exception $e) {
      $txn->rollBack();
      $this->messenger()->addError('Vaše uložené hodnocení se nepovedlo upravit! Chyba:');
      $this->messenger()->addError($e->getMessage());
    }
  }

  private function deleteRateRecord(int $rid) {
    $connection = Database::getConnection();
    $query = $connection->delete('ckc_hodnoceni')
      ->condition('rid', $rid);
    dpm((string) $query);
  }


}
