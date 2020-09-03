<?php

namespace Drupal\ckc_hodnoceni\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\Entity\User;
use Drupal\ckc_hodnoceni\CkcHodnoceniBase;
use Drupal\ckc_hodnoceni\CkcHodnoceniService;
use Drupal\views\Views;

/**
 * Implements an works import form.
 */
class CkcVotesImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckc_votes_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#theme'] = 'ckc_works_import_form';

    $form['validated'] = [
      '#type' => 'value',
      '#value' => $form_state->getValue('validated', FALSE),
    ];
    $form['ckc_year'] = [
      '#type' => 'value',
      '#value' => \Drupal::routeMatch()->getParameter('ckc_rocnik'),
    ];
    $form['parsed_import'] = [
      '#type' => 'value',
      '#value' => $form_state->getValue('parsed_import', []),
    ];

    $this->formStepInput($form, $form_state);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => ($form_state->getValue('validated', FALSE) ? 'Importovat' : 'Zkontrolovat'),
      '#button_type' => 'primary',
    ];
    if ($form_state->getValue('validated', FALSE) === TRUE) {
      $form['actions']['cancel'] = [
        '#type' => 'button',
        '#value' => 'Zrušit',
        '#executes_submit_callback' => TRUE,
      ];
      $form['actions']['cancel']['#submit'][] = '::cancelForm';
    }

    return $form;
  }

  private function formStepInput(array &$form, FormStateInterface $form_state) {
    $categories = CkcHodnoceniService::categories();
    array_walk($categories, function(&$v, $k) { return $v = "{$k}XX - {$v}"; });

    $validated = $form_state->getValue('validated', FALSE);
    $attributes = $validated ? ['disabled' => 'disabled'] : [];

    $form['ckc_category'] = [
      '#type' => 'select',
      '#title' => $validated ? 'Vybraná kategorie' : 'Vyberte kategorii',
      '#options' => $categories,
      '#attributes' => $attributes,
    ];
    if ($form_state->getValue('validated', FALSE) === FALSE) {
      $form['text_import'] = [
        '#type' => 'textarea',
        '#title' => 'Vložte hlasy pro import',
        '#cols' => 60,
        '#rows' => 18,
        '#default_value' => $form_state->getValue('text_import'),
        '#description' => <<<EOT
<p>Příklad pro vstup:</p>
<pre style="padding-left: 15px;">
Antonin_Dvorak
-3 117 -4 114 119 120 121 -5 105 106 122 126 133 -6 103 104 110 125 129 132
Blanka_Fucekova
-1 116 -2 108 110 -3 112 130 134 -4 101 117 120 129
...
</pre>
EOT,
      ];
    } else {
      $form['text_import_messages'] = [
        '#type' => 'textarea',
        '#title' => 'Hlasy které se budou importovat',
        '#cols' => 60,
        '#rows' => 18,
        '#default_value' => $form_state->getValue('text_import_messages', ''),
        '#attributes' => [
          'readonly' => 'readonly'
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // First check basic CSV data.
    if ($form_state->getValue('validated') === FALSE) {
      $data = $this->parseImport($form_state);
      if (empty($data)) {
        $form_state->setErrorByName('text_import', 'Chybějí data pro import!');
      } else {
        $errors = $this->validateData($data, $form_state);
        if (!empty($errors)) {
          $form_state->setErrorByName('text_import', new TranslatableMarkup('<ul><li>' . implode('</li><li>', $errors) . '</li></ul>'));
        } else {
          $form_state->setValue('parsed_import', $data);

          $text_import_messages = [];
          foreach ($data as $row) {
            if (empty($row['user_uid'])) {
              $str = 'nový porotce: '. $row['user_name'];
            } else {
              $str = 'existující porotce: '. $row['user_name'];
            }
            if ($row['vote_rid']) {
              $str .= ', upravuji hlasování: '. $row['vote_str'];
            } else {
              $str .= ', vkladám hlasování: '. $row['vote_str'];
            }
            $text_import_messages[] = $str;
          }
          $form_state->setValue('text_import_messages', implode(PHP_EOL, $text_import_messages));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('validated') === FALSE) {
      $form_state->setValue('validated', TRUE);
      $form_state->setRebuild(TRUE);
    } else {
      $skipped = 0;
      $updated = 0;
      $imported = 0;
      $data_for_import = $form_state->getValue('parsed_import', []);
      foreach ($data_for_import as $row) {
        if (empty($row['user_uid'])) {
          $user = $this->createUser($row['user_name']);
        } else {
          $user = user_load_by_name($row['user_name']);
        }
        $row['form_values']['uid'] = $user->id();
        $row['form_values']['rid'] = $row['vote_rid'];
        $row['form_values']['op'] = $row['vote_rid'] ? 'Upravit hodnocení' : 'Uložit hodnocení';
        $fs = (new FormState())->setValues($row['form_values']);
        \Drupal::formBuilder()->submitForm(
          '\Drupal\ckc_hodnoceni\Form\CkcRateForm',
          $fs,
          $row['form_values']['ckc_year'],
          $row['form_values']['ckc_category'],
          $user->id()
        );
        $errors = $fs->getErrors();
        if (empty($errors)) {
          if (!empty($row['vote_rid'])) {
            $updated++;
          } else {
            $imported++;
          }
        }
      }
      $this->messenger()->addStatus("{$skipped} hodnocení přeskočeno, {$updated} hodnocení upraveno a {$imported} hodnocení vloženo!");
      $view = Views::getView('ckc_hlasovani');
      $view->storage->invalidateCaches();
    }
  }

  private function validateData($data, FormStateInterface $form_state) {
    $selected_category = $form_state->getValue('ckc_category', NULL);
    $selected_category_str = str_pad($selected_category, 3, 'X', STR_PAD_RIGHT);
    $errors = [];
    $works = CkcHodnoceniService::works(
      $form_state->getValue('ckc_year', NULL),
      $selected_category,
    );
    $works_keys = array_map('strval', array_keys($works));

    foreach ($data as $row) {
      $invalid_values = [];
      foreach ($row['form_values'] as $key => $val) {
        if (strpos($key, 'order_') !== 0) {
          continue;
        }
        if (empty($val)) {
          continue;
        }
        if (!in_array($val, $works_keys)) {
          $invalid_values[] = $val;
        }
      }
      if (!empty($invalid_values)) {
        $errors[] = '<b>'. $row['user_name'] .'</b>: Nevalidní kód(y) <b>'. implode(', ', $invalid_values) .'</b> pro vybranou kategorii <b>'. $selected_category_str  .'</b>!';
      }
    }

    return $errors;
  }

  private function parseImport(FormStateInterface $form_state) {
    // Normalize EOL
    $string = preg_replace('/(*BSR_ANYCRLF)\R/', PHP_EOL, $form_state->getValue('text_import', ''));
    // Split string by lines and normalize lines
    $lines = preg_split('/'.PHP_EOL.'/', $string);
    $lines = array_filter($lines, function($v) { return !empty(trim($v)); });
    $lines = array_map(function($v) { return preg_replace('/\s\s+/', ' ', trim($v)); }, $lines);

    $data = [];
    $line_name = reset($lines);
    $line_vote_str = next($lines);
    do {
      // Check if line contains name
      if (preg_match('/^[^-]*$/', $line_name) && preg_match('/^-[1-6] /', $line_vote_str)) {
        $name = $line_name;
        $vote_str = $line_vote_str;
        // Prepare data
        $user = user_load_by_name($name);
        if ($user) {
          $rid = $this->readRateRecord($form_state->getValue('ckc_year', NULL), $form_state->getValue('ckc_category', NULL), $user->id());
        }
        $data[] = [
          'user_name' => $name,
          'user_uid' => $user ? $user->id() : NULL,
          'vote_str' => $vote_str,
          'vote_rid' => isset($rid) ? $rid : NULL,
          'form_values' => $this->prepareFormValues($form_state, $vote_str),
        ];
      }
      // Get next data
      $line_name = next($lines);
      $line_vote_str = next($lines);
    } while ($line_name && $line_vote_str);

    return $data;
  }

  private function prepareFormValues(FormStateInterface $form_state, string $vote_str) {
    $form_values = [];
    $form_values['rid'] = NULL;
    $form_values['ckc_year'] = NULL;
    $form_values['ckc_category'] = NULL;
    $form_values['uid'] = NULL;
    // $form_values['exclude_first_place'] = 0;
    $form_values['note'] = '';
    // $form_values['status'] = 0;
    foreach (CkcHodnoceniBase::CKC_HODNOCENI_TABLE_FIELDS_PLACES as $place) {
      $form_values[$place] = NULL;
    }

    // Fill values - base
    $form_values['ckc_year'] = $form_state->getValue('ckc_year', NULL);
    $form_values['ckc_category'] = $form_state->getValue('ckc_category', NULL);
    // Fill values - votes
    $votes_parsed = [];
    preg_match_all('/-([0-6])\s(?:([0-9]{3})\s?)(?:([0-9]{3})\s?)?(?:([0-9]{3})\s?)?(?:([0-9]{3})\s?)?(?:([0-9]{3})\s?)?(?:([0-9]{3})\s?)?/', $vote_str, $votes_parsed);
    foreach ($votes_parsed[1] as $key => $place) {
      switch ($place) {
        case 6:
          $form_values["order_{$place}_6"] = $votes_parsed[7][$key];
        case 5:
          $form_values["order_{$place}_5"] = $votes_parsed[6][$key];
        case 4:
          $form_values["order_{$place}_4"] = $votes_parsed[5][$key];
        case 3:
          $form_values["order_{$place}_3"] = $votes_parsed[4][$key];
        case 2:
          $form_values["order_{$place}_2"] = $votes_parsed[3][$key];
        case 1:
          $form_values["order_{$place}_1"] = $votes_parsed[2][$key];
      }
    }
    // if (empty($form_values["order_1_1"])) {
    //   $form_values['exclude_first_place'] = 1;
    // }

    return $form_values;
  }

  private function createUser($name) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Create new user
    $user = User::create();
    // Mandatory settings
    $user->enforceIsNew();
    $user->setUsername($name);
    $user->setPassword(md5($name));
    $user->setEmail("{$name}@example.com");
    // Optional settings
    $user->set("init", "{$name}@example.com");
    $user->set("langcode", $language);
    $user->set("preferred_langcode", $language);
    $user->set("preferred_admin_langcode", $language);
    $user->addRole('porotce');
    $user->activate();
    // Save new user
    $user->save();

    return $user;
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
    return $result_record ? $result_record['rid'] : NULL;
  }

}
