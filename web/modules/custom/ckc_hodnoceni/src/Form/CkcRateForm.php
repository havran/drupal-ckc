<?php

namespace Drupal\ckc_hodnoceni\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckc_hodnoceni\CkcHodnoceniBase;
use Drupal\ckc_hodnoceni\CkcHodnoceniService;
use Exception;

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
      '#value' => CkcHodnoceniBase::CKC_HODNOCENI_PLACES,
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

    $form['note'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#cols' => 60,
      '#title' => 'Poznámka',
      '#default_value' => $selected_values['note'],
      '#attributes' => [
      ],
    ];

    $form['exclude_first_place'] = [
      '#type' => 'checkbox',
      '#title' => 'nevyhlašovat',
      '#attributes' => [
        'tabindex' => -1,
      ],
      '#default_value' => $selected_values['exclude_first_place'] === 'y' ? 1 : 0,
    ];

    foreach(CkcHodnoceniBase::CKC_HODNOCENI_TABLE_FIELDS_PLACES as $place) {
      $this->addPlaceFromElement($place, $selected_values, $form);
    }

    foreach ($works_raw as $work) {
      $this->addWorkMarkupElement($work['code'], $selected_values, $form);
    }

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
        return in_array($key, CkcHodnoceniBase::CKC_HODNOCENI_TABLE_FIELDS_PLACES);
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
    $allowed_fields = array_merge(CkcHodnoceniBase::CKC_HODNOCENI_TABLE_FIELDS_BASE, CkcHodnoceniBase::CKC_HODNOCENI_TABLE_FIELDS_PLACES);
    $data = array_filter(
      $form_state->getValues(),
      function ($key) use ($allowed_fields) {
        return in_array($key, $allowed_fields);
      },
      ARRAY_FILTER_USE_KEY,
    );
    $this->createOrUpdateRateRecord($data, $form_state);
  }

  private function prepareSelectedValues($works_raw, $works_keys, $user_input) {
    if (empty($user_input)) {
      // if not submitted data then select data from DB
      $data = $this->readRateRecord(
        $this->get_year(),
        $this->get_category_id(),
        $this->get_uid()
      );
    } else {
      // if submitted data then recreate DB result
      $works_data_from_submit = [];
      foreach (CkcHodnoceniBase::CKC_HODNOCENI_TABLE_FIELDS_PLACES as $place_field_name) {
        if (
          empty($user_input[$place_field_name]) ||
          (!empty($user_input[$place_field_name]) && !in_array($user_input[$place_field_name], $works_keys))
        ) {
          continue;
        }
        $works_data_from_submit[$user_input[$place_field_name]] = $place_field_name;
      }
      $data_works = [];
      foreach ($works_raw as $work) {
        $data_works[$work['code']] = [
          'inputName' => $this->get($works_data_from_submit, $work['code'], ''),
          'mlok' => 0,
        ];
      }
      $data =  [
        'data' => [
          'rid' => $user_input['rid'],
          'ckc_year' => $user_input['ckc_year'],
          'ckc_category' => $user_input['ckc_category'],
          'uid' => $user_input['uid'],
          'exclude_first_place' => empty($user_input['exclude_first_place']) ? 'n' : 'y',
          'note' => $user_input['note'],
        ],
        'data_works' => $data_works,
      ];
    }

    $selected_values = [
      'rid' => $this->get($data, 'data.rid', null),
      'note' => $this->get($data, 'data.note', null),
      'exclude_first_place' => $this->get($data, 'data.exclude_first_place', 'n'),
      'map' => [
        'byInputName' => [],
        'byInputValue' => [],
      ],
    ];

    // Prepare map by input name.
    foreach (CkcHodnoceniBase::CKC_HODNOCENI_TABLE_FIELDS_PLACES as $place) {
      $value = $this->get($user_input, $place, '');
      $valid = in_array($value, $works_keys);
      $selected_values['map']['byInputName'][$place] = [
        'value' => $value,
        'valid' => $valid,
        'extra' => [],
      ];
      if ($valid) {
        $selected_values['map']['byInputValue'][$value]['inputName'] = $place;
      }
    }

    // Prepare map by input value.
    foreach ($works_raw as $work) {
      $input_name = $this->get($data, "data_works.{$work['code']}.inputName", '');
      $selected_values['map']['byInputValue'][$work['code']] = [
        'workCode' => $work['code'],
        'title' => $work['title'],
        'inputName' => $input_name,
        'mlok' => false,
      ];
      if (!empty($input_name)) {
        $selected_values['map']['byInputName'][$input_name]['value'] = $work['code'];
        $selected_values['map']['byInputName'][$input_name]['valid'] = true;
      }
    }

    if ($this->get($data, 'data.exclude_first_place', 'n') === 'y') {
      $selected_values['map']['byInputName']['order_1_1']['extra']['disabled'] = true;
    }

    return $selected_values;
  }

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
        ? CkcHodnoceniBase::CKC_HODNOCENI_PLACES[substr($selected_values['map']['byInputValue'][$work_code]['inputName'], 0, 7)]
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

  private function processMlokValues(FormStateInterface $form_state) {
    return '{}';
  }

  private function createOrUpdateRateRecord($data, FormStateInterface $form_state) {
    // ksm($data);
    $data['mlok'] = $this->processMlokValues($form_state);
    empty($data['rid'])
      ? $this->createRateRecord($form_state)
      : $this->updateRateRecord($form_state);
  }

  private function createRateRecord(FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $transaction = $connection->startTransaction();
    try {
      $q1 = $connection->insert('ckc_hodnoceni')->fields($this->getHodnoceniWorkRow($form_state));
      $rid = $q1->execute();
      $q2 = $connection->insert('ckc_hodnoceni_works')->fields(['rid', 'work_id', 'work_place', 'work_place_order', 'work_mlok']);
      foreach ($this->getHodnoceniWorksRows($rid, $form_state) as $record) {
        $q2->values($record);
      }
      $q2->execute();
      $this->messenger()->addStatus('Vaše hodnocení bylo uloženo do databáze!');
      return $rid;
    }
    catch (Exception $e) {
      $transaction->rollBack();
      $this->messenger()->addError('Vaše hodnocení se nepovedlo uložit do databáze! Chyba:');
      $this->messenger()->addError($e->getMessage());
    }
  }

  private function getHodnoceniWorkRow(FormStateInterface $form_state, $rid = null) {
    $current_time = time();
    $data = [
      'ckc_year' => $form_state->getValue('ckc_year'),
      'ckc_category' => $form_state->getValue('ckc_category'),
      'uid' => $form_state->getValue('uid'),
      'exclude_first_place' => $form_state->getValue('exclude_first_place', 'n') === 1 ? 'y' : 'n',
      'note' => $form_state->getValue('note'),
    ];
    if (empty($rid)) {
      $data['created'] = $current_time;
      $data['updated'] = $current_time;
    } else {
      $data['updated'] = $current_time;
    }
    return $data;
  }

  private function getHodnoceniWorksRows($rid, FormStateInterface $form_state) {
    // prepare work data
    $work_data = [];
    foreach (
      CkcHodnoceniService::get_works_by_year_and_category(
        $form_state->getValue('ckc_year'),
        $form_state->getValue('ckc_category')
      ) as $work) {
      $work_data[$work['code']] = [
        'rid' => $rid,
        'work_id' => $work['code'],
        'work_place' => null,
        'work_place_order' => null,
        'work_mlok' => 0,
      ];
    }
    // update work data by submitted data
    foreach (CkcHodnoceniBase::CKC_HODNOCENI_TABLE_FIELDS_PLACES as $place_field_name) {
      $work_id = $form_state->getValue($place_field_name);
      if (empty($work_data[$work_id])) {
        continue;
      }
      $work_data[$work_id]['work_place'] = (int) substr($place_field_name, 6, 1);
      $work_data[$work_id]['work_place_order'] = (int) substr($place_field_name, 8, 1);
    }
    return array_values($work_data);
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
    $result_record = $result->fetchAssoc();
    return [
      'data' => $result_record,
      'data_works' => $this->readWorksRatesForRateRecord($result_record),
    ];
  }

  private function readWorksRatesForRateRecord($result_record) {
    $rid = $this->get($result_record, 'rid', false);
    if ($rid === false) {
      return [];
    }
    $connection = Database::getConnection();
    $query = $connection->select('ckc_hodnoceni_works', 'hw')
      ->fields('hw', ['work_id', 'work_place', 'work_place_order', 'work_mlok'])
      ->condition('rid', (int) $rid);
    $results = $query->execute();
    $data_works = [];
    foreach ($results->fetchAllAssoc('work_id', \PDO::FETCH_ASSOC) as $result_work) {
      if (empty($result_work['work_place'])) {
        $data_works[$result_work['work_id']] = [
          'inputName' => '',
          'mlok' => 0,
        ];
        continue;
      }
      $data_works[$result_work['work_id']] = [
        'inputName' => "order_{$result_work['work_place']}_{$result_work['work_place_order']}",
        'mlok' => (int) $result_work['work_mlok'],
      ];
    }
    return $data_works;
  }

  private function updateRateRecord(FormStateInterface $form_state) {
    $rid = $form_state->getValue('rid');
    $connection = Database::getConnection();
    $transaction = $connection->startTransaction();
    try {
      // Update main data.
      $connection
        ->update('ckc_hodnoceni')
        ->fields($this->getHodnoceniWorkRow($form_state, $rid))
        ->condition('rid', $rid)
        ->execute();
      // Remove old data and insert updated data.
      $connection
        ->delete('ckc_hodnoceni_works')
        ->condition('rid', $rid)
        ->execute();
      $q2 = $connection->insert('ckc_hodnoceni_works')->fields(['rid', 'work_id', 'work_place', 'work_place_order', 'work_mlok']);
      foreach ($this->getHodnoceniWorksRows($rid, $form_state) as $record) {
        $q2->values($record);
      }
      $q2->execute();
      $this->messenger()->addStatus('Vaše uložené hodnocení bylo upraveno!');
    }
    catch (Exception $e) {
      $transaction->rollBack();
      $this->messenger()->addError('Vaše uložené hodnocení se nepovedlo upravit! Chyba:');
      $this->messenger()->addError($e->getMessage());
    }
  }

  private function deleteRateRecord(int $rid) {
    // $connection = Database::getConnection();
    // $query = $connection->delete('ckc_hodnoceni')
    //   ->condition('rid', $rid);
    // dpm((string) $query);
  }

  // private function logRateChangeToDb($rid, $data, $operation) {
  //   $connection = Database::getConnection();
  //   $txn = $connection->startTransaction();
  //
  //   $data_serialized = '';
  //   switch ($operation) {
  //     case 'C':
  //       $data_serialized = serialize([
  //         'old' => [],
  //         'new' => $data,
  //       ]);
  //       break;
  //     case 'U':
  //       $data_old = $this->readRateRecord($data['ckc_year'], $data['ckc_category'], $data['uid']);
  //       $data_serialized = serialize([
  //         'old' => $data_old,
  //         'new' => $data,
  //       ]);
  //       break;
  //   }
  //
  //   $query = $connection->insert('ckc_hodnoceni_log')
  //     ->fields([
  //       'rid' => $rid,
  //       'uid' => $data['uid'],
  //       'operation' => $operation,
  //       'data' => $data_serialized,
  //       'created' => $data['updated'],
  //     ]);
  //   try {
  //     $query->execute();
  //   }
  //   catch (Exception $e) {
  //     $txn->rollBack();
  //   }
  // }

  private function get($var, string $path = '', $default = null) {
    if (empty($var) || is_array($var) === false) {
      return $default;
    }
    if (empty($path)) {
      return $var;
    }
    $path_segments = explode('.', $path);
    $segment = array_shift($path_segments);
    $val = empty($var[$segment]) ? $default : $var[$segment];
    if (count($path_segments) === 0) {
      return $val;
    }
    if (count($path_segments) > 0 && is_array($val)) {
      return $this->get($val, join('.', $path_segments), $default);
    }
    return $default;
  }

}
