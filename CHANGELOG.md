# Changelog

All notable changes to `rs/form-laravel` are documented here. Versions follow [semantic versioning](https://semver.org/).

## 8.0.1 - 2026-10-07

Resolves GM-128: the package logged deprecation notices on PHP 8.4 and 8.5. No behaviour changes; PHP 8.1 is still supported.

### Fixed

- Parameters that default to `null` now declare an explicit nullable type (`?string`, `?\Closure`), so the package loads without "implicitly marking parameter as nullable" notices on PHP 8.4 and 8.5.
- `HasRelationships::hasMany()` no longer gives `$closure` a default. The default was never usable because the required `$count` follows it, and on PHP 8.0+ it triggered its own deprecation once made nullable. `relation()` still defaults `$closure` to `null`.
- CI now tests on PHP 8.1 to 8.5, lints `src/` for deprecations, and fails the test run on any deprecation.

## 8.0.0 - 2026-09-29

Resolves GM-126 and GM-127: values the user emptied were reverted when a form redisplayed after a validation failure.

### Changed

On a validation-failure redisplay, for the form that was posted:

- A text input, textarea or select the user cleared stays cleared instead of reverting to the model value or the field default.
- A checkbox the user unticked stays unticked, and a `CheckboxGroup` or multiple `Select` the user fully emptied stays empty, instead of reverting to the model.

Other forms on the page, and old input flashed by forms that are not formlets, are unaffected. Resolution on a plain `GET` with no old input is unchanged. See "Redisplay after a validation failure" in the README for the full rules.

### Added

- Every form renders a hidden `_formlet` input naming the form (its prefix, or `default`). It identifies which form was posted.
- `AbstractField::clearValue()` and `isCleared()`: empty a field so that its default does not apply, as distinct from `setValue(null)`.
- `AbstractField::populatesWhenAbsent()` and `getAbsentValue()`: a custom field type can say what its absence from a submission means. Defaults to `[]` for `multiple()` fields; `Checkbox` returns its unchecked value.

### Fixed

- `Select::multiple(false)` and `Input::multiple(false)` on a file input now switch the field back to single-value. Previously the argument was dropped and the field stayed multiple.
- A multiple file input keeps its model value on a validation-failure redisplay. Files are never flashed to old input, so its absence carries no signal.

### Upgrading

- If you have published the `form::components.form` view, keep the loop over `$form['hidden']`; the marker is rendered there.
- In tests that post to a formlet and assert on the redisplay, include `_formlet` in the posted data (`default`, or the form's prefix).
- Two unprefixed forms on one page both identify as `default`. Give them prefixes if they can fail validation independently.
- Remove application-side workarounds for the old behaviour, such as a `populateField()` override that copies old input verbatim whenever the session has any. Left in place they blank disabled or unrendered fields, and they ignore which form was posted.
- `setValue()`, `default()` and `isDirty()` are unchanged: `->setValue($this->model?->foo)` in `prepare()` still shows the default on a create form.
