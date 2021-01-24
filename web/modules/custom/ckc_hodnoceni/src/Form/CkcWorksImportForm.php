<?php

namespace Drupal\ckc_hodnoceni\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckc_hodnoceni\CkcHodnoceniService;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Implements an works import form.
 */
class CkcWorksImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckc_works_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $year_from_url = \Drupal::routeMatch()->getParameter('ckc_rocnik');
    $active = CkcHodnoceniService::active($year_from_url);

    if (!$active) {
      $this->messenger()->addWarning("Import prací bude přeskočen, protože ročník {$year_from_url} je uzamčen!");
    }

    $form['#theme'] = 'ckc_works_import_form';

    $form['validated'] = [
      '#type' => 'value',
      '#value' => $form_state->getValue('validated', FALSE),
    ];
    $form['ckc_year'] = [
      '#type' => 'value',
      '#value' => $year_from_url,
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
      '#disabled' => $form_state->getValue('validated', FALSE) === TRUE && ($active ? FALSE : TRUE),
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
        '#title' => 'Vložte seznam povídek pro import',
        '#cols' => 60,
        '#rows' => 18,
        '#default_value' => $form_state->getValue('text_import'),
        '#description' => 'Každý řádek musí obsahovat kód práce, tabulační znak a název práce.',
      ];
    } else {
      $form['text_import_messages'] = [
        '#type' => 'textarea',
        '#title' => 'Povídky které se budou upravovat po změně nebo importovat',
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
        $errors = $this->validateCode($data, $form_state);
        if (!empty($errors)) {
          $form_state->setErrorByName('text_import', new TranslatableMarkup('<ul><li>' . implode('</li><li>', $errors) . '</li></ul>'));
        } else {
          $data_normalized = $this->normalizeImport($data, $form_state);
          $form_state->setValue('parsed_import', $data_normalized);

          $text_import_messages = [];
          foreach ($data_normalized as $item) {
            if (isset($item['__node'])) {
              if ($item['__node']->title->value === $item['title']) {
                $str = 'už importována'."\t". $item['code'] ." ". $item['title'] ;
              } else {
                $str = 'změněna          '."\t";
                $str .= $item['__node']->field_kategorie_ref->entity->field_kod_kategorie->value . $item['__node']->field_poradi_povidky->value ." ". $item['__node']->title->value .' -> ';
                $str .= $item['code'] ." ". $item['title'];
              }
            } else {
              $str = 'vložit jako novou '."\t". $item['code'] .' '. $item['title'];
            }
            $text_import_messages[] = $str;
          }
          $form_state->setValue('text_import_messages', implode(PHP_EOL, $text_import_messages));
        }
      }
    }
    // Second validation???
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('validated') === FALSE) {
      $form_state->setValue('validated', TRUE);
      $form_state->setRebuild(TRUE);
    } else {
      $ckc_year = $form_state->getValue('ckc_year');
      $active = CkcHodnoceniService::active($ckc_year);
      if ($active) {
        $skipped = 0;
        $updated = 0;
        $imported = 0;
        $data_for_import = $form_state->getValue('parsed_import', []);
        foreach ($data_for_import as $row) {
          if (isset($row['__node'])) {
            if ($row['__node']->title->value === $row['title']) {
              $skipped++;
              continue;
            } else {
              $row['__node']->set('title', $row['title']);
              $row['__node']->save();
              $updated++;
              continue;
            }
          }
          $node = Node::create([
            'type' => 'povidka',
            'title' => $row['title'],
            'field_rocnik_ref' => (int)$row['year_id'],
            'field_kategorie_ref' => (int)$row['category_id'],
            'field_poradi_povidky' => substr($row['code'], 1, 2),
          ]);
          $node->save();
          $imported++;
        }
        // ksm($data_for_import);
        $this->messenger()->addStatus("{$skipped} prací přeskočeno, {$updated} prací upraveno a {$imported} prací vloženo!");
      } else {
        $this->messenger()->addError("Import prací byl přeskočen, protože ročník {$ckc_year} je uzamčen!");
      }
    }

    // ksm($form_state->getValues());
    // ksm($form);
    // $this->messenger()->addStatus($this->t('Your phone number is @number', ['@number' => $form_state->getValue('phone_number')]));
  }

  /**
   * {@inheritdoc}
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addWarning('Import byl zrušen!');
  }

  private function parseImport(FormStateInterface $form_state) {
    $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
    $data = $serializer->decode(
      "code\ttitle\n" .  $form_state->getValue('text_import', ''),
      'csv',
      [ CsvEncoder::DELIMITER_KEY => "\t" ],
    );
    return $data;
  }

  private function normalizeImport($data, FormStateInterface $form_state) {
    // Prepare mapping for years.
    $year_map = \Drupal::service('ckc_hodnoceni.service')->year_map();
    // Prepare mapping for categories.
    $category_map = \Drupal::service('ckc_hodnoceni.service')->category_map();
    // Normalize import.
    $data_normalized = array_map(
      function($row) use ($form_state, $year_map, $category_map) {
        $year = $form_state->getValue('ckc_year', null);
        $year_id = $year_map[$year]['id'];
        $category = $form_state->getValue('ckc_category', null);
        $category_id = $category_map[$category];
        $code = str_pad($row['code'], 3, '0', STR_PAD_LEFT);
        return [
          'year' => $year,
          'year_id' => $year_id,
          'category' => $category,
          'category_id' => $category_id,
          'code' => $code,
          'title' => $row['title'],
          '__node' => $this->getImportedWork((int)$year_id, (int)$category_id, $code),
        ];
      },
      $data
    );
    return $data_normalized;
  }

  private function validateCode($data, FormStateInterface $form_state) {
    $errors = [];
    if (isset($data['code'])) {
      $data = [$data];
    }
    foreach ($data as $i => $row) {
      // validate code
      $selected_category = $form_state->getValue('ckc_category', null);
      $match = preg_match(
        '/['. $selected_category .'][0-9]{2}/',
        str_pad($row['code'], 3, '0', STR_PAD_LEFT)
      );
      $selected_category_str = str_pad($selected_category, 3, 'X', STR_PAD_RIGHT);
      if ($match !== 1) {
        $errors[] = 'Nevalidní kód <b>'. $row['code'] .'</b> pro vybranou kategorii <b>'. $selected_category_str  .'</b> v řádku: <i>'. $row['code'] ."\t". $row['title'] .'</i>!';
      }
    }
    return $errors;
  }

  public function getImportedWork(int $year_id, int $category_id, string $code) {
    $node_etm = \Drupal::entityTypeManager()
      ->getStorage('node');
    $node_nids = $node_etm->getQuery()
      ->condition('type', 'povidka')
      ->condition('field_rocnik_ref', $year_id)
      ->condition('field_kategorie_ref', $category_id)
      ->condition('field_poradi_povidky', substr($code, 1, 2))
      ->execute();
    $nid = array_values($node_nids)[0];
    return isset($nid) ? $node_etm->load($nid) : null;
  }
}
