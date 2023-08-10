# Styling profiles

Drupal module which allows to define multiple (iq_barrio) theme settings.

## How to use
Go to /admin/config/system/styling-profiles to add a new styling profiles (or edit an existing one) and assing them to pages, domains etc. See [Submodules](#submodules) section for available ways to assign profiles.

The profile editing form is the same form as the theme settings form, so exactly the same settings can be configured.

## How does it work?
For each profile, all the SCSS files of both the iq_custom and iq_barrio themes are cloned and the _definition.scss file is render with the given settings. Based on mapping fo the profiles, the libraries are altered to load the clone resources instead of the original iq_barrio / iq_custom resources.

## Submodules

The styling_profiles modules is shipped with 2 submodules:

### styling_profiles_node_switch
Provies a styling_profile selection field which can be added to a content type. It enables the selection of styling profile on a per-node base. Instructions:
- Go to /admin/structure/types/manage/[content_type]/fields and add the field.
- Go to /admin/structure/types/manage/[content_type]/form-display and place the field inside the edit form. Choose the desired field widget (e.g. select or radios)
- Go to /admin/structure/types/manage/[content_type]/display and hide the field in the fronted.

### styling_profiles_domain_switch
Provies a styling_profile selection field for a domain. This requires the Drupal domain module with a proper setup. Go to /admin/config/domain/domain_profile_switch/config and assign the profiles to the domains.
