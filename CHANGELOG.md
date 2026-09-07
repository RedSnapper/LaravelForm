# Changelog

All notable changes to `rs/form-laravel` are documented here. Versions follow [semantic versioning](https://semver.org/).

## Unreleased (8.0.0)

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

### Upgrading

- If you have published the `form::components.form` view, keep the loop over `$form['hidden']`; the marker is rendered there.
- In tests that post to a formlet and assert on the redisplay, include `_formlet` in the posted data (`default`, or the form's prefix).
- Two unprefixed forms on one page both identify as `default`. Give them prefixes if they can fail validation independently.
- Remove application-side workarounds for the old behaviour, such as a `populateField()` override that copies old input verbatim whenever the session has any. Left in place they blank disabled or unrendered fields, and they ignore which form was posted.
- `setValue()`, `default()` and `isDirty()` are unchanged: `->setValue($this->model?->foo)` in `prepare()` still shows the default on a create form.
