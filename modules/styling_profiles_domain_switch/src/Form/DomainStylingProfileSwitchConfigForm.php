<?php

namespace Drupal\styling_profiles_domain_switch\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Defines the Google tag manager module and default container settings form.
 */
class DomainStylingProfileSwitchConfigForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a DomainStylingProfileSwitchConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Create function return static domain loader configuration.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   *
   * @return static
   *   return domain loader configuration.
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'),
        $container->get('entity_type.manager')
    );
  }

  /**
   * Function to get the list of styling profiles.
   *
   * @return array
   *   The complete theme registry data array.
   */
  public function getProfilesList() {
    $stylingProfiles = [];
    $stylingProfiles['_none'] = $this->t('None');
    foreach ($this->entityTypeManager->getStorage('styling_profile')->loadMultiple() as $profileKey => $profileData) {
      $stylingProfiles[$profileKey] = $profileData->label;
    }
    return $stylingProfiles;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'styling_profiles_domain_switch';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['styling_profiles_domain_switch.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('styling_profiles_domain_switch.settings');

    $profileNames = $this->getProfilesList();
    $domains = $this->entityTypeManager->getStorage('domain')->loadMultiple();
    foreach ($domains as $domain) {
      $domainId = $domain->id();
      $hostname = $domain->get('name');
      $form[$domainId] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Select Styling profile for "@domain"', ['@domain' => $hostname]),
      ];
      $form[$domainId][$domainId . '_site'] = [
        '#title' => $this->t('Styling profile for domain'),
        '#type' => 'select',
        '#options' => $profileNames,
        '#default_value' => $config->get($domainId . '_site'),
      ];
    }
    if (count($domains) === 0) {
      $form['styling_profiles_domain_switch_message'] = [
        '#markup' => $this->t('Zero domain records found. Please @link to create the domain.', [
          '@link' => Link::fromTextAndUrl($this->t('click here'), Url::fromRoute('domain.admin'))->toString(),
        ]),
      ];
      return $form;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $domains = $this->entityTypeManager->getStorage('domain')->loadMultiple();
    $config = $this->config('styling_profiles_domain_switch.settings');
    foreach ($domains as $domain) {
      $domainId = $domain->id();
      $config->set($domainId . '_site', $form_state->getValue($domainId . '_site'));
    }
    $config->save();
  }

}
