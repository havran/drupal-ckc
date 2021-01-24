<?php

namespace Drupal\ckc_hodnoceni\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckc_hodnoceni\CkcHodnoceniService;

/**
 * Defines a form that configures forms module settings.
 */
class CkcHodnoceniSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckc_hodnoceni_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ckc_hodnoceni.settings',
    ];
  }

  public function main_title() {
    return "CKČ nastavení";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $years_options = array_reduce(
      CkcHodnoceniService::get_years(),
      function ($acc, $i) {
        $status_string = $i['locked'] ? 'uzamčen' : 'aktivní';
        $acc[$i['id']] = "{$i['name']} ({$status_string})";
        return $acc;
      },
      []
    );
    $config = $this->config('ckc_hodnoceni.settings');
    $form['year_active'] = [
      '#type' => 'select',
      '#title' => 'Výber aktivního ročníku soutěže',
      '#options' => $years_options,
      '#default_value' => $config->get('year_active'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $years = CkcHodnoceniService::year_map('id');
    $year_active = $form_state->getValue('year_active');
    $year_active_string = $years[$year_active]['name'];
    $this->config('ckc_hodnoceni.settings')
      ->set('year_active', $form_state->getValue('year_active'))
      ->save();
    CkcHodnoceniService::set_active_year($form_state->getValue('year_active'));
    \Drupal::service('user.private_tempstore')->get('ckc_hodnoceni')->set('year_selected', $form_state->getValue('year_active'));
    $this->messenger()->addStatus("Ročník {$year_active_string} je teď aktivní! Ostatní ročníky byly uzamčeny, změna uložených dat byla zakázána.");
    parent::submitForm($form, $form_state);
  }

}
