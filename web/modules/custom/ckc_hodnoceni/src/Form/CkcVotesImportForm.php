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
    $form['#theme'] = 'ckc_votes_import_form';

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

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
