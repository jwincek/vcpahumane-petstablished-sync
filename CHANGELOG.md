# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-06-23

### Added
- Initial public release.
- Batched sync from the Petstablished public API with an admin progress UI and WP-Cron scheduling.
- `vcps_pet` custom post type with taxonomy filtering (species, breed, age, size, gender, color) and URL-driven compatibility filters.
- Block library: pet card, listing grid, slider, filters, details, gallery, actions, attributes, health, compatibility, comparison, compare bar, favorites (toggle and modal), adoption CTA, adoption action, adoption fee, breadcrumb, tagline, notifications toast, and back-to-top.
- Adoption action block supports three modes: Petstablished application form link, internal page link, or PDF download.
- Plugin-wide notification region (`petsync/pet-toast`) surfacing favorites/comparison/sharing confirmations and sync errors — visible toast plus screen-reader announcement from a single aria-live region.
- Hover/focus tooltips on the icon-only pet-actions overlay buttons, driven by their state-aware aria-labels.
- Anonymous favorites and side-by-side comparison powered by the Interactivity API.
- Block templates and template parts editable in the Site Editor, including user customizations (served from the plugin's `wp_theme` namespace).
- Built on the WordPress Abilities API and Block Bindings; server-rendered blocks with no build step.

[Unreleased]: https://github.com/jwincek/vcpahumane-pet-sync/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/jwincek/vcpahumane-pet-sync/releases/tag/v1.0.0
