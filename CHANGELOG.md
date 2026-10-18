# Changelog

All notable changes to `alfasix/alfakit-calendar` are documented in this file.

## Unreleased

- Initial release: `CalendarPlugin` (timezone, locale, first day, `editable()`, `selectable()`, sanitised `config()`), the `CalendarWidget` base class (`fetchEvents()`, month/week/day/list views, event click, date select and header actions, `onEventDrop()` / `onEventResize()` with authorisation and revert, resource and tenant scoping) and the `Event` builder. Rendered by the `widget:calendar` component in `@alfakit/react`, `@alfakit/vue` and `@alfakit/svelte`.
