{{--
    Resolves which error/maintenance view to render for the current portal.

    config('multidomain.page_style') maps each portal to:
      'shared'    — the common design (errors.shared.page)
      'own'       — the portal's own set (errors.{portal}.page)
      '<portal>'  — reuse another portal's set verbatim (e.g. 'landing')

    Falls back to the shared page if the resolved style's view doesn't
    exist yet, so a stub that hasn't been customized never 500s.
--}}
@php
    $portal = app(\App\Support\PortalResolver::class)->resolve(request());
    $style = config("multidomain.page_style.{$portal}", 'shared');

    $candidates = match ($style) {
        'shared' => ['errors.shared.page'],
        'own' => ["errors.{$portal}.page", 'errors.shared.page'],
        default => ["errors.{$style}.page", 'errors.shared.page'],
    };
@endphp
@includeFirst($candidates, ['code' => $code, 'meta' => \App\Support\ErrorPageMeta::for($code), 'message' => $message ?? null])
